<?php
$titulo = 'Política de privacidad y términos';
$ver = cfg('politica_version', '1.0'); $fecha = cfg('politica_fecha', ''); $borr = cfg('politica_borrador', true);
$resp = cfg('responsable_nombre', 'Servicio Nacional de Aprendizaje – SENA · Regional Antioquia · CTTAOA');
$dir = cfg('responsable_direccion', ''); $mail = cfg('responsable_correo', ''); $tel = cfg('responsable_telefono', '');
$secs = [['resumen', 'Resumen'], ['responsable', 'Responsable del tratamiento'], ['marco-legal', 'Marco legal'], ['datos', 'Datos que recolectamos'], ['finalidades', 'Para qué usamos tus datos'],
         ['sensibles-menores', 'Datos sensibles y menores de edad'], ['autorizacion', 'Tu autorización'], ['derechos', 'Tus derechos'], ['ejercicio', 'Cómo ejercer tus derechos'],
         ['terceros', 'Terceros y proveedores'], ['seguridad', 'Seguridad de la información'], ['conservacion', 'Conservación de los datos'], ['cookies', 'Cookies y almacenamiento'],
         ['terminos', 'Términos y condiciones de uso'], ['cambios', 'Cambios y versiones'], ['enlaces', 'Enlaces oficiales']];
?>
<section class="legal-hero">
 
 <h1 class="h2 fw-bold my-2">Política de privacidad y términos y condiciones</h1>
 <p class="mb-0">Cómo se protegen y para qué se usan tus datos en el carnet digital. Versión <?= e($ver) ?><?= $fecha !== '' ? ' · ' . e($fecha) : '' ?>.</p>
</section>
<?php if ($borr): ?>
<div class="alert alert-warning" role="alert"><strong>Documento base.</strong> Este texto debe ser revisado y aprobado por el SENA (área jurídica y responsable de protección de datos personales) antes de usarse con datos reales.</div>
<?php endif; ?>

<div class="row g-4">
 <div class="col-lg-3">
  <details class="legal-movil d-lg-none card card-body mb-1"><summary class="fw-semibold">Contenido de esta página</summary>
   <nav class="mt-2" aria-label="Contenido"><?php foreach ($secs as $s): ?><a class="d-block py-1" href="#<?= e($s[0]) ?>"><?= e($s[1]) ?></a><?php endforeach; ?></nav></details>
  <nav class="legal-nav d-none d-lg-block" aria-label="Contenido"><div class="small text-muted px-2 mb-1 text-uppercase">Contenido</div>
   <?php foreach ($secs as $s): ?><a href="#<?= e($s[0]) ?>" data-sec="<?= e($s[0]) ?>"><?= e($s[1]) ?></a><?php endforeach; ?></nav>
 </div>

 <div class="col-lg-9">
  <section class="legal-sec" id="resumen"><h2>1. Resumen</h2>
   <ul>
    <li>Usamos tus datos para <strong>elaborar y verificar tu carnet institucional</strong> y apoyar la gestión académica y administrativa del complejo (matrícula y control de la formación).</li>
    <li>No vendemos ni compartimos tus datos con fines comerciales ni publicitarios.</li>
    <li>Tu <strong>PIN</strong> es personal y se guarda cifrado: nadie, ni el administrador, lo conoce.</li>
    <li>Tienes derecho a <strong>conocer, actualizar y corregir</strong> tus datos y a presentar reclamos (ver «Tus derechos»).</li>
    <li>Al marcar «Acepto» autorizas este tratamiento. Guardamos la fecha, la versión y la dirección IP como prueba.</li>
   </ul></section>

  <section class="legal-sec" id="responsable"><h2>2. Responsable del tratamiento</h2>
   <p>El responsable del tratamiento de los datos personales de aprendices, instructores, funcionarios y contratistas es el <strong><?= e($resp) ?></strong>.</p>
   <ul>
    <?php if ($dir !== ''): ?><li><strong>Dirección:</strong> <?= e($dir) ?>.</li><?php endif; ?>
    <?php if ($mail !== ''): ?><li><strong>Correo para consultas y reclamos:</strong> <?= e($mail) ?>.</li><?php endif; ?>
    <?php if ($tel !== ''): ?><li><strong>Teléfono:</strong> <?= e($tel) ?>.</li><?php endif; ?>
    <li><strong>Canal institucional:</strong> servicio al ciudadano y PQRS en <a href="http://www.sena.edu.co/servicioalciudadano/PQRS" target="_blank" rel="noopener noreferrer">www.sena.edu.co</a>.<?= $mail === '' ? ' Mientras no se publique un correo específico, puedes dirigirte al administrador del complejo.' : '' ?></li>
   </ul>
   <p class="mb-0">El sistema de carnet digital fue desarrollado por Yeiner Castillo, aprendiz del SENA (Ficha 3134228), como propuesta para el complejo.</p></section>

  <section class="legal-sec" id="marco-legal"><h2>3. Marco legal</h2>
   <p>El tratamiento de datos personales se rige, entre otras, por estas normas (y las que las modifiquen o sustituyan):</p>
   <ul>
    <li><strong>Constitución Política de Colombia</strong>, artículos 15 (intimidad y <em>habeas data</em>) y 20.</li>
    <li><strong>Ley 1581 de 2012</strong>: régimen general de protección de datos personales.</li>
    <li><strong>Decreto 1377 de 2013</strong>, compilado en el <strong>Decreto 1074 de 2015</strong>, que reglamenta parcialmente la Ley 1581.</li>
    <li><strong>Ley 1098 de 2006</strong> (Código de la Infancia y la Adolescencia): interés superior de niños, niñas y adolescentes.</li>
    <li><strong>Ley 119 de 1994</strong>: reestructuración del SENA y definición de sus funciones.</li>
    <li><strong>Acuerdo 13 de 2020</strong> del Consejo Directivo Nacional del SENA: Política General de Seguridad de la Información y Protección de Datos Personales.</li>
   </ul>
   <p class="mb-0">Se aplican los principios de legalidad, finalidad, libertad, veracidad, transparencia, acceso y circulación restringida, seguridad y confidencialidad.</p></section>

  <section class="legal-sec" id="datos"><h2>4. Datos que recolectamos</h2>
   <ul>
    <li><strong>Identificación:</strong> tipo y número de documento, nombres, apellidos, fecha de expedición del documento y correo electrónico (obligatorio).</li>
    <li><strong>Correo electrónico:</strong> se usa para enviarte, cuando olvidas tu PIN, un enlace seguro y de un solo uso para crear uno nuevo, y para avisos del servicio. No se usa para publicidad.</li>
    <li><strong>Vínculo con el complejo:</strong> tipo de persona (aprendiz, instructor, funcionario o contratista), número de ficha y programa de formación, o área, dependencia o cargo.</li>
    <li><strong>Imagen:</strong> fotografía para el carnet.</li>
    <li><strong>Dato sensible opcional:</strong> tipo de sangre (RH). Solo se muestra en el carnet cuando el complejo lo ha verificado.</li>
    <li><strong>Datos de uso y seguridad:</strong> fecha y hora de ingresos, dirección IP, intentos fallidos, estado del carnet y registro de tu aceptación (fecha, versión e IP). El PIN se guarda cifrado, nunca en texto; los enlaces de recuperación de PIN se guardan solo como huella cifrada, vencen y se borran a los pocos días.</li>
    <li><strong>Código de barras:</strong> el carnet incluye un código con tu número de documento.</li>
   </ul></section>

  <section class="legal-sec" id="finalidades"><h2>5. Para qué usamos tus datos</h2>
   <ol>
    <li>Elaborar, mantener y mostrar tu carnet institucional digital.</li>
    <li>Identificarte y verificar si tu carnet está activo, inactivo o pendiente, y permitirte recuperar tu PIN por correo.</li>
    <li>Apoyar la gestión académica y administrativa del complejo (matrícula, control de la formación y registros de la ficha).</li>
    <li>Proteger el sistema: control de acceso, prevención de fraudes y suplantación, y auditoría de las acciones.</li>
    <li>Generar estadísticas internas que no identifican personas (por ejemplo, cuántos carnets están activos).</li>
    <li>Atender consultas, reclamos y solicitudes.</li>
    <li>Cumplir obligaciones legales y requerimientos de las autoridades.</li>
   </ol>
   <p class="mb-0">Tus datos no se usan para publicidad ni se venden a terceros.</p></section>

  <section class="legal-sec" id="sensibles-menores"><h2>6. Datos sensibles y menores de edad</h2>
   <p><strong>Datos sensibles.</strong> El tipo de sangre se relaciona con la salud y se considera dato sensible. Suministrarlo es <strong>facultativo</strong>: no estás obligado a darlo y el complejo no condiciona ningún trámite a ello.</p>
   <p class="mb-0"><strong>Menores de edad.</strong> Cuando el titular tiene menos de 18 años, el tratamiento respeta el interés superior del menor y sus derechos fundamentales. El complejo gestionará la autorización del padre, madre o representante legal según los formatos y procedimientos del SENA.</p></section>

  <section class="legal-sec" id="autorizacion"><h2>7. Tu autorización</h2>
   <p>Al marcar la casilla <em>«Acepto los términos y condiciones y la política de privacidad…»</em> en la primera vez que ingresas, manifiestas de forma <strong>previa, expresa e informada</strong> que conoces este documento y autorizas el tratamiento descrito.</p>
   <ul>
    <li>Guardamos como prueba la fecha, hora, versión del documento y dirección IP de tu aceptación.</li>
    <li>Si no aceptas, no podrás activar tu carnet digital; consulta con el complejo las alternativas disponibles.</li>
    <li>Puedes <strong>revocar</strong> tu autorización cuando no exista un deber legal o contractual que lo impida (ver «Cómo ejercer tus derechos»).</li>
    <li>Cuando la ley lo permite, el SENA también trata datos en ejercicio de sus funciones legales, sin que se requiera una autorización adicional.</li>
   </ul></section>

  <section class="legal-sec" id="derechos"><h2>8. Tus derechos</h2>
   <p>Como titular de los datos tienes derecho a:</p>
   <ol type="a">
    <li>Conocer, actualizar y rectificar tus datos personales.</li>
    <li>Solicitar prueba de la autorización que diste.</li>
    <li>Ser informado, si lo pides, del uso que se ha dado a tus datos.</li>
    <li>Presentar quejas ante la <strong>Superintendencia de Industria y Comercio</strong> por infracciones a la ley.</li>
    <li>Revocar la autorización y/o solicitar la supresión de tus datos cuando no se respeten los principios, derechos y garantías legales (no procede si tienes un deber legal o contractual de permanecer en la base de datos).</li>
    <li>Acceder de forma gratuita a tus datos personales.</li>
   </ol></section>

  <section class="legal-sec" id="ejercicio"><h2>9. Cómo ejercer tus derechos</h2>
   <p>Puedes presentar una <strong>consulta</strong> o un <strong>reclamo</strong> al administrador del complejo<?= $mail !== '' ? ', al correo ' . e($mail) : '' ?> o por los canales de servicio al ciudadano del SENA. Indica tu nombre, documento y lo que solicitas.</p>
   <ul>
    <li><strong>Consultas:</strong> se responden en un máximo de 10 días hábiles (prorrogables por 5 días hábiles más, informando los motivos).</li>
    <li><strong>Reclamos</strong> (corrección, actualización, supresión o revocatoria): se resuelven en un máximo de 15 días hábiles (prorrogables por 8 días hábiles más).</li>
   </ul>
   <p class="mb-0">Si no estás conforme con la respuesta, puedes acudir a la Superintendencia de Industria y Comercio.</p></section>

  <section class="legal-sec" id="terceros"><h2>10. Terceros y proveedores</h2>
   <ul>
    <li>No vendemos ni cedemos tus datos.</li>
    <li>Acceden a ellos el personal autorizado del complejo según su rol, el SENA como responsable, y las autoridades cuando la ley lo exija.</li>
    <li>Para enviar el correo de recuperación del PIN se usa el <strong>servicio de correo</strong> que el complejo configure (su servidor de correo o un proveedor externo); ese servicio recibe tu correo únicamente para entregar el mensaje.</li>
    <li>El sistema usa un proveedor de <strong>alojamiento web</strong> que almacena la información. Puede estar ubicado fuera de Colombia; el complejo verificará que ofrezca condiciones adecuadas de seguridad.</li>
    <li>Para mostrar la interfaz se cargan recursos de un servicio de distribución de contenido (<strong>jsDelivr</strong>), que puede ver tu dirección IP y tu navegador.</li>
   </ul></section>

  <section class="legal-sec" id="seguridad"><h2>11. Seguridad de la información</h2>
   <ul>
    <li>El PIN y las contraseñas se guardan cifrados; el administrador nunca conoce tu PIN.</li>
    <li>Acceso por roles y permisos, con historial de acciones y alertas de actividad sospechosa.</li>
    <li>Bloqueo temporal tras varios intentos fallidos y cierre automático de la sesión por inactividad.</li>
    <li>Copias de seguridad periódicas y conexión protegida (HTTPS) en el servidor.</li>
    <li>El carnet se oculta al salir de la pantalla y se avisa al detectar un intento de captura; sin embargo, una página web <strong>no puede impedir por completo</strong> las capturas ni las fotografías externas.</li>
   </ul>
   <p class="mb-0">Ninguna medida es infalible. Protege tu PIN y no lo compartas con nadie.</p></section>

  <section class="legal-sec" id="conservacion"><h2>12. Conservación de los datos</h2>
   <p class="mb-0">Los datos se conservan mientras exista tu vínculo con el complejo y durante el tiempo que exijan las normas y las tablas de retención documental del SENA. Después se suprimen o se archivan de forma segura.</p></section>

  <section class="legal-sec" id="cookies"><h2>13. Cookies y almacenamiento</h2>
   <ul>
    <li><strong>Cookie de sesión</strong> (necesaria): mantiene tu sesión abierta; se borra al cerrar el navegador.</li>
    <li><strong>Cookie del dispositivo:</strong> recuerda quién eres para pedirte solo el PIN (hasta 180 días). Se elimina con «Consultar otro documento».</li>
    <li>El panel de administración guarda en el navegador la preferencia de modo claro u oscuro.</li>
    <li>No usamos cookies publicitarias ni de seguimiento.</li>
   </ul></section>

  <section class="legal-sec" id="terminos"><h2>14. Términos y condiciones de uso</h2>
   <ol>
    <li><strong>Objeto.</strong> El carnet digital es una herramienta del complejo para identificar a aprendices, instructores, funcionarios y contratistas y verificar la vigencia del carnet. No reemplaza el documento de identidad ni otros documentos oficiales.</li>
    <li><strong>Uso personal.</strong> El carnet y el PIN son personales e intransferibles. Eres responsable del uso que se haga con tu PIN.</li>
    <li><strong>Veracidad.</strong> Los datos deben ser exactos. Avisa al administrador si encuentras errores.</li>
    <li><strong>Uso prohibido.</strong> No compartas tu PIN, no suplantes a otra persona, no intentes entrar a cuentas ajenas ni vulnerar la seguridad del sistema, y no fotografíes ni difundas carnets de otras personas.</li>
    <li><strong>Vigencia del carnet.</strong> La determina el sistema en el momento de la consulta: un carnet con la marca BORRADOR aún no está activado y uno con la marca INACTIVO no es válido. Una captura de pantalla no prueba que el carnet esté vigente.</li>
    <li><strong>Disponibilidad.</strong> El servicio puede tener interrupciones por mantenimiento o fallas técnicas.</li>
    <li><strong>Suspensión.</strong> El complejo puede desactivar el carnet o bloquear el acceso por razones de seguridad, uso indebido o terminación del vínculo.</li>
    <li><strong>Propiedad intelectual.</strong> El nombre, los logotipos y los signos distintivos del SENA pertenecen a la entidad.</li>
    <li><strong>Ley aplicable.</strong> Estos términos se rigen por las leyes de la República de Colombia.</li>
   </ol></section>

  <section class="legal-sec" id="cambios"><h2>15. Cambios y versiones</h2>
   <p class="mb-0">Este documento puede actualizarse. Cada versión tiene un número y una fecha. Cuando haya cambios importantes se te pedirá aceptar de nuevo antes de ver tu carnet. <strong>Versión vigente: <?= e($ver) ?><?= $fecha !== '' ? ' · ' . e($fecha) : '' ?>.</strong></p></section>

  <section class="legal-sec" id="enlaces"><h2>16. Enlaces oficiales</h2>
   <ul class="mb-0">
    <li><a href="https://www.sena.edu.co" target="_blank" rel="noopener noreferrer">SENA</a>: política de tratamiento de datos personales y servicio al ciudadano (PQRS).</li>
    <li><a href="https://www.sic.gov.co" target="_blank" rel="noopener noreferrer">Superintendencia de Industria y Comercio</a>: autoridad de protección de datos personales.</li>
    <li><a href="http://www.secretariasenado.gov.co/senado/basedoc/ley_1581_2012.html" target="_blank" rel="noopener noreferrer">Texto de la Ley 1581 de 2012</a>.</li>
   </ul></section>
 </div>
</div>
