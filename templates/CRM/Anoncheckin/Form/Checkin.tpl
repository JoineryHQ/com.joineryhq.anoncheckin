{if !$sid}
  {ts}We can't detect the session name. Please scan the session QR code again.{/ts}
{else}
  {if $participantName}
    <p>Hello, <strong>{$participantName}</strong>
  {/if}
  {if $sessionName}
    <h4>Attending session:</h4>
    <p>{$sessionName}</p>
  {/if}

  {if $pid}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
  {else}
    {ts}Next we need to identify you. Please scan your personal QR code.{/ts}

    <p>We'll remember this session for {$countdownMinutes} minutes.</p>
    <div id="countdown"></div>

    <script>
      const display = document.getElementById('countdown');

      let remainingSeconds = (60 * {$countdownMinutes});

      {literal}

        function render() {
          const minutes = Math.floor(remainingSeconds / 60);
          const seconds = remainingSeconds % 60;
          display.textContent = `${minutes}:${String(seconds).padStart(2, '0')}`;
        }

        render();

        const timer = setInterval(() => {
          remainingSeconds--;

          if (remainingSeconds <= 0) {
            remainingSeconds = 0;
            render();
            clearInterval(timer);
            return;
          }

          render();
        }, 1000);
      {/literal}
    </script>
  {/if}
{/if}