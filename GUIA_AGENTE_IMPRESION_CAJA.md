# Agente de impresión en la caja (Nissi)

Nissi no abre el diálogo de impresión del navegador si en la PC de la caja está corriendo el agente local. El navegador le manda el ticket o el KuDE a `https://127.0.0.1:4171` y el agente lo imprime con la impresora de Windows.

El programa está en `nissi_ventas/agente_impresion`. Hay que instalarlo en cada caja, no en el servidor.

Si el agente está apagado, la venta igual se guarda y Nissi abre el comprobante en una ventana, como antes.

## Qué imprime

| Comprobante en la venta | Qué sale |
|---|---|
| Ticket | PDF angosto generado por Nissi |
| Factura electrónica | El HTML del KuDE que devuelve la API, sin armar un PDF en Nissi. Si el cliente está en ticket, ese HTML ya mide 80 mm. Si está en A4, es hoja. |

Una PC, una impresora. El rollo térmico y la hoja A4 no pueden ser la misma impresora. La caja que factura en ticket tiene que tener la ticketera como predeterminada. La que factura en A4, la impresora de hoja.

## 1. Copiar el agente

En la PC de la caja, copiá la carpeta `agente_impresion` del repositorio. Tiene que estar `requirements.txt` junto a `install_autostart.ps1` y `print_agent.py`.

Ejemplo:

```text
C:\nissi\agente_impresion
```

No copies `.venv` de otra máquina. El instalador lo crea ahí.

## 2. Impresora de Windows

Instalá el driver del fabricante (Epson, Xprinter, etc.).

En Configuración → Bluetooth y dispositivos → Impresoras:

- Dejá esa impresora como predeterminada.
- No uses Microsoft Print to PDF ni "Imprimir a PDF": el agente se queda esperando un nombre de archivo.

Papel del rollo: 80 mm, salvo que la ticketera sea de 58 mm. En Windows, el tamaño de papel del driver tiene que coincidir con el rollo.

## 3. Instalar y que arranque al entrar

PowerShell con el usuario que usa la caja (el que abre Nissi). No hace falta administrador si ese usuario puede crear tareas.

Python 3.10 o superior, con **Add python.exe to PATH** marcado en el instalador.

```powershell
cd C:\nissi\agente_impresion
Set-ExecutionPolicy -Scope Process Bypass
.\install_autostart.ps1
```

Eso hace lo siguiente:

1. Crea el entorno `.venv` e instala las dependencias.
2. Copia `config.example.ini` a `config.ini` si todavía no existe.
3. Crea la tarea `NissiImpresionCaja`, que arranca al iniciar sesión de ese usuario.
4. Crea un certificado para `https://127.0.0.1` y prueba `https://127.0.0.1:4171/status`.

Queda corriendo sin ventana. Cerrar el navegador no lo detiene. Al volver a entrar a Windows arranca solo.

Para ver la consola mientras se prueba:

```bat
run_foreground.bat
```

Esa ventana, al cerrarla, apaga el agente. No la dejes como forma de uso diario.

Para sacar el arranque automático:

```powershell
.\uninstall_autostart.ps1
```

## 4. Elegir la impresora

Si la predeterminada de Windows ya es la ticketera, dejá `name` vacío.

Para fijarla por nombre:

```powershell
.venv\Scripts\python.exe print_agent.py --list-printers
```

Copiá el nombre tal cual a `config.ini`:

```ini
[server]
host = 127.0.0.1
port = 4171

[printer]
name = EPSON TM-T20III Receipt
width_mm = 80
escpos = auto
```

`width_mm` y `escpos` aplican en Linux. En Windows manda el driver. Después de editar `config.ini`, cerrá sesión y volvé a entrar, o reiniciá la tarea `NissiImpresionCaja` en el Programador de tareas.

## 5. Probar antes de vender

En la caja:

```bat
curl http://127.0.0.1:4171/status
```

Tiene que responder algo como `"status": "ok"` y el nombre de la impresora.

Página de prueba:

```bat
.venv\Scripts\python.exe print_agent.py --test-page
```

Tiene que salir un papel en la ticketera, sin ventana de impresión.

En el navegador, abrí `https://127.0.0.1:4171/status`. Si Chrome avisa del certificado, entrá igual (Avanzado → continuar) una vez. El instalador ya lo deja confiado para el usuario de la caja. Si no, Nissi no puede hablar con el agente y vuelve a abrir el PDF en pantalla.

Después cobrá una venta de prueba en Nissi:

- Con el agente en línea, no aparece el diálogo de impresión y sale el papel.
- Si apagás el agente y cobrás de nuevo, se abre la ventana del comprobante. Así se confirma el plan B.

## 6. Dónde tiene que estar

El agente escucha solo en esa PC (`127.0.0.1`). Nissi puede estar en otro servidor. El navegador de la caja es el que llama a localhost.

No sirve instalarlo en el servidor si el cajero usa otra computadora. Cada caja lleva su copia.

Nissi ya llama al puerto `4171`. No hay una pantalla en el sistema para cambiar esa dirección. Si cambiás el puerto en `config.ini`, también hay que cambiarlo en `nissi_ventas/sistema/js/functions_v3.js` (`urlsAgenteImpresion`).

## Si no imprime

| Qué pasa | Qué revisar |
|---|---|
| Se abre el PDF en el navegador | El agente no respondió. `curl http://127.0.0.1:4171/status` en esa misma PC. |
| `/status` no responde | La tarea no arrancó. Mirá el Programador de tareas, `NissiImpresionCaja`, y `print_agent.log` en `agente_impresion`. |
| Chrome dice que el certificado no es válido | Abrí `https://127.0.0.1:4171/status` y aceptá la excepción, o volvé a correr `install_autostart.ps1` con el usuario de la caja. |
| Imprime en otra impresora | La predeterminada de Windows, o `name` en `config.ini`. |
| El ticket sale chico o cortado | El papel del driver no es el del rollo (80 mm). En factura electrónica, el formato ticket o A4 se elige en la API, no en Nissi. |
| Pide guardar un PDF | La impresora predeterminada es "Microsoft Print to PDF". Cambiala. |

El log queda en `print_agent.log`, al lado de `print_agent.py`.
