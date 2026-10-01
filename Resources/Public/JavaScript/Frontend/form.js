/**
 * CaptchaFox for TYPO3 forms: renders every CaptchaFox element explicitly and writes its token into the
 * hidden field of the same form element, so several forms with CaptchaFox work on one page.
 *
 * The server-side verification of that field stays decisive; this script only connects widget and field.
 */
(() => {
  'use strict';

  const SELECTOR = '[data-captchafox-typo3]';

  const fieldOf = (container) => document.getElementById(container.dataset.captchafoxField || '');

  const render = (container) => {
    if (container.dataset.cfState) {
      return;
    }
    container.dataset.cfState = 'rendering';

    const field = fieldOf(container);
    const setToken = (token) => {
      if (field) {
        field.value = typeof token === 'string' ? token : '';
      }
    };

    // A form shown again after a failed validation still carries the token that was already used.
    setToken('');

    const options = {
      sitekey: container.dataset.sitekey,
      onVerify: setToken,
      onExpire: () => setToken(''),
      onFail: () => setToken(''),
      onError: () => setToken(''),
    };
    ['mode', 'lang', 'theme'].forEach((name) => {
      if (container.dataset[name]) {
        options[name] = container.dataset[name];
      }
    });

    Promise.resolve(window.captchafox.render(container, options))
      .then(() => {
        container.dataset.cfState = 'rendered';
      })
      .catch(() => {
        container.dataset.cfState = 'error';
      });
  };

  const renderAll = () => {
    document.querySelectorAll(SELECTOR).forEach(render);
  };

  // Called by the CaptchaFox API once it is loaded (onload parameter of the script URL).
  window.captchaFoxTypo3OnLoad = renderAll;

  // The API may already be loaded, e.g. when another script loaded it first.
  if (window.captchafox && typeof window.captchafox.render === 'function') {
    renderAll();
  }
})();
