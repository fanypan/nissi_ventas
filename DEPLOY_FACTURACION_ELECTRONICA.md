# Actualizar facturación electrónica en producción

Este documento explica cómo llevar a producción la emisión de facturas electrónicas (pasarela SIFEN) del sistema de ventas.

El código vive en el repositorio `nissi_ventas` (`https://github.com/fanypan/nissi_ventas.git`, rama `main`). El `docker-compose.yml` y el `Dockerfile` de la carpeta padre sirven para levantar el proyecto en Windows y **no forman parte de este repositorio**.

## Qué hace

Cuando el vendedor elige **Factura** (no Ticket) y la facturación electrónica está activa, PHP llama a la pasarela SIFEN. Si la respuesta ya trae el KuDE firmado, la caja lo abre en una ventana aunque el estado siga en proceso. Ese enlace dura unos minutos, no lleva el token y no se guarda. El token queda en el servidor. Si más tarde hace falta el archivo, se baja de nuevo con el token.

Si SIFEN falla, la venta igual queda guardada y se imprime el comprobante interno.

## 1. Bajar los cambios con git

En el servidor, dentro de la carpeta del sistema (la que contiene `conexion.php`, `sistema/`, etc.):

```bash
git status
git pull origin main
```

Hacé un backup de la base antes del pull. Ya existe una carpeta `backup/` en el proyecto; también podés exportar desde phpMyAdmin.

Archivos que entran con este cambio:

- `conexion.php` (crea las tablas si no existen)
- `sistema/includes/sifen_client.php` (nuevo)
- `sistema/ajax.php`
- `sistema/configuracion_factura_electronica.php` (nuevo)
- `sistema/includes/nav.php`
- `sistema/js/functions_v3.js`
- `DEPLOY_FACTURACION_ELECTRONICA.md`

No hace falta recompilar PHP ni tocar el procedimiento `procesar_venta`. Si en el servidor los PHP están montados por volumen (`./nissi_ventas:/var/www/html`), alcanza con el `git pull` y recargar la página. Reconstruí la imagen Docker solo si cambiaste el `Dockerfile` de la carpeta padre (hace falta la extensión `curl`).

## 2. Tablas

Se crean solas la primera vez que se abre cualquier página, porque `conexion.php` llama a `ensureFacturacionTables()`.

Si preferís crearlas a mano en phpMyAdmin, ejecutá:

```sql
CREATE TABLE IF NOT EXISTS `config_facturacion_electronica` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `api_url` VARCHAR(255) NULL DEFAULT NULL,
  `api_token` VARCHAR(255) NULL DEFAULT NULL,
  `ambiente` VARCHAR(10) NOT NULL DEFAULT 'test',
  `establecimiento` VARCHAR(10) NOT NULL DEFAULT '001',
  `punto` VARCHAR(10) NOT NULL DEFAULT '001',
  `moneda` VARCHAR(5) NOT NULL DEFAULT 'PYG',
  `iva_por_defecto` INT NOT NULL DEFAULT 10,
  `activo` TINYINT NOT NULL DEFAULT 0,
  `timbrado` VARCHAR(20) NULL DEFAULT NULL,
  `date_add` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_upd` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `factura_electronica` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `noventa` INT NOT NULL,
  `document_id` VARCHAR(64) NULL DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'queued',
  `cdc` VARCHAR(44) NULL DEFAULT NULL,
  `qr_url` VARCHAR(500) NULL DEFAULT NULL,
  `kude_path` VARCHAR(500) NULL DEFAULT NULL,
  `numero` INT NULL DEFAULT NULL,
  `idempotency_key` VARCHAR(191) NULL DEFAULT NULL,
  `error_message` TEXT NULL,
  `request_payload` TEXT NULL,
  `response_raw` MEDIUMTEXT NULL,
  `date_add` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_fe_idempotency` (`idempotency_key`),
  KEY `idx_fe_noventa` (`noventa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

No se agregan columnas a `venta` ni a `producto`. El IVA sale de `iva_por_defecto` (10% al empezar).

El cliente gana una columna para el correo. Se crea sola al abrir el sistema. Si preferís hacerlo a mano:

```sql
ALTER TABLE `cliente` ADD COLUMN `email` VARCHAR(120) NULL DEFAULT NULL AFTER `direccion`;
```

Ese correo se manda como `cliente.email`. Si está cargado y la pasarela tiene servidor de correo, ella envía el PDF. El plazo de una factura a crédito sale de la venta (por defecto 30 días) y va en `condicion.credito.plazo`. La forma de pago de una venta al contado sale del tipo elegido en caja. Departamento, distrito y ciudad del receptor los completa la pasarela.

## 3. Qué cargar a mano (no va en git)

Entrá como **Administrador** a **Otros → Configuración factura electrónica**. Los demás roles no ven ese menú, la página los devuelve al inicio, y el servidor rechaza el guardado si el rol no es administrador.

Completá:

| Campo | Valor en producción |
|---|---|
| URL de la API | URL pública HTTPS, por ejemplo `https://api.sifen.arandutech.com` |
| Token | `sk_live_…` entregado por AranduTech |
| Ambiente | Producción |
| Establecimiento | El de la SET, por ejemplo `001` |
| Punto de expedición | El de la SET, por ejemplo `001` |
| Timbrado | Número de timbrado vigente |
| Moneda | `PYG` |
| IVA por defecto | `10` (o `5` / `0` si corresponde a todo el local) |
| Activar emisión | Marcado |

El token queda en la base, no en el repositorio. Si guardás de nuevo y dejás el campo token vacío, se conserva el anterior.

No hace falta editar variables en el código. Opcionalmente, si la URL o el token están vacíos en la base, el sistema usa las variables de entorno `SIFEN_API_URL` y `SIFEN_API_TOKEN`. Esas variables, si se usan, se cargan en el servidor (`.env` o `docker-compose.yml` de la carpeta padre). Esos archivos **no** se suben con este git. Lo recomendado es cargar todo desde la vista.

## 4. Red: no uses localhost dentro de Docker

`http://localhost:8088` apunta al propio contenedor, no a la PC. En producción usá la URL pública HTTPS.

Para probar en Windows contra una pasarela local, en el `docker-compose.yml` de la carpeta padre (fuera del repo) agregá al servicio `app`:

```yaml
extra_hosts:
  - "host.docker.internal:host-gateway"
```

Y en la vista poné `http://host.docker.internal:8088`.

El PHP necesita la extensión `curl`. El `Dockerfile` local ya la instala; en un PHP de Windows tenés que tener `extension=curl` habilitada. Si no está, el sistema intenta `file_get_contents` (requiere `allow_url_fopen`).

## 5. Checklist homologación → producción

1. Backup de la base.
2. `git pull` en el servidor.
3. Abrí el sistema una vez para que se creen las tablas (o ejecutá el SQL de arriba).
4. En la vista, cargá primero el ambiente de homologación: URL de test y token `sk_test_…`.
5. Pulsá **Probar conexión**. Tiene que responder OK (`GET /api/health`).
6. Activá la emisión y hacé una venta eligiendo **Factura**. Tiene que abrir el KuDE. En `factura_electronica` debe quedar `status = approved` y un `cdc`.
7. Una venta con **Ticket** no debe llamar a SIFEN.
8. Cuando la SET habilite producción, cambiá la URL, el token a `sk_live_…` y el ambiente a Producción. Volvé a probar conexión y emití una factura real de bajo monto.
9. Confirmá establecimiento, punto y timbrado con los datos del contribuyente. La numeración la asigna la pasarela: el sistema no envía número de factura.

## 6. Si una factura queda en proceso

La venta ya está guardada. El estado queda en `factura_electronica` (`queued`, `processing`, `approved`, `rejected` o `error`). El detalle del rechazo está en `error_message`. Volver a emitir la misma venta no genera otro documento: se usa la clave `venta-{noventa}`.
