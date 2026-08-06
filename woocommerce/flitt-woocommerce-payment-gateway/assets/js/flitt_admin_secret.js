(function () {
    'use strict';

    function initializeSecretToggles() {
        var toggles = document.querySelectorAll('[data-flitt-secret-toggle]');

        Array.prototype.forEach.call(toggles, function (toggle) {
            if (toggle.getAttribute('data-flitt-secret-ready') === 'true') {
                return;
            }

            var input = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!input) {
                return;
            }

            toggle.setAttribute('data-flitt-secret-ready', 'true');
            toggle.addEventListener('click', function () {
                var reveal = input.type === 'password';

                input.type = reveal ? 'text' : 'password';
                toggle.textContent = reveal
                    ? toggle.getAttribute('data-hide-label')
                    : toggle.getAttribute('data-show-label');
                toggle.setAttribute(
                    'aria-label',
                    reveal
                        ? toggle.getAttribute('data-hide-aria-label')
                        : toggle.getAttribute('data-show-aria-label')
                );
                toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
                input.focus();
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSecretToggles);
    } else {
        initializeSecretToggles();
    }
}());
