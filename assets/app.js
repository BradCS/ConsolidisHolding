(function () {
  const toggleBtn = document.getElementById('contactToggle');
  const panel = document.getElementById('contactPanel');
  const turnstileContainer = document.getElementById('turnstileContainer');

  if (!toggleBtn || !panel || !turnstileContainer) {
    return;
  }

  let scriptLoaded = false;
  let widgetRendered = false;

  function loadTurnstileScript(callback) {
    if (scriptLoaded) {
      callback();
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
    script.async = true;
    script.defer = true;
    script.onload = function () {
      scriptLoaded = true;
      callback();
    };
    document.head.appendChild(script);
  }

  function renderTurnstileIfNeeded() {
    if (widgetRendered) {
      return;
    }

    const siteKey = turnstileContainer.getAttribute('data-sitekey');
    if (!siteKey || !window.turnstile) {
      return;
    }

    window.turnstile.render('#turnstileContainer', {
      sitekey: siteKey,
      theme: 'light',
    });

    widgetRendered = true;
  }

  toggleBtn.addEventListener('click', function () {
    const isOpen = !panel.hasAttribute('hidden');

    if (isOpen) {
      panel.setAttribute('hidden', 'hidden');
      toggleBtn.setAttribute('aria-expanded', 'false');
      return;
    }

    panel.removeAttribute('hidden');
    toggleBtn.setAttribute('aria-expanded', 'true');

    loadTurnstileScript(renderTurnstileIfNeeded);

    const firstInput = panel.querySelector('input, textarea');
    if (firstInput) {
      firstInput.focus();
    }
  });
})();
