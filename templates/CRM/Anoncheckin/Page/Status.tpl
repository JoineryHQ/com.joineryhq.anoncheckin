{if $pid}
  <p>Hello, <strong>{$display_name}</strong>.

  {if empty($sessions)}
    <p>You have no sessions recorded for this event.</p>
    <p>Please scan a <strong>session QR code</strong> to identify your next session.</p>
  {else}
    <p>These sessions have been recorded for you in this event:</p>
    <table>
      {foreach from=$sessions item=session}
        <hr>{$session}
      {/foreach}
    </table>
  {/if}
  <a id="anoncheckin-scanButton" class="button" href="#">Scan a <strong>session QR code</strong> to record attendance.</a>
{else}
  <p>Hello.</p>
  <a id="anoncheckin-scanButton" class="button" href="#">Please scan your <strong>personal QR code</strong> to identify yourself.</a>
{/if}

<video id="anoncheckin-video" width="300" height="200" autoplay></video>
<p id="anoncheckin-scan-success">Scanned! Redirecting ...</p>

<script>
const button = document.getElementById('anoncheckin-scanButton');
const video = document.getElementById('anoncheckin-video');

button.onclick = async () => {
  const stream = await navigator.mediaDevices.getUserMedia({
    video: { facingMode: "environment" }
  });

  video.srcObject = stream;

  const canvas = document.createElement('canvas');
  const ctx = canvas.getContext('2d');
  function isValidUrl(qrData) {
    for (i in CRM.vars.anoncheckin.validQrBaseUrls) {
      if (qrData.startsWith(CRM.vars.anoncheckin.validQrBaseUrls[i])) {
        return true;
      }
    }
    return false;
  }

  function scan() {
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0);

      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const code = jsQR(imageData.data, canvas.width, canvas.height);

      if (code && isValidUrl(code.data)) {
        // stop the camera now that we have our code.
        stream.getTracks().forEach(t => t.stop());
        // show success message and hide camera
        CRM.$('#anoncheckin-video').hide();
        CRM.$('#anoncheckin-scan-success').show();
        
        // code value is now in code.data. Parse that and redirect to next step,
        window.location.href = code.data;
        return;
      }
    }
    requestAnimationFrame(scan);
  }

  scan();
};
</script>