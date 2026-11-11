#!/usr/bin/env python3
"""Agente de impresion local para la caja de Nissi. Solo escucha en 127.0.0.1."""

from __future__ import annotations

import argparse
import base64
import configparser
import datetime
import io
import ipaddress
import json
import logging
import os
import shutil
import ssl
import subprocess
import sys
import tempfile
import threading
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from typing import Any
from urllib.parse import urlparse

IS_WINDOWS = sys.platform == "win32"
AGENT_DIR = Path(__file__).resolve().parent
DEFAULT_HOST = "127.0.0.1"
DEFAULT_PORT = 4171
MAX_BODY_BYTES = 8 * 1024 * 1024

LOG = logging.getLogger("nissi_impresion")

try:
    from PIL import Image
except ImportError:  # pragma: no cover
    Image = None  # type: ignore


def _loopback_hosts() -> set[str]:
    return {"127.0.0.1", "::1", "localhost"}


def default_config_path() -> Path:
    return AGENT_DIR / "config.ini"


def load_config(path: Path | None = None) -> configparser.ConfigParser:
    cfg = configparser.ConfigParser()
    cfg["server"] = {"host": DEFAULT_HOST, "port": str(DEFAULT_PORT)}
    cfg["printer"] = {"name": "", "width_mm": "80"}
    cfg_path = path or default_config_path()
    if cfg_path.exists():
        cfg.read(cfg_path, encoding="utf-8")
    return cfg


def bind_host(cfg: configparser.ConfigParser) -> str:
    host = cfg.get("server", "host", fallback=DEFAULT_HOST).strip() or DEFAULT_HOST
    if host not in _loopback_hosts():
        LOG.warning("Refusing non-localhost bind %s; using %s", host, DEFAULT_HOST)
        return DEFAULT_HOST
    return host


def configured_printer_name(cfg: configparser.ConfigParser) -> str:
    return cfg.get("printer", "name", fallback="").strip()


def cert_paths() -> tuple[Path, Path]:
    cert_dir = AGENT_DIR / "certs"
    return cert_dir / "cert.pem", cert_dir / "key.pem"


def ensure_tls_certs() -> tuple[Path, Path] | None:
    """Crea un certificado de localhost para que Nissi en HTTPS pueda llamar al agente."""
    cert_path, key_path = cert_paths()
    if cert_path.is_file() and key_path.is_file():
        return cert_path, key_path
    try:
        from cryptography import x509
        from cryptography.hazmat.primitives import hashes, serialization
        from cryptography.hazmat.primitives.asymmetric import rsa
        from cryptography.x509.oid import NameOID
    except ImportError:
        LOG.warning("Instala 'cryptography' para habilitar HTTPS (hace falta si Nissi se abre por https://)")
        return None

    cert_path.parent.mkdir(parents=True, exist_ok=True)
    key = rsa.generate_private_key(public_exponent=65537, key_size=2048)
    subject = x509.Name([x509.NameAttribute(NameOID.COMMON_NAME, "127.0.0.1")])
    now = datetime.datetime.now(datetime.timezone.utc)
    san = x509.SubjectAlternativeName(
        [
            x509.DNSName("localhost"),
            x509.IPAddress(ipaddress.IPv4Address("127.0.0.1")),
            x509.IPAddress(ipaddress.IPv6Address("::1")),
        ]
    )
    cert = (
        x509.CertificateBuilder()
        .subject_name(subject)
        .issuer_name(subject)
        .public_key(key.public_key())
        .serial_number(x509.random_serial_number())
        .not_valid_before(now - datetime.timedelta(days=1))
        .not_valid_after(now + datetime.timedelta(days=3650))
        .add_extension(san, critical=False)
        .add_extension(x509.BasicConstraints(ca=True, path_length=None), critical=True)
        .sign(key, hashes.SHA256())
    )
    key_path.write_bytes(
        key.private_bytes(
            encoding=serialization.Encoding.PEM,
            format=serialization.PrivateFormat.TraditionalOpenSSL,
            encryption_algorithm=serialization.NoEncryption(),
        )
    )
    cert_path.write_bytes(cert.public_bytes(serialization.Encoding.PEM))
    LOG.info("Created TLS certificate %s", cert_path)
    return cert_path, key_path


def trust_windows_root(cert_path: Path) -> None:
    if not IS_WINDOWS or not cert_path.is_file():
        return
    result = subprocess.run(
        ["certutil", "-addstore", "-user", "Root", str(cert_path)],
        capture_output=True,
        text=True,
        check=False,
    )
    if result.returncode == 0:
        LOG.info("Trusted agent certificate in CurrentUser Root")
    else:
        LOG.warning("Could not trust certificate (certutil): %s", result.stderr or result.stdout)


def make_ssl_context() -> ssl.SSLContext | None:
    paths = ensure_tls_certs()
    if not paths:
        return None
    cert_path, key_path = paths
    trust_windows_root(cert_path)
    context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    context.load_cert_chain(certfile=str(cert_path), keyfile=str(key_path))
    return context


def configured_width_mm(cfg: configparser.ConfigParser) -> float:
    return cfg.getfloat("printer", "width_mm", fallback=80.0)


def configured_escpos_mode(cfg: configparser.ConfigParser) -> str:
    return cfg.get("printer", "escpos", fallback="auto").strip().lower()


def match_printer(requested: str, names: list[str], default: str = "") -> str:
    """Resolve config.ini name against the OS list (exact, case, unique substring)."""
    requested = (requested or "").strip()
    if requested:
        if requested in names:
            return requested
        lower = requested.lower()
        same = [name for name in names if name.lower() == lower]
        if len(same) == 1:
            return same[0]
        partial = [name for name in names if lower in name.lower() or name.lower() in lower]
        if len(partial) == 1:
            LOG.info("Printer %r matched %s", requested, partial[0])
            return partial[0]
        if len(partial) > 1:
            raise PrintError(
                f"Printer {requested!r} is ambiguous ({', '.join(partial)}). "
                "Set the exact name from: python print_agent.py --list-printers"
            )
        if default:
            LOG.warning(
                "Printer %r not found; using default %s. Available: %s",
                requested,
                default,
                ", ".join(names) or "(none)",
            )
            return default
        raise PrintError(
            f"Printer not found: {requested}. Available: {', '.join(names) or '(none)'}"
        )
    if default:
        return default
    if len(names) == 1:
        return names[0]
    raise PrintError(
        f"No default printer. Set [printer] name in config.ini. Available: {', '.join(names) or '(none)'}"
    )


def width_mm_to_pixels(width_mm: float, dpi: float = 203.0, max_px: int = 576) -> int:
    """576 px ≈ área imprimible 80 mm @ 203 dpi (térmicas ESC/POS)."""
    px = int(width_mm / 25.4 * dpi)
    return max(1, min(px, max_px))


def cups_printer_details(printer: str) -> str:
    chunks: list[str] = []
    for argv in (["lpstat", "-l", "-p", printer], ["lpoptions", "-p", printer]):
        try:
            proc = subprocess.run(
                argv,
                capture_output=True,
                text=True,
                timeout=10,
                check=False,
            )
            chunks.extend((proc.stdout or "", proc.stderr or ""))
        except (FileNotFoundError, subprocess.TimeoutExpired):
            continue
    return "\n".join(chunks).lower()


def should_use_escpos(printer: str, mode: str) -> bool:
    if mode in {"1", "true", "yes", "on"}:
        return True
    if mode in {"0", "false", "no", "off"}:
        return False
    details = cups_printer_details(printer)
    raw_markers = (
        "raw printer",
        "local raw",
        "impresora en bruto",
        "printer-make-and-model='local raw",
        "printer-make-and-model=local raw",
    )
    return any(marker in details for marker in raw_markers)


def image_to_escpos_raster(image: Any, width_px: int, fragment_height: int = 960) -> bytes:
    """GS v 0 bit image raster (ESC/POS, same family as python-escpos bitImageRaster)."""
    if Image is None:
        raise PrintError("Pillow is required")
    from PIL import ImageOps

    try:
        resample = Image.Resampling.LANCZOS
    except AttributeError:
        resample = Image.LANCZOS
    src = image.convert("RGBA")
    canvas = Image.new("RGB", src.size, (255, 255, 255))
    canvas.paste(src, mask=src.split()[3])
    img = canvas.convert("L")
    if img.width != width_px:
        height = max(1, round(img.height * width_px / img.width))
        img = img.resize((width_px, height), resample)
    img = ImageOps.invert(img)
    img = img.convert("1")

    out = bytearray(b"\x1b\x40")  # ESC @
    y = 0
    while y < img.height:
        chunk_h = min(fragment_height, img.height - y)
        chunk = img.crop((0, y, img.width, y + chunk_h))
        width_bytes = (chunk.width + 7) >> 3
        header = bytes(
            [
                0x1D,
                0x76,
                0x30,
                0x00,  # GS v 0 — high density H+V
                width_bytes & 0xFF,
                (width_bytes >> 8) & 0xFF,
                chunk_h & 0xFF,
                (chunk_h >> 8) & 0xFF,
            ]
        )
        out.extend(header)
        out.extend(chunk.tobytes())
        y += chunk_h
    out.extend(b"\n\n\x1dV\x00")  # feed + partial cut
    return bytes(out)


class PrintError(Exception):
    pass


class FilePrintBackend:
    """Dev/test backend: writes the last job to a folder."""

    def __init__(self, output_dir: Path, printer_name: str = "File"):
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        self._printer_name = printer_name
        self.last_path: Path | None = None

    @property
    def printer_name(self) -> str:
        return self._printer_name

    def list_printers(self) -> list[str]:
        return [self._printer_name]

    def print_image(self, image: Any, title: str = "Nissi") -> str:
        path = self.output_dir / "last_ticket.png"
        image.convert("RGB").save(path)
        self.last_path = path
        return self._printer_name

    def print_pdf(self, pdf_bytes: bytes, title: str = "Nissi") -> str:
        path = self.output_dir / "last_ticket.pdf"
        path.write_bytes(pdf_bytes)
        self.last_path = path
        return self._printer_name


def _cups_cli_available() -> bool:
    return shutil.which("lp") is not None and shutil.which("lpstat") is not None


def _run_cups_command(argv: list[str], *, timeout: int = 30) -> subprocess.CompletedProcess[str]:
    try:
        return subprocess.run(
            argv,
            check=True,
            capture_output=True,
            text=True,
            timeout=timeout,
        )
    except FileNotFoundError as exc:
        raise PrintError("CUPS not found (install cups-client)") from exc
    except subprocess.CalledProcessError as exc:
        detail = (exc.stderr or exc.stdout or "").strip()
        raise PrintError(detail or f"Command failed: {' '.join(argv)}") from exc
    except subprocess.TimeoutExpired as exc:
        raise PrintError(f"Timed out: {' '.join(argv)}") from exc


def cups_default_printer() -> str:
    proc = _run_cups_command(["lpstat", "-d"])
    text = proc.stdout.strip()
    lower = text.lower()
    if "no system default" in lower or "no hay un destino" in lower:
        return ""
    if ":" in text:
        return text.split(":", 1)[1].strip()
    return ""


def cups_list_printer_names() -> list[str]:
    proc = _run_cups_command(["lpstat", "-a"])
    names: list[str] = []
    for line in proc.stdout.splitlines():
        line = line.strip()
        if not line:
            continue
        lower = line.lower()
        if " accepting " in lower or " aceptando " in lower:
            names.append(line.split()[0])
    return names


class CupsPrintBackend:
    """Linux/macOS: print via CUPS (lp)."""

    def __init__(self, printer_name: str = "", width_mm: float = 80.0, escpos_mode: str = "auto"):
        if not _cups_cli_available():
            raise PrintError("CUPS lp/lpstat not available")
        self.configured_name = printer_name.strip()
        self.width_mm = width_mm
        self.escpos_mode = escpos_mode
        self._escpos_cached: bool | None = None

    def _resolve_printer(self) -> str:
        if self.configured_name:
            return self.configured_name
        default = cups_default_printer()
        if default:
            return default
        printers = self.list_printers()
        if len(printers) == 1:
            return printers[0]
        raise PrintError(
            "No default CUPS printer; set [printer] name in config.ini "
            "or run: lpoptions -d NombreImpresora"
        )

    @property
    def printer_name(self) -> str:
        return self._resolve_printer()

    def list_printers(self) -> list[str]:
        return cups_list_printer_names()

    def _ensure_printer(self, printer: str) -> str:
        return match_printer(printer, self.list_printers(), cups_default_printer())

    def _target_width_px(self) -> int:
        return width_mm_to_pixels(self.width_mm)

    def _escpos_enabled(self, printer: str) -> bool:
        if self._escpos_cached is None:
            self._escpos_cached = should_use_escpos(printer, self.escpos_mode)
            if self._escpos_cached:
                LOG.info(
                    "ESC/POS raster for %s (%spx wide, %.0fmm paper)",
                    printer,
                    self._target_width_px(),
                    self.width_mm,
                )
        return self._escpos_cached

    def _lp_print_file(self, path: Path, title: str, extra_options: list[str] | None = None) -> str:
        printer = self._ensure_printer(self._resolve_printer())
        cmd = ["lp", "-d", printer, "-t", title or "Nissi"]
        if extra_options:
            cmd.extend(extra_options)
        cmd.append(str(path))
        _run_cups_command(cmd, timeout=120)
        return printer

    def _lp_raw_bytes(self, data: bytes, title: str) -> str:
        with tempfile.NamedTemporaryFile(delete=False) as tmp:
            path = Path(tmp.name)
        try:
            path.write_bytes(data)
            return self._lp_print_file(
                path,
                title,
                ["-o", "raw", "-o", "document-format=application/octet-stream"],
            )
        finally:
            path.unlink(missing_ok=True)

    def print_image(self, image: Any, title: str = "Nissi") -> str:
        printer = self._ensure_printer(self._resolve_printer())
        if self._escpos_enabled(printer):
            payload = image_to_escpos_raster(image, self._target_width_px())
            self._lp_raw_bytes(payload, title)
            return printer
        with tempfile.NamedTemporaryFile(suffix=".png", delete=False) as tmp:
            path = Path(tmp.name)
        try:
            image.convert("RGB").save(path)
            return self._lp_print_file(path, title, ["-o", "fit-to-page"])
        finally:
            path.unlink(missing_ok=True)

    def print_pdf(self, pdf_bytes: bytes, title: str = "Nissi") -> str:
        printer = self._ensure_printer(self._resolve_printer())
        if self._escpos_enabled(printer):
            images = pdf_to_images(pdf_bytes)
            if not images:
                raise PrintError("PDF has no pages")
            for index, image in enumerate(images):
                page_title = title if index == 0 else f"{title} ({index + 1})"
                payload = image_to_escpos_raster(image, self._target_width_px())
                self._lp_raw_bytes(payload, page_title)
            return printer
        with tempfile.NamedTemporaryFile(suffix=".pdf", delete=False) as tmp:
            path = Path(tmp.name)
        try:
            path.write_bytes(pdf_bytes)
            return self._lp_print_file(path, title)
        finally:
            path.unlink(missing_ok=True)


class WindowsPrintBackend:
    def __init__(self, printer_name: str = ""):
        if not IS_WINDOWS:
            raise PrintError("Windows print backend is only available on Windows")
        self.configured_name = printer_name.strip()

    def _default_printer(self) -> str:
        import win32print

        try:
            return win32print.GetDefaultPrinter() or ""
        except Exception:
            return ""

    @property
    def printer_name(self) -> str:
        return match_printer(self.configured_name, self.list_printers(), self._default_printer())

    def list_printers(self) -> list[str]:
        import win32print

        flags = win32print.PRINTER_ENUM_LOCAL | win32print.PRINTER_ENUM_CONNECTIONS
        return [item[2] for item in win32print.EnumPrinters(flags)]

    def print_image(self, image: Any, title: str = "Nissi") -> str:
        import win32ui
        from PIL import ImageWin

        printer = self.printer_name

        img = image.convert("RGB")
        hdc = win32ui.CreateDC()
        hdc.CreatePrinterDC(printer)
        printable_w = hdc.GetDeviceCaps(8)  # HORZRES
        printable_h = hdc.GetDeviceCaps(10)  # VERTRES
        if printable_w <= 0 or printable_h <= 0 or img.width <= 0:
            hdc.DeleteDC()
            raise PrintError("Invalid printer or image size")

        dest_w = printable_w
        dest_h = max(1, int(img.height * (printable_w / img.width)))
        hdc.StartDoc(title or "Nissi")
        offset = 0
        try:
            while offset < dest_h:
                chunk_h = min(printable_h, dest_h - offset)
                src_y0 = int(offset * img.height / dest_h)
                src_y1 = int((offset + chunk_h) * img.height / dest_h)
                src_y1 = max(src_y0 + 1, src_y1)
                crop = img.crop((0, src_y0, img.width, min(img.height, src_y1)))
                hdc.StartPage()
                dib = ImageWin.Dib(crop)
                dib.draw(hdc.GetHandleOutput(), (0, 0, dest_w, chunk_h))
                hdc.EndPage()
                offset += chunk_h
            hdc.EndDoc()
        except Exception:
            try:
                hdc.AbortDoc()
            except Exception:
                pass
            raise
        finally:
            hdc.DeleteDC()
        return printer

    def print_pdf(self, pdf_bytes: bytes, title: str = "Nissi") -> str:
        images = pdf_to_images(pdf_bytes)
        if not images:
            raise PrintError("PDF has no pages")
        printer = self.printer_name
        for index, image in enumerate(images):
            page_title = title if index == 0 else f"{title} ({index + 1})"
            self.print_image(image, title=page_title)
        return printer


def find_edge() -> Path | None:
    env = os.environ.get("POS_LOCAL_PRINT_EDGE")
    candidates = []
    if env:
        candidates.append(Path(env))
    candidates.extend(
        [
            Path(r"C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"),
            Path(r"C:\Program Files\Microsoft\Edge\Application\msedge.exe"),
        ]
    )
    for path in candidates:
        if path.is_file():
            return path
    return None


def html_to_pdf_bytes(html: str) -> bytes:
    edge = find_edge()
    if not edge:
        raise PrintError("Microsoft Edge was not found; send image or PDF instead")
    with tempfile.TemporaryDirectory(prefix="nissi_impresion_") as tmp:
        html_path = Path(tmp) / "ticket.html"
        pdf_path = Path(tmp) / "ticket.pdf"
        html_path.write_text(html, encoding="utf-8")
        cmd = [
            str(edge),
            "--headless=new",
            "--disable-gpu",
            "--no-first-run",
            "--no-default-browser-check",
            "--no-pdf-header-footer",
            f"--print-to-pdf={pdf_path}",
            str(html_path),
        ]
        try:
            subprocess.run(cmd, check=True, timeout=30, capture_output=True)
        except (subprocess.CalledProcessError, subprocess.TimeoutExpired) as exc:
            raise PrintError(f"Edge could not convert HTML to PDF: {exc}") from exc
        if not pdf_path.is_file():
            raise PrintError("Edge did not produce a PDF")
        return pdf_path.read_bytes()


def pdf_to_images(pdf_bytes: bytes) -> list[Any]:
    try:
        import pypdfium2 as pdfium
    except ImportError as exc:
        raise PrintError("pypdfium2 is required to print PDF") from exc
    document = pdfium.PdfDocument(pdf_bytes)
    images = []
    for index in range(len(document)):
        page = document[index]
        bitmap = page.render(scale=2)
        images.append(bitmap.to_pil())
    return images


def decode_image(value: str) -> Any:
    if Image is None:
        raise PrintError("Pillow is required")
    raw = value.strip()
    if raw.startswith("data:") and "," in raw:
        raw = raw.split(",", 1)[1]
    data = base64.b64decode(raw)
    return Image.open(io.BytesIO(data)).convert("RGB")


def decode_pdf(value: str) -> bytes:
    raw = value.strip()
    if raw.startswith("data:") and "," in raw:
        raw = raw.split(",", 1)[1]
    return base64.b64decode(raw)


def make_backend(cfg: configparser.ConfigParser, output_dir: Path | None = None):
    if output_dir is not None:
        return FilePrintBackend(output_dir)
    if IS_WINDOWS:
        return WindowsPrintBackend(configured_printer_name(cfg))
    printer_name = configured_printer_name(cfg)
    try:
        backend = CupsPrintBackend(
            printer_name,
            width_mm=configured_width_mm(cfg),
            escpos_mode=configured_escpos_mode(cfg),
        )
        LOG.info("Using CUPS printer backend (lp)")
        return backend
    except PrintError as exc:
        fallback = AGENT_DIR / "out"
        LOG.warning("CUPS unavailable (%s); writing tickets to %s", exc, fallback)
        return FilePrintBackend(fallback, printer_name="File (non-Windows)")


class PrintApp:
    def __init__(self, cfg: configparser.ConfigParser, backend):
        self.cfg = cfg
        self.backend = backend

    def status(self) -> dict[str, Any]:
        try:
            printer = self.backend.printer_name
            printers = self.backend.list_printers()
        except Exception as exc:
            return {"status": "error", "ok": False, "error": str(exc)}
        return {
            "status": "ok",
            "ok": True,
            "printer": printer,
            "printers": printers,
        }

    def print_job(self, payload: dict[str, Any]) -> dict[str, Any]:
        title = str(payload.get("title") or "Nissi")
        image_b64 = payload.get("image") or payload.get("png") or payload.get("jpeg")
        pdf_b64 = payload.get("pdf")
        html = payload.get("html")
        printer = self.backend.printer_name

        if image_b64:
            image = decode_image(str(image_b64))
            printer = self.backend.print_image(image, title=title)
            return {"ok": True, "printer": printer, "via": "image"}

        if pdf_b64:
            pdf_bytes = decode_pdf(str(pdf_b64))
            printer = self.backend.print_pdf(pdf_bytes, title=title)
            return {"ok": True, "printer": printer, "via": "pdf"}

        if html:
            pdf_bytes = html_to_pdf_bytes(str(html))
            printer = self.backend.print_pdf(pdf_bytes, title=title)
            return {"ok": True, "printer": printer, "via": "html"}

        raise PrintError("Send html, pdf or image")


def _cors_headers(request_headers: str | None = None) -> dict[str, str]:
    allow_headers = request_headers or "Content-Type"
    return {
        "Access-Control-Allow-Origin": "*",
        "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
        "Access-Control-Allow-Headers": allow_headers,
        "Access-Control-Allow-Private-Network": "true",
        "Access-Control-Max-Age": "86400",
    }


def make_handler(app: PrintApp):
    class Handler(BaseHTTPRequestHandler):
        server_version = "NissiImpresion/1.0"

        def log_message(self, fmt: str, *args: Any) -> None:
            LOG.info("%s - %s", self.address_string(), fmt % args)

        def _client_allowed(self) -> bool:
            host = self.client_address[0]
            return host in _loopback_hosts()

        def _write(self, code: int, body: bytes, content_type: str = "application/json") -> None:
            self.send_response(code)
            requested = self.headers.get("Access-Control-Request-Headers")
            for key, value in _cors_headers(requested).items():
                self.send_header(key, value)
            self.send_header("Content-Type", content_type)
            self.send_header("Content-Length", str(len(body)))
            self.send_header("Cache-Control", "no-store")
            self.end_headers()
            self.wfile.write(body)

        def _json(self, code: int, payload: dict[str, Any]) -> None:
            self._write(code, json.dumps(payload).encode("utf-8"))

        def do_OPTIONS(self) -> None:
            self._write(204, b"")

        def do_GET(self) -> None:
            if not self._client_allowed():
                self._json(403, {"ok": False, "error": "localhost only"})
                return
            path = urlparse(self.path).path
            if path in ("/status", "/"):
                payload = app.status()
                code = 200 if payload.get("ok") else 503
                self._json(code, payload)
                return
            self._json(404, {"ok": False, "error": "not found"})

        def do_POST(self) -> None:
            if not self._client_allowed():
                self._json(403, {"ok": False, "error": "localhost only"})
                return
            path = urlparse(self.path).path
            if path != "/print":
                self._json(404, {"ok": False, "error": "not found"})
                return
            length = int(self.headers.get("Content-Length") or 0)
            if length <= 0 or length > MAX_BODY_BYTES:
                self._json(400, {"ok": False, "error": "invalid body size"})
                return
            raw = self.rfile.read(length)
            content_type = (self.headers.get("Content-Type") or "").split(";", 1)[0].strip()
            try:
                if content_type == "text/html":
                    payload = {"html": raw.decode("utf-8"), "title": "Nissi"}
                else:
                    payload = json.loads(raw.decode("utf-8"))
                    if not isinstance(payload, dict):
                        raise ValueError("JSON object required")
            except (UnicodeDecodeError, json.JSONDecodeError, ValueError) as exc:
                self._json(400, {"ok": False, "error": f"invalid payload: {exc}"})
                return
            try:
                result = app.print_job(payload)
            except PrintError as exc:
                LOG.warning("Print failed: %s", exc)
                self._json(500, {"ok": False, "error": str(exc)})
                return
            except Exception as exc:
                LOG.exception("Unexpected print error")
                self._json(500, {"ok": False, "error": str(exc)})
                return
            self._json(200, result)

    return Handler


def serve(
    app: PrintApp,
    host: str,
    port: int,
    ssl_context: ssl.SSLContext | None = None,
) -> ThreadingHTTPServer:
    server = ThreadingHTTPServer((host, port), make_handler(app))
    scheme = "http"
    if ssl_context is not None:
        server.socket = ssl_context.wrap_socket(server.socket, server_side=True)
        scheme = "https"
    LOG.info("Listening on %s://%s:%s  printer=%s", scheme, host, port, app.backend.printer_name)
    return server


def run_forever(
    app: PrintApp,
    host: str,
    port: int,
    ssl_context: ssl.SSLContext | None = None,
) -> None:
    server = serve(app, host, port, ssl_context=ssl_context)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        LOG.info("Stopped")
    finally:
        server.server_close()


def setup_logging(log_file: Path | None = None) -> None:
    handlers: list[logging.Handler] = [logging.StreamHandler(sys.stdout)]
    if log_file is not None:
        log_file.parent.mkdir(parents=True, exist_ok=True)
        handlers.append(logging.FileHandler(log_file, encoding="utf-8"))
    logging.basicConfig(
        level=logging.INFO,
        format="%(asctime)s %(levelname)s %(message)s",
        handlers=handlers,
    )


def _test_page_image_b64() -> str:
    if Image is None:
        raise PrintError("Pillow is required for test page")
    from PIL import ImageDraw

    img = Image.new("RGB", (576, 240), "white")
    draw = ImageDraw.Draw(img)
    draw.text((24, 24), "Nissi", fill="black")
    draw.text((24, 64), "Prueba de impresion local", fill="black")
    buf = io.BytesIO()
    img.save(buf, format="PNG")
    return base64.b64encode(buf.getvalue()).decode("ascii")


def print_test_page(app: PrintApp) -> None:
    if find_edge() is not None:
        html = (
            "<html><body style='font-family:monospace;width:80mm'>"
            "<h1>Nissi</h1><p>Prueba de impresión local</p>"
            "</body></html>"
        )
        result = app.print_job({"html": html, "title": "Prueba Nissi"})
    else:
        result = app.print_job({"image": _test_page_image_b64(), "title": "Prueba Nissi"})
    print(json.dumps(result, indent=2))


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description="Agente de impresion local de Nissi")
    parser.add_argument("--serve", action="store_true", help="Start HTTP/HTTPS server")
    parser.add_argument("--no-tls", action="store_true", help="Listen HTTP only (Chrome blocks this from https POS)")
    parser.add_argument("--config", type=Path, default=None)
    parser.add_argument("--host", default=None)
    parser.add_argument("--port", type=int, default=None)
    parser.add_argument("--printer", default=None, help="Override printer name")
    parser.add_argument("--output-dir", type=Path, default=None, help="Write files instead of printing")
    parser.add_argument("--list-printers", action="store_true")
    parser.add_argument("--test-page", action="store_true")
    parser.add_argument("--log-file", type=Path, default=AGENT_DIR / "print_agent.log")
    args = parser.parse_args(argv)

    setup_logging(args.log_file)
    cfg = load_config(args.config)
    if args.printer is not None:
        cfg["printer"]["name"] = args.printer

    backend = make_backend(cfg, output_dir=args.output_dir)
    app = PrintApp(cfg, backend)

    if args.list_printers:
        print(json.dumps({"printer": backend.printer_name, "printers": backend.list_printers()}, indent=2))
        return 0
    if args.test_page:
        print_test_page(app)
        return 0

    host = args.host or bind_host(cfg)
    if host not in _loopback_hosts():
        LOG.error("Host must be localhost")
        return 2
    port = args.port or cfg.getint("server", "port", fallback=DEFAULT_PORT)
    ssl_context = None if args.no_tls else make_ssl_context()
    if ssl_context is None and not args.no_tls:
        LOG.warning("Arranque solo HTTP. Si Nissi se abre por https, Chrome bloquea la impresion.")
    run_forever(app, host, port, ssl_context=ssl_context)
    return 0


if IS_WINDOWS:
    try:
        import win32event
        import win32service
        import win32serviceutil
        import servicemanager
    except ImportError:
        win32serviceutil = None  # type: ignore
    else:

        class NissiImpresionCajaService(win32serviceutil.ServiceFramework):
            _svc_name_ = "NissiImpresionCaja"
            _svc_display_name_ = "Nissi impresion de caja"
            _svc_description_ = "Imprime tickets y KuDE de Nissi por el spooler de Windows (solo localhost)."

            def __init__(self, args):
                win32serviceutil.ServiceFramework.__init__(self, args)
                self.stop_event = win32event.CreateEvent(None, 0, 0, None)
                self.server = None
                self.thread = None

            def SvcStop(self):
                self.ReportServiceStatus(win32service.SERVICE_STOP_PENDING)
                if self.server:
                    self.server.shutdown()
                win32event.SetEvent(self.stop_event)

            def SvcDoRun(self):
                servicemanager.LogInfoMsg("Nissi impresion de caja iniciando")
                setup_logging(AGENT_DIR / "print_agent.log")
                cfg = load_config()
                app = PrintApp(cfg, make_backend(cfg))
                host = bind_host(cfg)
                port = cfg.getint("server", "port", fallback=DEFAULT_PORT)
                self.server = serve(app, host, port, ssl_context=make_ssl_context())
                self.thread = threading.Thread(target=self.server.serve_forever, daemon=True)
                self.thread.start()
                win32event.WaitForSingleObject(self.stop_event, win32event.INFINITE)
                self.server.server_close()


if __name__ == "__main__":
    if (
        IS_WINDOWS
        and "win32serviceutil" in globals()
        and win32serviceutil is not None
        and len(sys.argv) > 1
        and sys.argv[1] in {"install", "remove", "start", "stop", "restart", "debug", "update"}
    ):
        win32serviceutil.HandleCommandLine(NissiImpresionCajaService)
    else:
        if not (len(sys.argv) > 1 and sys.argv[1] in {"--serve", "--list-printers", "--test-page"}):
            if "--help" not in sys.argv and "-h" not in sys.argv:
                sys.argv.insert(1, "--serve")
        raise SystemExit(main())
