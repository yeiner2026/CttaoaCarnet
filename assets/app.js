(function(){
  document.querySelectorAll('.escena').forEach(function(esc){
    var carne=esc.querySelector('.carne'), x0=null, movido=false;
    function girar(){carne.classList.toggle('volteado');esc.setAttribute('aria-pressed',carne.classList.contains('volteado'));}
    esc.addEventListener('click',function(){ if(!movido) girar(); movido=false; });
    esc.addEventListener('keydown',function(e){ if(e.key==='Enter'||e.key===' '){e.preventDefault();girar();} });
    esc.addEventListener('pointerdown',function(e){x0=e.clientX;movido=false;});
    esc.addEventListener('pointerup',function(e){
      if(x0!==null && Math.abs(e.clientX-x0)>40){movido=true;girar();}
      x0=null;
    });
  });
  var f=document.querySelectorAll('form[data-confirmar]');
  f.forEach(function(el){el.addEventListener('submit',function(e){if(!confirm(el.dataset.confirmar))e.preventDefault();});});
  document.querySelectorAll('form[data-carga]').forEach(function(el){el.addEventListener('submit',function(){
    var b=el.querySelector('button[type=submit]:not([formnovalidate])'); if(b){b.disabled=true;b.textContent='Procesando…';}});});
  // Medidas complementarias (no impiden capturas): ocultar el carnet al pasar a segundo plano o perder el foco.
  var zona=document.getElementById('zona-carne');
  if(zona){
    var ocultar=function(){zona.classList.add('oculto');var c=zona.querySelector('.carne');if(c)c.classList.remove('volteado');};
    document.addEventListener('visibilitychange',function(){if(document.hidden)ocultar();});
    window.addEventListener('pagehide',ocultar);
    window.addEventListener('blur',ocultar);
    var b=document.getElementById('btn-mostrar');
    if(b)b.addEventListener('click',function(){zona.classList.remove('oculto');});
    zona.addEventListener('contextmenu',function(e){e.preventDefault();});
  }
  // Vista previa del logotipo/firma antes de guardar
  document.querySelectorAll('input[type=file][data-preview]').forEach(function(inp){
    inp.addEventListener('change',function(){
      var img=document.getElementById(inp.dataset.preview); if(!img||!inp.files[0])return;
      img.src=URL.createObjectURL(inp.files[0]);
    });
  });
})();

// Notificaciones: desaparecen solas a los 3 segundos (las fijas, como el código de recuperación, tienen botón ×).
(function(){
  document.querySelectorAll('.alert[data-flash]').forEach(function(el){
    setTimeout(function(){
      el.style.transition='opacity .4s'; el.style.opacity='0';
      setTimeout(function(){ if(el.parentNode) el.parentNode.removeChild(el); },400);
    },3000);
  });
})();

// Cierre de sesión por inactividad (sin mover el mouse, tocar la pantalla ni teclear).
// Mientras hay actividad avisa al servidor (/ping) para que la sesión no venza. A los últimos segundos muestra un aviso.
(function(){
  var b = document.body, seg = parseInt(b.getAttribute('data-idle') || '0', 10);
  if (!seg) return;
  var ping = b.getAttribute('data-ping'), form = document.getElementById('form-salir');
  var ultimo = Date.now(), ultimoPing = Date.now(), aviso = null, cuenta = null;
  var margen = Math.min(30, Math.max(3, Math.floor(seg / 3)));
  function enviarPing(){ ultimoPing = Date.now(); try { fetch(ping, {credentials: 'same-origin', cache: 'no-store'}).then(function(r){ if (r.status === 401) salir(); }); } catch (e) {} }
  function actividad(){
    if (aviso) return;                         // con el aviso visible hay que pulsar el botón
    ultimo = Date.now();
    if (Date.now() - ultimoPing > Math.min(seg * 1000 / 3, 120000)) enviarPing();
  }
  ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click'].forEach(function(ev){ document.addEventListener(ev, actividad, {passive: true}); });
  function salir(){
    if (!form) { location.reload(); return; }
    var m = document.createElement('input'); m.type = 'hidden'; m.name = 'motivo'; m.value = 'inactividad'; form.appendChild(m);
    form.submit();
  }
  function mostrar(){
    aviso = document.createElement('div');
    aviso.className = 'alert alert-warning shadow';
    aviso.setAttribute('role', 'alert');
    aviso.style.cssText = 'position:fixed;left:50%;bottom:1rem;transform:translateX(-50%);z-index:2000;max-width:92vw;';
    aviso.innerHTML = 'Tu sesión se cerrará por inactividad en <strong id="idle-cuenta"></strong> s. <button type="button" class="btn btn-sm btn-dark ms-2" id="idle-seguir">Seguir conectado</button>';
    document.body.appendChild(aviso); cuenta = document.getElementById('idle-cuenta');
    document.getElementById('idle-seguir').addEventListener('click', function(){
      document.body.removeChild(aviso); aviso = null; ultimo = Date.now(); enviarPing();
    });
  }
  setInterval(function(){
    var inactivo = (Date.now() - ultimo) / 1000;
    if (inactivo >= seg) { salir(); return; }
    if (inactivo >= seg - margen && !aviso) mostrar();
    if (aviso && cuenta) cuenta.textContent = Math.max(0, Math.ceil(seg - inactivo));
  }, 1000);
})();

// Modo claro / oscuro (solo panel de administración; se recuerda en este navegador).
(function(){
  var b = document.getElementById('btn-tema'); if (!b) return; var h = document.documentElement;
  function etiqueta(){ b.textContent = h.getAttribute('data-bs-theme') === 'dark' ? 'Modo claro' : 'Modo oscuro'; }
  etiqueta();
  b.addEventListener('click', function(){
    var oscuro = h.getAttribute('data-bs-theme') === 'dark';
    if (oscuro) h.removeAttribute('data-bs-theme'); else h.setAttribute('data-bs-theme', 'dark');
    try { localStorage.setItem('cttaoa_tema', oscuro ? 'light' : 'dark'); } catch (e) {}
    etiqueta();
  });
})();

// Cuenta regresiva de las cuentas bloqueadas (Configuración).
(function(){
  var els = document.querySelectorAll('.cuenta-atras'); if (!els.length) return;
  setInterval(function(){
    els.forEach(function(el){
      var s = Math.max(0, parseInt(el.getAttribute('data-segundos'), 10) - 1); el.setAttribute('data-segundos', s);
      var m = Math.floor(s / 60), r = s % 60; el.textContent = (m < 10 ? '0' : '') + m + ':' + (r < 10 ? '0' : '') + r;
      if (s === 0) el.textContent = 'Ya puede intentar';
    });
  }, 1000);
})();

// Aviso de "no se permiten capturas": se muestra al detectar el atajo de captura (mejor esfuerzo; ver manual).
(function(){
  if (!document.body.getAttribute('data-guarda')) return;
  var aviso = document.createElement('div'); aviso.id = 'aviso-capturas';
  aviso.innerHTML = '<div><p style="font-size:1.4rem;font-weight:700">No se permiten capturas de pantalla</p><p>Esta pantalla contiene datos personales protegidos.</p></div>';
  document.body.appendChild(aviso);
  function bloquear(){
    document.body.classList.add('sin-capturas');
    try { if (navigator.clipboard) navigator.clipboard.writeText(' '); } catch (e) {}
    setTimeout(function(){ document.body.classList.remove('sin-capturas'); }, 3500);
  }
  function esAtajo(e){
    var k = (e.key || '').toLowerCase();
    return e.key === 'PrintScreen' || e.keyCode === 44 || ((e.metaKey || e.ctrlKey) && e.shiftKey && (k === '3' || k === '4' || k === '5' || k === 's'));
  }
  document.addEventListener('keydown', function(e){ if (esAtajo(e)) bloquear(); });
  document.addEventListener('keyup', function(e){ if (esAtajo(e)) bloquear(); });
})();

// Casilla «Acepto los términos»: el botón de enviar se habilita solo si está marcada.
(function(){
  document.querySelectorAll('form[data-acepto]').forEach(function(f){
    var c = f.querySelector('input[name=acepto]'), b = f.querySelector('button[type=submit]');
    if (!c || !b) return;
    function revisar(){ b.disabled = !c.checked; }
    c.addEventListener('change', revisar); revisar();
  });
})();

// Índice de la política de privacidad: resalta la sección que se está leyendo.
(function(){
  var enlaces = document.querySelectorAll('.legal-nav a[data-sec]');
  if (!enlaces.length || !('IntersectionObserver' in window)) return;
  var mapa = {}; enlaces.forEach(function(a){ mapa[a.getAttribute('data-sec')] = a; });
  var obs = new IntersectionObserver(function(entradas){
    entradas.forEach(function(e){
      if (e.isIntersecting) { enlaces.forEach(function(a){ a.classList.remove('act'); }); var a = mapa[e.target.id]; if (a) a.classList.add('act'); }
    });
  }, { rootMargin: '-15% 0px -75% 0px' });
  document.querySelectorAll('.legal-sec').forEach(function(s){ obs.observe(s); });
})();

// Formularios con archivos: si se envían SIN elegir ningún archivo, se mandan como formulario normal.
// (Algunos hostings gratuitos bloquean los envíos «multipart»; así guardar y editar sin foto sigue funcionando.)
document.addEventListener('submit', function(e){
  var f = e.target; if (!f || f.tagName !== 'FORM' || String(f.enctype || '').toLowerCase().indexOf('multipart') !== 0) return;
  var archivos = f.querySelectorAll('input[type=file]'), hay = false;
  archivos.forEach(function(i){ if (i.files && i.files.length) hay = true; });
  if (hay) return;
  archivos.forEach(function(i){ i.disabled = true; });
  f.enctype = 'application/x-www-form-urlencoded';
}, true);


// Marca en el menú lateral la opción de la pantalla donde estás
(function () {
  var enlaces = document.querySelectorAll('.p-side nav a'); if (!enlaces.length) return;
  var base = enlaces[0].pathname.replace(/\/+$/, '');                       // la ruta del panel (el enlace «Inicio»)
  var ruta = location.pathname.replace(/\/+$/, ''), mejor = null, largo = -1;
  Array.prototype.forEach.call(enlaces, function (a) {
    var h = a.pathname.replace(/\/+$/, '');
    if (h !== base && h.indexOf(base + '/') !== 0) return;                    // ignora el enlace a la página principal
    var coincide = (h === base) ? (ruta === base) : (ruta === h || ruta.indexOf(h + '/') === 0);
    if (coincide && h.length > largo) { mejor = a; largo = h.length; }
  });
  if (mejor) { mejor.classList.add('act'); mejor.setAttribute('aria-current', 'page'); }
})();































