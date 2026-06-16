

$(document).ready(function () {

  var isVideoCanceled = false;

  $('#anoncheckin-video-cancel').click(function (e) {
    console.log('click action on cancel button.')
    e.preventDefault();
    isVideoCanceled = true;
  });

  const video = document.getElementById('anoncheckin-video');

  function hideScanner() {
    isVideoCanceled = false;
    $('#anoncheckin-scanner').hide();
    $('#anoncheckin-overlay').hide();
    $('svg#anoncheckin-loading-indicator').hide();
    $('div#anoncheckin-scan-status-container').hide();
  }

  async function getCameraStream(constraints) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('Camera API not supported');
    }

    try {
      return await navigator.mediaDevices.getUserMedia(constraints);
    } catch (err) {
      // Normalize common cases
      if (err.name === 'NotAllowedError') {
        throw new Error('Camera permission denied');
      }
      if (err.name === 'NotFoundError') {
        throw new Error('No camera found');
      }
      if (err.name === 'NotReadableError') {
        throw new Error('Camera already in use');
      }
      throw new Error('Unable to access camera');
    }
  }

  function showMessage(text, type) {
    const el = document.getElementById('anoncheckin-scan-status');
    console.log('showMessage el', el, text, type);
    el.textContent = text;
    el.className = 'message-type-' + type;
    $('div#anoncheckin-scan-status-container').show();
  }

  async function openScanner(e) {
    e.preventDefault();
    $('svg#anoncheckin-loading-indicator').show();
    isVideoCanceled = false;

    var validateCallback = window[$(e.currentTarget).data('scanValidateCallback')];
    var clickEvent = e;

    $('#anoncheckin-overlay').show();
    $('#anoncheckin-scanner').show();

    try {
      const stream = await getCameraStream({
        video: {facingMode: "environment"}
      });

      video.srcObject = stream;
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      
      function scan() {
        console.log('scan isVideoCanceled', isVideoCanceled);
        if (video.readyState === video.HAVE_ENOUGH_DATA) {
          canvas.width = video.videoWidth;
          canvas.height = video.videoHeight;
          ctx.drawImage(video, 0, 0);

          const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
          const code = jsQR(imageData.data, canvas.width, canvas.height);

          if (code && code.data && validateCallback(code.data, clickEvent)) {
            // show success message

            stopVideo();

            // code value is now in code.data. Parse that and redirect to next step,
            window.location.href = code.data;
            return;
          }
          if (isVideoCanceled) {
            stopVideo();
            return;
          }
        }
        requestAnimationFrame(scan);
      }

      function stopVideo() {
        // stop the camera now that we have our code.
        stream.getTracks().forEach(t => t.stop());
        console.log('stopped video');
        hideScanner();
      }
      
      scan();
      $('#anoncheckin-video-cancel').show();
      $('svg#anoncheckin-loading-indicator').hide();
    } catch (err) {
      $('#anoncheckin-scanner').hide();
      showMessage(err.message + '\n(Try using your camera app instead.)', 'error');
    }
  }

  $('#anoncheckin-scan-badge').click(openScanner);
  $('#anoncheckin-scan-session').click(openScanner);
  $('#anoncheckin-scan-status-close').click(hideScanner);
  
});
    