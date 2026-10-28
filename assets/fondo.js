/* Quitar el fondo de una fotografía EN EL NAVEGADOR: la imagen no sale del equipo hasta que se guarda.
   Usa MediaPipe «Selfie Segmentation» (Apache-2.0), incluido en assets/vendor/selfie_segmentation/ (funciona sin internet).
   Se activa en los campos de archivo que tengan el atributo data-quitar-fondo. El fondo queda blanco. */
(function () {
  'use strict';
  var BASE = (function () { var s = document.currentScript; return s ? s.src.replace(/fondo\.js(\?.*)?$/, 'vendor/selfie_segmentation/') : ''; })();
  var MAX = 1000;                         // lado mayor (px) con el que se trabaja; suficiente para un carnet
  var modelo = null, cargando = null;

  function cargarScript(src) {
    return new Promise(function (ok, err) {
      var s = document.createElement('script'); s.src = src; s.onload = ok; s.onerror = function () { err(new Error('No se pudo cargar ' + src)); };
      document.head.appendChild(s);
    });
  }
  function obtenerModelo() {
    if (modelo) return Promise.resolve(modelo);
    if (!cargando) cargando = cargarScript(BASE + 'selfie_segmentation.js').then(function () {
      var m = new window.SelfieSegmentation({ locateFile: function (f) { return BASE + f; } });
      m.setOptions({ modelSelection: 0 });
      return m.initialize().then(function () { modelo = m; return m; });
    }).catch(function (e) { cargando = null; throw e; });
    return cargando;
  }
  function leerImagen(file) {
    return new Promise(function (ok, err) {
      var url = URL.createObjectURL(file), img = new Image();
      img.onload = function () { URL.revokeObjectURL(url); ok(img); };
      img.onerror = function () { URL.revokeObjectURL(url); err(new Error('No se pudo leer la imagen.')); };
      img.src = url;                       // el navegador respeta la orientación de la foto (EXIF)
    });
  }
  function suavizado(x, a, b) { var t = Math.min(1, Math.max(0, (x - a) / (b - a))); return t * t * (3 - 2 * t); }

  /** Devuelve un Blob JPEG con la persona sobre fondo blanco. */
  function quitarFondo(file) {
    return Promise.all([leerImagen(file), obtenerModelo()]).then(function (r) {
      var img = r[0], m = r[1], k = Math.min(1, MAX / Math.max(img.naturalWidth, img.naturalHeight));
      var w = Math.round(img.naturalWidth * k), h = Math.round(img.naturalHeight * k);
      var entrada = document.createElement('canvas'); entrada.width = w; entrada.height = h; entrada.getContext('2d').drawImage(img, 0, 0, w, h);
      return new Promise(function (ok, err) {
        m.onResults(function (res) { ok(res.segmentationMask); });
        m.send({ image: entrada }).catch(err);
      }).then(function (mask) {
        var mc = document.createElement('canvas'); mc.width = w; mc.height = h; var mx = mc.getContext('2d', { willReadFrequently: true });
        mx.drawImage(mask, 0, 0, w, h);
        var d = mx.getImageData(0, 0, w, h), px = d.data, usaAlfa = false, mn = 255, mxv = 0, i;
        for (i = 3; i < px.length; i += 4 * 97) { if (px[i] < mn) mn = px[i]; if (px[i] > mxv) mxv = px[i]; }
        usaAlfa = (mxv - mn) > 40;          // algunas versiones guardan la máscara en el canal alfa y otras en el rojo
        for (i = 0; i < px.length; i += 4) { var v = (usaAlfa ? px[i + 3] : px[i]) / 255; px[i] = px[i + 1] = px[i + 2] = 0; px[i + 3] = Math.round(255 * suavizado(v, 0.35, 0.75)); }
        mx.putImageData(d, 0, 0);
        var suave = document.createElement('canvas'); suave.width = w; suave.height = h; var sx = suave.getContext('2d');
        try { sx.filter = 'blur(1.2px)'; } catch (e) { /* sin filtro: se usa la máscara tal cual */ }
        sx.drawImage(mc, 0, 0); sx.filter = 'none';
        var persona = document.createElement('canvas'); persona.width = w; persona.height = h; var px2 = persona.getContext('2d');
        px2.drawImage(entrada, 0, 0); px2.globalCompositeOperation = 'destination-in'; px2.drawImage(suave, 0, 0);
        var sal = document.createElement('canvas'); sal.width = w; sal.height = h; var so = sal.getContext('2d');
        so.fillStyle = '#ffffff'; so.fillRect(0, 0, w, h); so.drawImage(persona, 0, 0);
        return new Promise(function (ok2, err2) { sal.toBlob(function (b) { b ? ok2(b) : err2(new Error('No se pudo crear la imagen.')); }, 'image/jpeg', 0.92); });
      });
    });
  }

  function montar(input) {
    var caja = document.createElement('div'); caja.className = 'qf-caja small';
    caja.innerHTML = '<button type="button" class="btn btn-sm btn-outline-success" disabled>Quitar fondo (queda blanco)</button> ' +
      '<button type="button" class="btn btn-sm btn-outline-secondary d-none">Usar la original</button> <span class="qf-estado text-muted"></span><div class="qf-prev d-none"></div>';
    input.insertAdjacentElement('afterend', caja);
    var bQuitar = caja.children[0], bOrig = caja.children[1], estado = caja.querySelector('.qf-estado'), prev = caja.querySelector('.qf-prev');
    var original = null, ocupado = false;
    function dar(file) { try { var dt = new DataTransfer(); dt.items.add(file); input.files = dt.files; return true; } catch (e) { return false; } }
    function limpiar() { prev.classList.add('d-none'); prev.innerHTML = ''; bOrig.classList.add('d-none'); estado.textContent = ''; }
    input.addEventListener('change', function () {
      if (input.__qf) { input.__qf = false; return; }          // el cambio lo hizo este mismo script
      original = (input.files && input.files[0]) || null; limpiar(); bQuitar.disabled = !original;
    });
    bQuitar.addEventListener('click', function () {
      if (!original || ocupado) return;
      ocupado = true; bQuitar.disabled = true; estado.className = 'qf-estado text-muted';
      estado.textContent = 'Quitando el fondo… (la primera vez tarda unos segundos más)';
      quitarFondo(original).then(function (blob) {
        var base = original.name.replace(/\.[^.]+$/, '') || 'foto', nuevo = new File([blob], base + '-sin-fondo.jpg', { type: 'image/jpeg' });
        input.__qf = true;
        if (!dar(nuevo)) { input.__qf = false; throw new Error('Tu navegador no permite reemplazar la foto: usa otro navegador.'); }
        var a = URL.createObjectURL(original), b = URL.createObjectURL(nuevo);
        prev.innerHTML = '<img src="' + a + '" alt="Foto original"> <img src="' + b + '" alt="Foto sin fondo">'; prev.classList.remove('d-none');
        bOrig.classList.remove('d-none'); estado.className = 'qf-estado text-success';
        estado.textContent = 'Listo: se usará la foto sin fondo (derecha). Si no te gusta, pulsa «Usar la original».';
      }).catch(function (e) {
        estado.className = 'qf-estado text-danger'; estado.textContent = 'No se pudo quitar el fondo (' + (e && e.message ? e.message : 'error') + '). Se usará la foto original.';
      }).then(function () { ocupado = false; bQuitar.disabled = !original; });
    });
    bOrig.addEventListener('click', function () {
      if (!original) return; input.__qf = true; if (!dar(original)) input.__qf = false; limpiar(); estado.className = 'qf-estado text-muted'; estado.textContent = 'Se usará la foto original.';
    });
  }

  /** Carga masiva: quita el fondo a TODAS las fotos elegidas, una por una. Conserva el nombre (el número de documento) y cambia solo la extensión a .jpg. */
  function montarMasivo(input) {
    var caja = document.createElement('div'); caja.className = 'qf-caja small';
    caja.innerHTML = '<button type="button" class="btn btn-sm btn-outline-success" disabled>Quitar el fondo de todas las fotos (queda blanco)</button> ' +
      '<button type="button" class="btn btn-sm btn-outline-danger d-none">Cancelar</button> <button type="button" class="btn btn-sm btn-outline-secondary d-none">Usar las originales</button> ' +
      '<span class="qf-estado text-muted"></span><div class="progress d-none mt-1" style="height:.55rem"><div class="progress-bar bg-success" style="width:0%"></div></div>';
    input.insertAdjacentElement('afterend', caja);
    var bQuitar = caja.children[0], bCancelar = caja.children[1], bOrig = caja.children[2], estado = caja.querySelector('.qf-estado'), barra = caja.querySelector('.progress'), relleno = barra.firstElementChild;
    var originales = [], ocupado = false, cancelar = false;
    function dar(lista) { try { var dt = new DataTransfer(); lista.forEach(function (f) { dt.items.add(f); }); input.files = dt.files; return true; } catch (e) { return false; } }
    function reiniciar() { bOrig.classList.add('d-none'); bCancelar.classList.add('d-none'); barra.classList.add('d-none'); relleno.style.width = '0%'; estado.textContent = ''; estado.className = 'qf-estado text-muted'; }
    input.addEventListener('change', function () {
      if (input.__qf) { input.__qf = false; return; }
      originales = Array.prototype.slice.call(input.files || []); reiniciar(); bQuitar.disabled = !originales.length;
      if (originales.length) estado.textContent = originales.length + ' foto(s) elegida(s).';
    });
    bCancelar.addEventListener('click', function () { cancelar = true; });
    bQuitar.addEventListener('click', function () {
      if (!originales.length || ocupado) return;
      ocupado = true; cancelar = false; bQuitar.disabled = true; bCancelar.classList.remove('d-none'); barra.classList.remove('d-none'); bOrig.classList.add('d-none');
      var total = originales.length, hechas = 0, fallos = 0, resultado = originales.slice(), primero = true;
      estado.className = 'qf-estado text-muted'; estado.textContent = 'Preparando… (la primera vez tarda unos segundos más)';
      function siguiente(i) {
        if (i >= total || cancelar) return Promise.resolve();
        var f = originales[i];
        return quitarFondo(f).then(function (blob) {
          resultado[i] = new File([blob], (f.name.replace(/\.[^.]+$/, '') || 'foto') + '.jpg', { type: 'image/jpeg' });
        }).catch(function () { fallos++; }).then(function () {
          hechas++; relleno.style.width = Math.round(100 * hechas / total) + '%'; estado.textContent = 'Quitando el fondo… ' + hechas + ' de ' + total + (fallos ? ' (' + fallos + ' sin poder procesar)' : '');
          return siguiente(i + 1);
        });
      }
      siguiente(0).then(function () {
        input.__qf = true; var ok = dar(resultado); if (!ok) input.__qf = false;
        bCancelar.classList.add('d-none'); bOrig.classList.remove('d-none');
        if (!ok) { estado.className = 'qf-estado text-danger'; estado.textContent = 'Tu navegador no permite reemplazar las fotos: usa otro navegador.'; return; }
        var buenas = hechas - fallos; estado.className = 'qf-estado ' + (fallos ? 'text-warning' : 'text-success');
        estado.textContent = (cancelar ? 'Cancelado a la mitad: ' : 'Listo: ') + buenas + ' foto(s) sin fondo' + (fallos ? ', ' + fallos + ' se dejaron como estaban (no se pudo procesar)' : '') + (cancelar ? ', el resto quedó con la foto original' : '') + '. Los nombres se conservan.';
      }).then(function () { ocupado = false; bQuitar.disabled = !originales.length; });
    });
    bOrig.addEventListener('click', function () {
      if (!originales.length) return; input.__qf = true; if (!dar(originales)) input.__qf = false; reiniciar(); estado.textContent = 'Se usarán las ' + originales.length + ' foto(s) originales.';
    });
  }
  document.addEventListener('DOMContentLoaded', function () { Array.prototype.forEach.call(document.querySelectorAll('input[type=file][data-quitar-fondo]'), function (i) { (i.getAttribute('data-quitar-fondo') === 'masivo' ? montarMasivo : montar)(i); }); });
})();
