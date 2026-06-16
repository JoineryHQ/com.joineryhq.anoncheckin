window.anoncheckinQrScanner = {

  isVideoCanceled: false,
  video: null,

  init: function() {
    anoncheckinQrScanner.video = document.getElementById('anoncheckin-video');

    $('#anoncheckin-video-cancel').click(
      anoncheckinQrScanner.cancelScan.bind(anoncheckinQrScanner)
    );

    $('#anoncheckin-scan-status-close').click(
      anoncheckinQrScanner.hideScanner.bind(anoncheckinQrScanner)
    );
  },

  cancelScan: function(e) {
    console.log('click action on cancel button.');
    e.preventDefault();
    anoncheckinQrScanner.isVideoCanceled = true;
  },

  hideScanner: function() {
    anoncheckinQrScanner.isVideoCanceled = false;
    $('#anoncheckin-scanner').hide();
    $('#anoncheckin-overlay').hide();
    $('svg#anoncheckin-loading-indicator').hide();
    $('div#anoncheckin-scan-status-container').hide();
  },

  showMessage: function(text, type) {
    const el = document.getElementById('anoncheckin-scan-status');
    console.log('showMessage el', el, text, type);
    el.textContent = text;
    el.className = 'message-type-' + type;
    $('div#anoncheckin-scan-status-container').show();
  },

  getCameraStream: async function(constraints) {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      throw new Error('Camera API not supported');
    }

    try {
      return await navigator.mediaDevices.getUserMedia(constraints);
    }
    catch (err) {
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
  },

  openScanner: async function(e) {
    e.preventDefault();

    $('svg#anoncheckin-loading-indicator').show();
    anoncheckinQrScanner.isVideoCanceled = false;

    const validateCallback =
      window[$(e.currentTarget).data('scanValidateCallback')];

    const clickEvent = e;

    $('#anoncheckin-overlay').show();
    $('#anoncheckin-scanner').show();

    try {
      const stream = await anoncheckinQrScanner.getCameraStream({
        video: {facingMode: 'environment'}
      });

      anoncheckinQrScanner.video.srcObject = stream;

      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');

      const self = anoncheckinQrScanner;

      function stopVideo() {
        stream.getTracks().forEach(t => t.stop());
        console.log('stopped video');
        self.hideScanner();
      }

      function scan() {
        console.log('scan isVideoCanceled', self.isVideoCanceled);

        if (self.video.readyState === self.video.HAVE_ENOUGH_DATA) {
          canvas.width = self.video.videoWidth;
          canvas.height = self.video.videoHeight;

          ctx.drawImage(self.video, 0, 0);

          const imageData = ctx.getImageData(
            0,
            0,
            canvas.width,
            canvas.height
          );

          const code = jsQR(
            imageData.data,
            canvas.width,
            canvas.height
          );

          if (
            code &&
            code.data &&
            validateCallback(code.data, clickEvent)
          ) {
            stopVideo();
            window.location.href = code.data;
            return;
          }

          if (self.isVideoCanceled) {
            stopVideo();
            return;
          }
        }

        requestAnimationFrame(scan);
      }

      scan();

      $('#anoncheckin-video-cancel').show();
      $('svg#anoncheckin-loading-indicator').hide();
    }
    catch (err) {
      $('#anoncheckin-scanner').hide();

      anoncheckinQrScanner.showMessage(
        err.message + '\n(Try using your camera app instead.)',
        'error'
      );
    }
  }

};

$(function() {
  anoncheckinQrScanner.init();
});