# Historial de cambios · CTTAOA Carnet digital

## Versión 9.1 (octubre de 2026)
**Sin cambios en la base de datos** (si ya tienes la versión 9, no importes nada).
1. **Quitar el fondo de las fotos en el navegador** (MediaPipe Selfie Segmentation, Apache 2.0, incluido en `assets/vendor/selfie_segmentation/`): botón «Quitar fondo (queda blanco)» con vista previa y «Usar la original». Gratis, sin enviar la foto a ningún servicio y sin internet. Disponible al crear o editar una persona, en su ficha, al crear un administrador y en «Mi cuenta»; y en **Fotografías masivas** con «Quitar el fondo de todas las fotos» (una por una, con barra de avance, cancelable, conserva el nombre = número de documento y «Usar las originales»). Probado con una fotografía real (el fondo queda blanco, la persona se conserva) y con el camino de fallo (si el modelo no carga, se conserva la foto original).
2. `sitio_url`: ahora solo cuenta el dominio; la carpeta de instalación se agrega sola (los enlaces del correo ya no repiten la carpeta).
3. La política de seguridad (CSP) permite WebAssembly (`'wasm-unsafe-eval'`); `.htaccess` declara el tipo `.wasm`.
4. Texto de licencias de terceros en el README (Bootstrap, ZXing, MediaPipe).

## Versión 9 (octubre de 2026)
**Cambia la base de datos:** instalación nueva → `sql/cttaoa.sql` (15 tablas); desde la versión 8 → `sql/actualizacion_v9.sql` una sola vez (tipo «contratista», fechas de la ficha y la tabla `restablecer_pin`; no borra datos).

### Cambios
1. «Carnet» (sin tilde) en todo el sistema, el PDF y la plantilla de Excel.
2. Carnet: nombres arriba y apellidos abajo con mayúscula inicial (también al guardar); programa, ficha y complejo en verde. Igual en el PDF.
3. Fotografías de máximo 3 MB.
4. Nuevo tipo de persona **contratista** (cargo o área): formularios, Excel, gráficas, verificación y PDF.
5. Administradores: se asignan solo desde «Usuarios y permisos» buscando por cédula; se quitó el botón de la ficha de cada persona.
6. Botones «Volver» en las pantallas internas.
7. **Fichas con fecha de inicio y fin:** aviso 5 días antes (inicio del panel y lista de fichas, en amarillo), en rojo al terminar; al terminar, todos sus carnets pasan a inactivo (una sola vez, queda en el historial) hasta que un administrador los reactive o elimine la ficha. Las fechas se editan en la ficha o vienen en las columnas M y N del Excel (opcionales).
8. **PIN por correo:** correo obligatorio y único (al crear el PIN, y en el formulario del administrador); quien ya tenía PIN y no tenía correo lo agrega al entrar. «Olvidé mi PIN» envía un enlace de un solo uso (guardado solo como huella, vence) y desbloquea el documento al usarse. El administrador ya no genera códigos (se quitó la tabla de códigos de recuperación de la instalación nueva).
9. Envío de correos propio (sin librerías): SMTP con STARTTLS o SSL, `mail()` o modo «archivo»; botón de correo de prueba en Configuración.
10. Política de privacidad 1.1 (correo, enlace del PIN, contratistas): todos la aceptan de nuevo.
11. Formularios con archivo que se envían sin elegir ningún archivo viajan como formularios normales (ayuda en hosting que bloquean los envíos «multipart»).

### Correcciones
- La fecha de fin de una ficha (futura) se rechazaba como «fuera de rango»; ahora se admite (la fecha de expedición sigue sin poder ser futura).

### Pruebas
998 comprobaciones automáticas (PHP 8.3 + MariaDB): 896 de las versiones anteriores (actualizadas a los cambios de diseño) y 102 nuevas, entre ellas el envío SMTP contra un servidor de prueba y la actualización de una base de la versión 8 con datos.

---
## Versión 8 (octubre de 2026)
**Cambia la base de datos** (una columna y una tabla nuevas): para una instalación existente ejecuta `sql/actualizacion_v8.sql` una sola vez en phpMyAdmin; para empezar de cero importa `sql/cttaoa.sql` en una base vacía.

### Página pública de verificación: `/verificar`
1. Página **sin usuario ni contraseña** para vigilantes y empresas (ejemplo: `cttaoacarnet.unaux.com/verificar`). Se consulta **escribiendo el documento** o **escaneando el código de barras con la cámara**.
2. Muestra solo **documento, nombre completo y estado** (sin foto ni otros datos): **ACTIVO** (verde) con «En formación», «En etapa práctica», «Instructor» o «Funcionario»; **NO VIGENTE**, **RETIRO VOLUNTARIO**, **CANCELADO** o **CERTIFICADO (EGRESADO)** (rojo); **NO REGISTRADO** (gris). Indica la fecha de la consulta y que no reemplaza los registros oficiales del SENA.
3. **Cámara:** usa el lector nativo del navegador (Chrome/Android) y, si no existe (iPhone/Safari), la librería ZXing 0.21.3 (cargada desde jsDelivr). Requiere **https**. No se garantiza en todos los iPhone: si falla, se escribe el documento.
4. Cada consulta queda registrada (documento, resultado, IP y fecha) y se ve en el panel → **Consultas públicas** (permiso «Consultar historiales»). Se conservan 90 días.
5. **Alerta (solo aviso, sin bloquear):** si una misma IP consulta 100 o más documentos distintos en una hora (clave `verificar_alerta` en `config.php`; 0 la desactiva).
6. La cámara se permite únicamente en esta página (`Permissions-Policy`); el resto del sitio la sigue bloqueando.

### Estado del aprendiz en su formación
1. Nuevo dato solo para **aprendices**: **En formación**, **En etapa práctica**, **Retiro voluntario**, **Cancelado** o **Certificado (egresado)** (por defecto, «En formación»; los aprendices ya existentes se toman como «En formación»).
2. **Retiro voluntario, cancelado o certificado desactivan el carnet solo** y no permiten activarlo (ni individualmente ni con «Activar carnets completos»). Al volver a un estado vigente el carnet **no se reactiva solo**.
3. Se edita en el formulario de la persona, **por ficha completa** (selector «Estado en la formación de toda la ficha» + «Aplicar a toda la ficha», permiso «Crear y editar registros») y desde el **Excel**.
4. Aparece en la tabla de la ficha (columna «Formación»), en la lista de personas, en la ficha de la persona, en «Mis datos» del titular y en la API `/api/mi-carne`. Todo queda en el historial.

### Excel
- La plantilla trae una columna nueva **opcional «Estado de formación (aprendices)»** (lista desplegable) y las instrucciones la explican. Vacía = «En formación». Acepta con o sin tilde y mayúsculas («Etapa práctica», «etapa practica»…). Un valor desconocido es un error de esa fila; en instructores y funcionarios se ignora con un aviso.
- Importar «Retiro voluntario», «Cancelado» o «Certificado» a alguien con carnet activo lo desactiva.

### Otros cambios
- **Página principal:** se quitó el cuadrito «SENA · Regional Antioquia»; queda «Tu carnet digital, siempre a la mano» y la descripción.
- **Pie de página:** `© 2026 CTTAOA · Desarrollado por Yeiner Castillo · Ficha 3134228`.
- Los bloqueos de la versión 7 siguen igual: **solo por documento, 3 intentos, escalonado, sin IP** (la página `/verificar` tampoco bloquea por IP; solo avisa).

### Pruebas
848 comprobaciones automáticas (PHP 8.3 + MariaDB), 77 nuevas. La lectura con la cámara se probó con un video simulado del código de barras en Chromium (ruta ZXing y ruta del lector nativo simulada); no se pudo probar en un iPhone real.

---
## Versión 7 (octubre de 2026)
**Sin cambios en la base de datos.** Si tienes la base de la versión 5 o 6, no importes nada.

### Cambios en los bloqueos de la consulta pública (aprendices, instructores y funcionarios)
1. **El bloqueo es solo por número de documento. Ya no se bloquea por IP.** Como en el WiFi del complejo muchas personas comparten la misma IP, el límite por IP podía bloquear a todos o generar falsas alertas. Se eliminó (también la clave `titular_ip_max_intentos` de `config.php`; si la dejas en tu archivo no pasa nada).
2. **3 intentos por documento** (configurable de 1 a 10 en Configuración → «Intentos permitidos por documento»). Cuentan juntos los fallos de «Tengo PIN», «Primera vez» y «Olvidé PIN».
3. **Bloqueo escalonado:** el primer bloqueo dura 15 minutos (configurable); si al volver a intentar se vuelve a fallar, el segundo bloqueo seguido dura **1 hora** y el tercero **24 horas**.
   - Los fallos **no se reinician** cuando vence un bloqueo.
   - Se reinician con un acierto (PIN correcto), cuando un administrador desbloquea, o si pasan 24 horas sin ningún fallo.
   - Con «Desbloqueo solo por un administrador» activo, el primer bloqueo ya es manual (como antes).
   - La lista **Cuentas bloqueadas** indica el nivel («bloqueo n.º 2»).
4. **Mensajes iguales exista o no el documento.** Cualquier fallo muestra *«Los datos no coinciden con el registro…»* seguido de *«Te quedan N intentos antes de un bloqueo temporal»*, también para documentos que no existen (así no se revela quién está registrado). Los documentos inexistentes se bloquean igual que los existentes. Un documento vacío no crea contadores.
5. **Alertas basadas en bloqueos, no en IP:**
   - «N cuentas de titulares se bloquearon en la última hora» (N configurable en Configuración → «Avisar desde (cuentas bloqueadas en 1 hora)»: de 1 a 20, **recomendado 5**).
   - «N cuenta(s) llegaron al bloqueo de 24 horas».
   - Se mantienen: bloqueos de las últimas 24 horas, varios fallos contra una cuenta de administrador e ingreso de un administrador desde una IP nueva.
   - **Después de actualizar, entra a Configuración y pon «Avisar desde» en 5** (antes era un número de intentos por IP y podría estar en 10 o 100).
6. Los contadores sin bloqueo se borran solos a los 2 días (antes, 30).
7. Los mensajes de bloqueo muestran la duración en horas cuando corresponde («Intenta de nuevo en 24 horas»).

### Sin cambios
- El **panel de administración** conserva sus bloqueos por usuario+IP (5 fallos) y por IP (20), porque los administradores son pocos y no comparten el problema del WiFi.

### Pruebas
771 comprobaciones automáticas (PHP 8.3 + MariaDB), con 37 pruebas nuevas del bloqueo escalonado, los mensajes iguales y la ausencia de bloqueo por IP.

---
## Versión 6 (octubre de 2026)
**Sin cambios en la base de datos.** Si tienes la base de la versión 5, no necesitas importar nada.

### Nuevo
1. **Acceso al panel desde el carnet digital.**
   Cuando una persona entra a su carnet con su PIN y está vinculada a una cuenta de administrador **activa**, aparece:
   - un botón «Panel de administración» en la barra superior, y
   - una tarjeta «Acceso de administración» bajo el carnet, que explica su rol: *administrador principal* (todas las funciones) o *administrador autorizado* (con la lista de lo que puede hacer).
   El botón lleva al panel, que **siempre pide usuario y contraseña**: el PIN de 4 dígitos no da acceso al panel. Dentro, cada administrador ve solo lo que sus permisos permiten (como antes). Quien no es administrador no ve nada de esto.
2. **Activar o desactivar una ficha completa en un solo paso.**
   En la página de la ficha, junto a «Eliminar ficha completa»:
   - **Activar carnets completos (N):** activa todos los carnets que tengan todos sus datos y foto. Los incompletos no se tocan y se informa cuántos quedaron pendientes. Si hay carnets desactivados completos, aparece la casilla «incluir los N desactivados».
   - **Desactivar todos los activos (N):** desactiva los carnets activos de la ficha.
   - Ambos piden confirmación, muestran las cantidades, requieren el permiso «Activar y desactivar carnets» y quedan en el historial (`activar_ficha`, `desactivar_ficha`).
   - La ficha muestra un resumen: integrantes, activos, completos listos para activar e incompletos.

### Cambios
3. **Datos obligatorios para todos los tipos de persona** (aprendiz, instructor, funcionario, administrador), porque aparecen en el carnet:
   tipo de documento, número de documento, nombres, apellidos, **fecha de expedición**, **tipo de sangre (RH)** y, según el tipo, ficha y programa (aprendices) o área/cargo (instructores y funcionarios). Solo el **correo** sigue siendo opcional.
   - Se aplica en: formulario de persona, creación de administrador con persona nueva, «Mi cuenta» (crear y editar tu carnet) e **importación de Excel** (antes eran avisos; ahora son errores por fila).
   - El **RH** se pidió también en los formularios de administrador. Ya no existe la casilla «RH verificado»: como el dato es obligatorio, siempre se imprime en el carnet.
   - Para **activar** un carnet ahora también deben estar completos el RH y la fecha de expedición (además de nombres, apellidos, documento, foto y ficha/área).
   - La **foto** se puede subir después de crear a la persona (es necesario para la importación masiva), pero sigue siendo obligatoria para activar el carnet.
   - Efecto en datos existentes: una persona sin RH o sin fecha de expedición aparece como «datos incompletos» (el carnet se ve como borrador) hasta que se complete el dato editándola. El sistema lo marca solo (automático, como máximo una hora después de la primera visita al panel) para que salgan en el filtro «Datos incompletos» y en el indicador del inicio.
   - La **plantilla de Excel** se actualizó: columnas obligatorias marcadas en verde oscuro e instrucciones nuevas. Descarga la nueva plantilla desde *Importar Excel*.
4. **El carnet se adapta al contenido.** Ya no tiene un alto fijo: los espacios entre la raya y el nombre, y entre el código de barras y «Regional Antioquia», son constantes y pequeños. Con un solo nombre o sin línea de ficha el carnet se ve más compacto, y con nombres largos crece. El reverso siempre tiene el mismo alto que el frente.
5. **Menú lateral del panel:** se quitó «Mi carnet». El administrador sin carnet lo crea en *Mi cuenta* («completa tus datos»); lo puede ver, activar o desactivar en *Personas*, y entrar desde la página principal con su PIN. (La pantalla «Mi carnet» sigue disponible desde *Mi cuenta* → «Ver mi carnet», útil para administradores sin permiso de consulta.)

### Corregido
6. **WiFi compartido del complejo.** Los contadores de intentos fallidos **por IP** guardaban errores sin límite de tiempo: en una red donde todos comparten IP, los errores de muchos días se sumaban hasta bloquear a todos durante 15 minutos. Ahora esos contadores se reinician si pasa más de **1 hora** sin nuevos errores. (Los contadores por cuenta no cambian.)
   *(En la versión 7 el bloqueo por IP de los titulares se eliminó por completo: ver arriba.)*

### Pruebas
Más de 640 comprobaciones automáticas (PHP 8.3 + MariaDB), adaptadas a los nuevos datos obligatorios, más pruebas nuevas para el acceso al panel, la activación por ficha y el carnet adaptable.

---

## Versión 5 (octubre de 2026)
- **Mi cuenta** del administrador: datos personales, contraseña, información del acceso, actividad reciente, cerrar sesión; y crear su propio carnet.
- **Usuarios y permisos:** un principal puede quitar el poder o el acceso a otro principal (nunca a sí mismo).
- **Inicio con gráficas**, **modo oscuro** del panel, pie de página con créditos.
- **Historial** con borrado automático; **copia de seguridad** automática cada 24 h; **cuentas bloqueadas** con cuenta regresiva y desbloqueo; **alertas** de actividad sospechosa (con «Revisar»).
- **Política de privacidad y términos** (`/privacidad`) con casilla de aceptación obligatoria en «Primera vez», registro de la aceptación (fecha, versión, IP) y nueva aceptación si cambia la versión.
- Carnet con sello **INACTIVO**; «Mis datos» cerrado por defecto; aviso de capturas (sin marca de agua).
- Botones sin flechas; menú «Personas».

## Versión 4
Reescritura completa a **PHP puro + MySQL/MariaDB** para hosting compartido (sin Python). Panel de administración, importación de Excel, fotos masivas, plantilla, flujo del titular (PIN, recuperación, cierre por inactividad), código de barras Code128, historial.

## Versiones 1 a 3
Primeras versiones en Django + MySQL (XAMPP): modelos, vistas, seguridad, importación, carnet digital y panel.
