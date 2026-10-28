// Página pública /verificar: lee el código de barras del carnet con la cámara.
// Usa el lector nativo del navegador (BarcodeDetector, Chrome/Android) y, si no existe (por ejemplo iPhone/Safari), la librería ZXing.
(function(){
  var form = document.getElementById('form-verificar'); if (!form) return;
  var input = document.getElementById('v-doc'), btn = document.getElementById('btn-escanear'), caja = document.getElementById('caja-escaner'),
      video = document.getElementById('video-escaner'), aviso = document.getElementById('aviso-escaner'), cerrar = document.getElementById('btn-cerrar-escaner');
  var stream = null, lector = null, temporizador = null, activo = false;
  function msg(t, error){ aviso.textContent = t; aviso.className = 'small mt-2 ' + (error ? 'text-danger' : 'text-muted'); }
  function parar(){
    activo = false; if (temporizador) { clearTimeout(temporizador); temporizador = null; }
    if (lector) { try { lector.reset(); } catch (e) {} lector = null; }
    if (stream) { stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; }
    try { video.srcObject = null; } catch (e) {} btn.disabled = false;
  }
  function cerrarTodo(){ parar(); caja.classList.add('d-none'); }
  function errorCamara(e){
    parar();
    msg((e && e.name === 'NotAllowedError') ? 'No diste permiso para usar la cámara. Actívalo en el navegador o escribe el documento.' : 'No se pudo abrir la cámara. Escribe el documento.', true);
  }
  function encontrado(texto){
    var d = String(texto || '').replace(/\D/g, '');
    if (d.length < 5 || d.length > 15) return false;
    input.value = d; msg('Código leído: ' + d); parar(); enviar(); return true;
  }
  var CAMARA = { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false };
  // Lector nativo del navegador: nosotros abrimos la cámara y le pasamos los cuadros.
  async function usarNativo(){
    try { stream = await navigator.mediaDevices.getUserMedia(CAMARA); } catch (e) { return errorCamara(e); }
    video.setAttribute('playsinline', ''); video.muted = true; video.srcObject = stream;
    try { await video.play(); } catch (e) {}
    activo = true; msg('Apunta al código de barras del carnet; acércalo o aléjalo hasta que se lea.');
    var det = new window.BarcodeDetector({ formats: ['code_128', 'code_39', 'itf', 'ean_13'] });
    (function ciclo(){
      if (!activo) return;
      det.detect(video).then(function(r){ for (var i = 0; i < r.length; i++) if (encontrado(r[i].rawValue)) return; temporizador = setTimeout(ciclo, 150); })
        .catch(function(){ temporizador = setTimeout(ciclo, 300); });
    })();
  }
  // Librería ZXing: ella misma abre la cámara y decodifica de forma continua.
  function usarZXing(){
    function arrancar(){
      var Z = window.ZXing, hints = new Map();
      hints.set(Z.DecodeHintType.POSSIBLE_FORMATS, [Z.BarcodeFormat.CODE_128, Z.BarcodeFormat.CODE_39, Z.BarcodeFormat.ITF, Z.BarcodeFormat.EAN_13]);
      // (no activar TRY_HARDER: en lectura continua impide leer el código de barras del carnet)
      lector = new Z.BrowserMultiFormatReader(hints, 150);
      video.setAttribute('playsinline', ''); video.muted = true; activo = true;
      lector.decodeFromConstraints(CAMARA, video, function(res){ if (res) encontrado(res.getText()); })
        .then(function(){ msg('Apunta al código de barras del carnet; acércalo o aléjalo hasta que se lea.'); }).catch(errorCamara);
    }
    if (window.ZXing) return arrancar();
    var s = document.createElement('script'); s.src = 'https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js';
    s.onload = arrancar; s.onerror = function(){ parar(); msg('No se pudo cargar el lector de códigos. Escribe el documento.', true); }; document.head.appendChild(s);
  }
  async function iniciar(){
    caja.classList.remove('d-none');
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) { msg('Este navegador no permite usar la cámara aquí. Escribe el documento.', true); return; }
    if (window.isSecureContext === false) { msg('La cámara solo funciona con conexión segura (https). Escribe el documento.', true); return; }
    audio(); btn.disabled = true; msg('Abriendo la cámara…');
    var nativo = false;
    if ('BarcodeDetector' in window && window.BarcodeDetector.getSupportedFormats) {
      try { nativo = (await window.BarcodeDetector.getSupportedFormats()).indexOf('code_128') >= 0; } catch (e) {}
    }
    if (nativo) usarNativo(); else usarZXing();
  }

    // ---------- Sonido y vibración del resultado (Web Audio: no usa archivos de audio) ----------
  var ctxAudio = null, sonidoOn = true;
  try { sonidoOn = localStorage.getItem('cttaoa_sonido') !== '0'; } catch (e) {}
  function audio(){
    try { var AC = window.AudioContext || window.webkitAudioContext; if (!AC) return null; if (!ctxAudio) ctxAudio = new AC(); if (ctxAudio.state === 'suspended' && ctxAudio.resume) ctxAudio.resume(); return ctxAudio; } catch (e) { return null; }
  }
  function tono(freq, ini, dur, tipo, vol){
    var c = audio(); if (!c) return; var o = c.createOscillator(), g = c.createGain(), t0 = c.currentTime + ini;
    o.type = tipo || 'sine'; o.frequency.value = freq; g.gain.setValueAtTime(0.0001, t0); g.gain.exponentialRampToValueAtTime(vol || 0.25, t0 + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
    o.connect(g); g.connect(c.destination); o.start(t0); o.stop(t0 + dur + 0.05);
  }
  function vibrar(p){ try { if (navigator.vibrate) navigator.vibrate(p); } catch (e) {} }
  function sonar(estado){
    if (!sonidoOn) return;
    try {
      if (estado === 'ok') { tono(880, 0, 0.14, 'sine'); tono(1320, 0.16, 0.24, 'sine'); vibrar(80); }                 // bien: dos tonos agudos que suben
      else if (estado === 'mal') { tono(196, 0, 0.6, 'sawtooth', 0.18); vibrar([220, 90, 220]); }                      // no vigente: un zumbido grave y largo
      else { tono(330, 0, 0.16, 'square', 0.14); tono(330, 0.26, 0.16, 'square', 0.14); vibrar([120, 80, 120]); }       // no registrado: dos pitidos cortos
    } catch (e) {}
  }
  function estadoDe(sec){ return sec.classList.contains('verif-ok') ? 'ok' : (sec.classList.contains('verif-mal') ? 'mal' : 'no'); }
  var botonSonido = document.createElement('button'); botonSonido.type = 'button'; botonSonido.className = 'btn btn-sm btn-outline-secondary mt-2 align-self-start';
  function etiquetaSonido(){ botonSonido.textContent = sonidoOn ? '🔊 Sonido del resultado: activado' : '🔇 Sonido del resultado: apagado'; }
  etiquetaSonido(); btn.parentNode.insertAdjacentElement('afterend', botonSonido);
  botonSonido.addEventListener('click', function(){ sonidoOn = !sonidoOn; try { localStorage.setItem('cttaoa_sonido', sonidoOn ? '1' : '0'); } catch (e) {} etiquetaSonido(); if (sonidoOn) { audio(); sonar('ok'); } });
  // La consulta se hace sin recargar la página: así el navegador deja sonar el tono (ya hubo un toque del usuario).
  var enviando = false;
  function enviar(){ if (form.requestSubmit) form.requestSubmit(); else form.submit(); }
  form.addEventListener('submit', function(ev){
    if (!window.fetch || !window.DOMParser) return;                      // sin soporte: envío normal
    ev.preventDefault(); if (enviando) return; enviando = true; audio();
    var bt = form.querySelector('button[type=submit]'); if (bt) bt.disabled = true;
    fetch(location.href, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(new FormData(form)).toString() })
      .then(function(r){ return r.text(); })
      .then(function(html){
        var d = new DOMParser().parseFromString(html, 'text/html'), nuevo = d.getElementById('resultado'), tk = d.querySelector('#form-verificar input[name=_csrf]');
        if (!nuevo) throw new Error('sin resultado');
        if (tk) form.querySelector('input[name=_csrf]').value = tk.value;
        var viejo = document.getElementById('resultado'); if (viejo) viejo.remove();
        form.insertAdjacentElement('afterend', nuevo); nuevo.scrollIntoView({ block: 'center' }); sonar(estadoDe(nuevo));
      })
      .catch(function(){ enviando = false; form.submit(); })
      .then(function(){ enviando = false; if (bt) bt.disabled = false; });
  });






  btn.addEventListener('click', iniciar); cerrar.addEventListener('click', cerrarTodo);
  window.addEventListener('pagehide', parar);
})();
