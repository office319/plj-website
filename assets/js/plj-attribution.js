(function () {
  const STORAGE_FIRST_LANDING = 'plj_first_landing_url';
  const STORAGE_FIRST_REFERRER = 'plj_first_referrer';
  const PARAM_KEYS = [
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_content',
    'utm_term',
    'gclid',
    'gbraid',
    'wbraid',
    'fbclid',
    'msclkid',
  ];

  function getStored(key) {
    try {
      return window.sessionStorage.getItem(key) || '';
    } catch (_error) {
      return '';
    }
  }

  function setStored(key, value) {
    try {
      if (!getStored(key) && value) window.sessionStorage.setItem(key, value);
    } catch (_error) {
      // Ignore storage failures; the form still submits without attribution.
    }
  }

  function urlParams(url) {
    try {
      return new URL(url, window.location.origin).searchParams;
    } catch (_error) {
      return new URLSearchParams();
    }
  }

  function ensureInput(form, name, value) {
    if (!form || !name) return;
    let input = form.querySelector(`input[name="${name}"]`);
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      form.appendChild(input);
    }
    input.value = value || '';
  }

  function collectAttribution() {
    const currentUrl = window.location.href;
    setStored(STORAGE_FIRST_LANDING, currentUrl);
    setStored(STORAGE_FIRST_REFERRER, document.referrer || '');

    const firstLandingUrl = getStored(STORAGE_FIRST_LANDING) || currentUrl;
    const currentParams = urlParams(currentUrl);
    const firstParams = urlParams(firstLandingUrl);
    const data = {
      source_url: currentUrl,
      landing_url: currentUrl,
      first_landing_url: firstLandingUrl,
      referrer: document.referrer || '',
      first_referrer: getStored(STORAGE_FIRST_REFERRER) || '',
    };

    PARAM_KEYS.forEach((key) => {
      data[key] = currentParams.get(key) || firstParams.get(key) || '';
    });

    return data;
  }

  function fill(form) {
    if (!form || !String(form.getAttribute('action') || '').includes('contact.php')) return;
    const data = collectAttribution();
    Object.keys(data).forEach((key) => ensureInput(form, key, data[key]));
  }

  function fillAll() {
    document.querySelectorAll('form[action*="contact.php"]').forEach(fill);
  }

  window.PLJAttribution = { fill, fillAll, collect: collectAttribution };

  document.addEventListener('DOMContentLoaded', fillAll);
  document.addEventListener('submit', function (event) {
    fill(event.target);
  }, true);
}());
