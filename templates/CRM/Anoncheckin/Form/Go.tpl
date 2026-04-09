<style>
  .card {
    background: #fff;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  }
  .center {
    text-align: center;
  }
  .button {
    display: block;
    width: 100%;
    padding: 0.75rem;
    margin-top: 0.5rem;
    font-size: 1rem;
    border: none;
    border-radius: 6px;
    background: #007bff;
    color: #fff;
  }
  .button.secondary {
    background: #6c757d;
  }
  .button.success {
    background: #28a745;
  }
  .list {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .list li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
  }

  #anoncheckin-overlay {
    position: fixed;
    height: 100%;
    width: 100%;
    opacity: .85;
    background: black;
    top: 0;
    z-index: 1000;
  }

  #anoncheckin-scanner {
    position: fixed;
    width: 100%;
    top: 10%;
    left: 0;
    z-index: 2000;
    display: none;
  }
  #anoncheckin-video-cancel {
    display: block;
    width: 50%;
    left: 25%;
    position: relative;
    padding: 1em;
    margin-bottom: .5em;
  }
  #anoncheckin-video {
    position: relative;
    height: auto;
    width: 50%;
    border-radius: 1em;
    z-index: 2000;
    left: 25%;
  }


</style>
</head>
<body>

  <!-- Intro -->
  <div class="card center">
    <h2>Welcome</h2>
    <p>Record your sessions here.</p>
  </div>

  <!-- User -->
  <div class="card center">
    <strong>Marcus Brown</strong>
    <button id="anoncheckin-scan-badge" class="button secondary">Scan my badge</button>
  </div>

  <!-- Session selection -->
  <div class="card center">
    <div><strong>Session 3: Foo Bar</strong></div>
    <button class="button secondary">Scan session QR code</button>
  </div>

  <!-- Confirm -->
  <div class="card center">
    <button class="button success">Confirm and save</button>
  </div>

  <!-- Saved sessions -->
  <div class="card">
    <h2>Checked-in Sessions</h2>
    <ul class="list">
      <li>Session 1: Baz Bot</li>
      <li>Session 2: Bar Bif</li>
    </ul>
  </div>

  <div id="anoncheckin-scanner">
    <button id="anoncheckin-video-cancel">Cancel</button>
    <video id="anoncheckin-video" autoplay></video>
    <p id="anoncheckin-scan-status"></p>
  </div>
  <script>

    var isVideoCanceled = false;

    CRM.$('body').append('<div id="anoncheckin-overlay"></div>');
    CRM.$('#anoncheckin-overlay').hide();

    CRM.$('#anoncheckin-video-cancel').click(function (e) {
      // CRM.$('#anoncheckin-scanner').hide();
      e.preventDefault();
      isVideoCanceled = true;
    });

    const button = document.getElementById('anoncheckin-scan-badge');
    const video = document.getElementById('anoncheckin-video');
    {literal}
      
    function hideScanner() {
      isVideoCanceled = false;
      CRM.$('#anoncheckin-scanner').hide();
      CRM.$('#anoncheckin-overlay').hide();      
    }

    button.onclick = async (e) => {
      console.log('onclick isVideoCanceled', isVideoCanceled);
      e.preventDefault();

      CRM.$('#anoncheckin-overlay').show();
      CRM.$('#anoncheckin-scanner').show();

      const stream = await navigator.mediaDevices.getUserMedia({
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

          // if (code && isValidUrl(code.data)) {
          if (code) {
            // show success message and hide camera
            // CRM.$('#anoncheckin-video').hide();
            // CRM.$('#anoncheckin-scan-success').show();

            stopVideo();

            // code value is now in code.data. Parse that and redirect to next step,
            // window.location.href = code.data;
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
    };
    {/literal}

  </script>