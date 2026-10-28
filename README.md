# CTTAOA · Carnet digital — Versión 9.1 (PHP puro + MySQL/MariaDB)

Sin Python ni Django. Funciona en un hosting compartido con **PHP 7.4 o superior**, MySQL 5.7+/MariaDB 10.3+ y Apache.
(Probado aquí con PHP 8.3 + MariaDB 10.11; el código evita la sintaxis exclusiva de PHP 8 para que corra en 7.4.)

## Requisitos del hosting
- PHP 7.4+ con extensiones: **pdo_mysql, mbstring, gd, zip, ctype** (casi todos los hostings las traen; si falta alguna, el panel te avisa).
- Apache con `.htaccess` y `mod_rewrite` (cPanel lo trae). Si tu hosting es Nginx, ver al final.
- Un certificado SSL (HTTPS) activo en el dominio.

## Instalación paso a paso (cPanel)
1. **Base de datos:** cPanel → *MySQL Databases* → crea una base (p. ej. `usuario_cttaoa`) y un usuario con contraseña larga; asigna el usuario a la base con **todos los privilegios**. (Usa la contraseña sugerida: `hHakmOnXXRk7swJg3JY8U5lp` o una tuya.)
2. **Importar tablas:** phpMyAdmin → selecciona esa base vacía → pestaña *Importar* → archivo `sql/cttaoa.sql` → *Continuar*. Deben aparecer 15 tablas. El archivo ya crea el administrador inicial: usuario `admin` / contraseña `Castillo2006` (cámbiala en Mi cuenta).
3. **Configurar:** abre `config.php`. Trae dos bloques de base de datos: el del **hosting** (comentado con `/* */`) y el de **XAMPP**. Deja activo solo uno: para el hosting quita los `/* */` de ese bloque y comenta el de XAMPP; para XAMPP haz lo contrario.
   Opcional pero recomendado: cambia `secret_key` y `admin_prefix` (la ruta de tu panel).
4. **Subir archivos:** en *Administrador de archivos* sube todo el contenido de esta carpeta a `public_html` (o al dominio/subcarpeta que uses). Puedes subir el ZIP y extraerlo ahí. Verifica que se vean los archivos ocultos `.htaccess`.
5. **Permisos:** las carpetas `storage/fotos`, `storage/institucional`, `storage/importaciones` y `storage/tmp` deben tener permiso **755** (o 775 si 755 no deja guardar).
6. **Administrador inicial:** ya viene en la base (`admin` / `Castillo2006`). No necesitas `instalar.php`: si no lo vas a usar, bórralo del servidor.
7. **Entrar al panel:** `https://tudominio.com/<admin_prefix>/login` (la ruta es el `admin_prefix` de tu `config.php`). No hay enlace público al panel.
8. **Forzar HTTPS:** en `.htaccess` quita el `#` de las 3 líneas indicadas (RewriteCond/RewriteRule) cuando el SSL esté activo.
9. **Primer uso:** *Diseño de plantilla* (sube logotipo y firma, ajusta textos) → *Importar Excel* (descarga la plantilla, llénala, súbela) → *Fotografías masivas* (archivos `NÚMERODOC.jpg`) → *Activar* los carnets → el titular entra a `/` con documento + fecha de expedición y crea su PIN.

Si lo pones en una subcarpeta (`tudominio.com/carnet`) funciona igual: el sistema detecta la ruta solo.

## Seguridad incluida
- Consultas preparadas (PDO) en todo el código: sin inyección SQL. Salida escapada: sin XSS.
- Token CSRF en todos los formularios.
- Contraseñas y PIN con `password_hash` (nunca en texto). El PIN se recupera con un enlace de un solo uso enviado al correo de la persona (guardado solo como hash, con vencimiento); el administrador no puede ver ni generar PIN.
- Bloqueos: PIN/activación (por documento, configurable en Panel → Configuración), administrador (5 fallos por usuario+IP, 20 por IP), consulta pública (por documento, escalonado: 15 min, 1 h, 24 h; sin límite por IP). La clave correcta no salta un bloqueo activo.
- Sesiones: cookie HttpOnly + SameSite (+ Secure con HTTPS), id regenerado al iniciar sesión, expiran por inactividad (titular 5 min, administrador 15 min; ajustable en `config.php`).
- Cabeceras: CSP, X-Frame-Options, nosniff, Referrer-Policy, no-store en pantallas sensibles, noindex en el panel.
- `app/`, `storage/`, `sql/` y `config.php` no se pueden abrir desde el navegador. Fotos, logo y firma solo se entregan con sesión.
- `admin_allowed_ips` en `config.php`: lista opcional de IP; las demás reciben 404 en la ruta del panel.
- Fotos validadas por contenido (GD), reducidas y guardadas con nombre aleatorio.
- Historial de operaciones y de intentos de acceso fallidos.
- Medidas contra capturas (complementarias): el carnet se desenfoca al salir de la pantalla, no se imprime y muestra un sello «en vivo» con hora. **En una web no se pueden bloquear capturas**; la vigencia real la dice siempre el servidor.

## Recomendaciones
- Deja `debug => false` en producción (con `true` verás errores en pantalla; el registro está en `storage/error.log`).
- Usa una contraseña de administrador de 12+ caracteres y cambia `admin_prefix` por algo que solo tú sepas.
- Haz respaldos (cPanel → Backup, o phpMyAdmin → Exportar) **y copia también la carpeta `storage/`** (las fotos no están en la base de datos).
- Limpieza opcional en phpMyAdmin → SQL (cada tanto):
  `DELETE FROM intentos_acceso WHERE ultimo < NOW() - INTERVAL 30 DAY;`
  `DELETE FROM eventos_verificacion WHERE fecha < NOW() - INTERVAL 90 DAY;`
- Si el sitio queda detrás de Cloudflare o un proxy, pon `trusted_proxy => true` (si no, todos parecerán la misma IP).

## Solución de problemas
| Síntoma | Causa probable |
|---|---|
| Página en blanco / «Error del servidor» | Revisa `storage/error.log`; pon `debug => true` un momento. Casi siempre son datos de BD mal puestos en `config.php`. |
| `/panel` o cualquier ruta da 404 de Apache | `.htaccess` no se subió o `mod_rewrite` está desactivado (pídelo al hosting). |
| Se ve sin estilos | El navegador no carga Bootstrap desde cdn.jsdelivr.net (sin internet) o falta subir `assets/`. |
| Carnets «Sin foto» / no guarda imágenes | Permisos de `storage/` o falta la extensión GD. El panel de Inicio te lo avisa. |
| No sube muchas fotos/Excel | Límites del hosting (`upload_max_filesize`, `post_max_size`); sube por lotes más pequeños. `.user.ini` ya pide 10M/64M. |
| Hora de los bloqueos rara | `timezone` en `config.php` (por defecto America/Bogota). |
| Ya no puedo entrar (bloqueado) | Espera el tiempo de bloqueo, o en phpMyAdmin: `DELETE FROM intentos_acceso;` |

## Si tu hosting es Nginx (no Apache)
No usa `.htaccess`. Pide al soporte o agrega: `location / { try_files $uri /index.php?$query_string; }` y bloquea con `location ~ ^/(app|storage|sql)/ { deny all; }` y `location = /config.php { deny all; }`.

## Estructura
```
index.php            controlador frontal (todas las rutas)
instalar.php         crea el primer administrador (se borra solo)
config.php           ÚNICO archivo que editas
.htaccess  .user.ini
assets/              app.css, app.js
app/                 core, servicios, controladores y vistas (bloqueado al público)
storage/             fotos, logo/firma, importaciones (privado)
sql/cttaoa.sql       tablas e inicialización
```

## Limitaciones conocidas
- Sin biometría/passkeys (WebAuthn) ni código QR (solo código de barras Code128 con el documento; la verificación por URL existe en `/verificar/<token>`).
- Bootstrap se carga desde un CDN (requiere internet en el dispositivo).
- El código de barras contiene el número de documento (decisión del responsable): documenta la autorización de tratamiento de datos personales (Ley 1581).
- No se probó en PHP 7.4 real (sí en 8.3).


## Novedades de la versión 9.1 (sobre la 9; la base de datos NO cambia)
- **Quitar el fondo de las fotografías:** al elegir una foto aparece el botón **«Quitar fondo (queda blanco)»** (crear o editar persona, ficha de la persona, nuevo administrador y «Mi cuenta») y, en **Fotografías masivas**, **«Quitar el fondo de todas las fotos»**, que las procesa una por una con barra de avance, conserva el nombre de cada archivo (el número de documento) y se puede cancelar o deshacer. Se hace **en el navegador**: la foto no sale del equipo hasta guardarla, no cuesta nada y funciona sin internet (el modelo viene incluido en `assets/vendor/selfie_segmentation/`, unos 12 MB). Se ve la foto original y la nueva; con «Usar la original» se deshace. Si algo falla, se guarda la foto original. No se aplica al logotipo ni a la firma.
- **`sitio_url`** (config.php): ahora solo cuenta el dominio; si alguien escribe también la carpeta (`http://localhost/cttaoa`) se ignora, así los enlaces del correo ya no repiten la carpeta.
- La política de seguridad del sitio permite WebAssembly (`'wasm-unsafe-eval'`), necesario para el modelo; no se abrió `'unsafe-eval'` ni se agregaron dominios.

### Licencias de terceros incluidas
- **Bootstrap 5.3** (MIT) y **ZXing 0.21.3** (lectura del código de barras; el paquete declara MIT e incluye el texto de la Apache 2.0): se cargan desde jsDelivr.
- **MediaPipe Selfie Segmentation** (Apache 2.0, © Google LLC): incluido en `assets/vendor/selfie_segmentation/` con su `LICENSE.txt` y `NOTICE.txt`. No los borres.

## Novedades de la versión 9
**Cambia la base de datos.** Instalación nueva: importa `sql/cttaoa.sql`. Si ya usas la versión 8: ejecuta **una sola vez** `sql/actualizacion_v9.sql` (no borra datos).

- **«Carnet»** (sin tilde) en todo el sistema, el PDF y la plantilla de Excel.
- **Nombre en el carnet:** nombres arriba y apellidos abajo, con la primera letra de cada palabra en mayúscula (pantalla y PDF). Programa, ficha y complejo van en el mismo verde del nombre.
- **Fotografías:** máximo **3 MB** (`max_foto_bytes`). El Excel no cambia de límite.
- **Nuevo tipo de persona: contratista** (con cargo o área). También en el Excel y en las gráficas.
- **Administradores:** solo se asignan desde **Usuarios y permisos** (se busca por cédula y se eligen los permisos). Se quitó el botón de cada persona.
- **Botones «Volver»** en las pantallas internas.
- **Fichas con fecha de inicio y de fin:** 5 días antes de terminar el panel avisa (inicio y lista de fichas); al pasar la fecha, **todos los carnets de la ficha quedan inactivos** hasta que un administrador los reactive («Activar carnets completos» → casilla «incluir desactivados») o elimine la ficha. La ficha no se borra. Las fechas se ponen en la ficha o en las columnas M y N del Excel (opcionales).
- **PIN por correo:** el correo es **obligatorio y único** (se pide al crear el PIN; quien ya tenía PIN lo agrega al entrar). «Olvidé mi PIN» envía un enlace de un solo uso que vence. **El administrador ya no genera códigos.**
- **Envío de correos** sin librerías: modo `smtp` (recomendado), `mail` o `archivo` (pruebas). En *Configuración → Correo* hay un botón de **correo de prueba**.
- **Política de privacidad 1.1:** habla del correo y del enlace del PIN; todos deben aceptarla de nuevo.
- **PDF del carnet** (una hoja, con fecha y estado, protegido con el número de documento) en «Mi carnet» y en la ficha de cada persona.

### Configurar el correo (config.php → `correo` y `sitio_url`)
1. Pon en `sitio_url` **solo el dominio** del sitio (ej.: `https://midominio.com`; en XAMPP, `http://localhost`). La carpeta donde está instalado se agrega sola. Los enlaces del correo se arman con ella.
2. Elige el modo: **`smtp`** con el correo que trae tu hosting (cPanel → *Cuentas de correo*; suele ser servidor `mail.tudominio.com`, puerto 465 `ssl` o 587 `tls`) o con un servicio gratuito (Brevo, Gmail con «contraseña de aplicación»…). Con **`mail`** se usa la función del hosting.
3. Entra al panel → *Configuración* → *Correo* → escribe tu correo → **Enviar prueba**. Si llega (revisa «no deseado»), «Olvidé mi PIN» funcionará.
- Los hosting gratuitos suelen bloquear el envío de correos y también los formularios con archivos (error 403 «openresty»): para producción usa un hosting que no los bloquee.

### Actualizar desde la versión 8
1. Haz una copia de tu base y de tu carpeta.
2. Sube los archivos de esta versión sobre los del hosting, **excepto `config.php`**. Agrega a tu `config.php` las claves nuevas (`ficha_aviso_dias`, `sitio_url`, `correo`) copiándolas del `config.php` de esta versión; cambia `politica_version` a `'1.1'`.
3. En phpMyAdmin → pestaña SQL: ejecuta **una sola vez** `sql/actualizacion_v9.sql`.
4. Recarga con `Ctrl+F5`. Las personas que no tengan correo lo agregarán al entrar con su PIN.

## Novedades de la versión 8
Resumen (el detalle completo está en `CHANGELOG.md`):
- **Página pública `/verificar`** (sin usuario): vigilantes y empresas consultan por documento o con la **cámara** (código de barras) y ven documento, nombre completo y estado. Requiere https para la cámara.
- **Estado del aprendiz en su formación:** En formación, En etapa práctica, Retiro voluntario, Cancelado, Certificado. Los tres últimos desactivan el carnet solo. Se cambia por persona, **por ficha completa** o desde el Excel (columna nueva opcional).
- **Panel → Consultas públicas:** quién consultó qué y cuándo; aviso (sin bloqueo) si una IP consulta 100 o más documentos distintos en una hora (`verificar_alerta` en `config.php`).
- **Encabezado** sin el cuadrito «SENA · Regional Antioquia» y **pie de página** `© 2026 CTTAOA · Desarrollado por Yeiner Castillo · Ficha 3134228`.

### Direcciones del sistema
- Aprendices, instructores y funcionarios: `tu-dominio/`
- Administradores: `tu-dominio/<admin_prefix>/login` (la ruta secreta de `config.php`)
- Vigilantes y empresas: `tu-dominio/verificar`

### Actualizar desde la versión 7 (o anteriores)
1. Haz una copia de tus archivos y de la base de datos.
2. Sube los archivos de esta versión sobre los del hosting, **excepto `config.php`** (conserva el tuyo). Si quieres el aviso de consultas masivas, agrega en tu `config.php` la línea `'verificar_alerta' => 100,` y, si quieres, el pie nuevo en la clave `footer`.
3. **Base de datos:** en phpMyAdmin → pestaña SQL, ejecuta **una sola vez** el contenido de `sql/actualizacion_v8.sql`. (Alternativa: crear una base nueva e importar `sql/cttaoa.sql`; borra los datos.)
4. Recarga con `Ctrl+F5`. Activa **https** en tu hosting para que funcione la cámara.

## Novedades de la versión 7
Resumen (el detalle completo está en `CHANGELOG.md`):
- **Bloqueo solo por documento, sin IP:** 3 intentos por número de documento; el WiFi compartido del complejo ya no afecta a nadie.
- **Bloqueo escalonado:** 15 minutos, luego 1 hora y luego 24 horas si se sigue fallando. Un acierto o 24 horas sin errores reinician el contador; el administrador puede desbloquear en Configuración → Cuentas bloqueadas.
- **Mismo mensaje exista o no el documento**, con «Te quedan N intentos» (no se revela quién está registrado).
- **Alertas por bloqueos:** «N cuentas bloqueadas en 1 hora» (recomendado 5) y «cuenta con bloqueo de 24 horas». Ya no hay alertas por IP de los titulares.
- **Sin cambios en la base de datos.**

### Actualizar desde la versión 6 (o 5)
1. Haz una copia de tus archivos.
2. Sube los archivos de esta versión sobre los del hosting, **excepto `config.php`** (conserva el tuyo).
3. No toques la base de datos. Recarga con `Ctrl+F5`.
4. En el panel: **Configuración → «Avisar desde (cuentas bloqueadas en 1 hora)»** → escribe **5** y guarda.

## Novedades de la versión 6
- **Acceso al panel desde el carnet:** si quien entra a su carnet digital tiene una cuenta de administrador activa, ve el botón «Ir al panel de administración» (todo el panel si es principal; si es autorizado, solo verá lo que sus permisos permiten). Siempre pide usuario y contraseña del panel.
- **Datos obligatorios:** como todo aparece en el carnet, son obligatorios para cualquier tipo de persona: tipo y número de documento, nombres, apellidos, fecha de expedición, **tipo de sangre (RH)** y ficha/programa (aprendices) o área/cargo (instructores y funcionarios). Solo el correo es opcional; la foto se puede subir después pero es necesaria para activar.
- **Activar o desactivar toda una ficha de una vez:** en la ficha, junto a «Eliminar ficha completa».
- **Carnet adaptable:** su alto se ajusta al contenido.
- **Menú lateral:** se quitó «Mi carnet» (el administrador crea su carnet en *Mi cuenta* y lo ve/activa en *Personas*).

## Novedades de la versión 5
- **Mi cuenta** (administradores): datos personales, cambio de contraseña, información del acceso (solo consulta), actividad reciente y cerrar sesión.
- **Usuarios y permisos:** un administrador principal puede quitar el poder total o el acceso a otro principal (nunca a sí mismo).
- **Pie de página** en todas las pantallas (se cambia en `config.php`, clave `footer`).
- **Inicio con gráficas:** ingresos de titulares, activación de PIN, carnets por estado y personas por tipo.
- **Modo oscuro** solo en el panel de administración (botón arriba a la derecha).
- **Historial** con borrado automático (por defecto 7 días; configurable).
- **Copia de seguridad automática** cada 24 horas en `storage/backups/copia_cttaoa.sql` (siempre queda solo la más reciente). Se puede crear y descargar desde Configuración. No incluye las fotos.
- **Cuentas bloqueadas:** lista con cuenta regresiva y botón Desbloquear; opción «desbloqueo solo por un administrador».
- **Alertas de actividad sospechosa** (Configuración e Inicio).
- **Capturas de pantalla:** una web no puede bloquearlas del todo; el sistema oculta el carnet al salir de la pantalla, avisa al detectar el atajo de captura y pone una marca de agua con usuario/documento y hora.
- Botones sin flechas; menú «Personas» (con filtros por tipo); el carnet reparte mejor el espacio.
