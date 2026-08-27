{{-- Global double-submit guard.

     Every non-GET form is allowed exactly one in-flight submission. This stops a
     double-clicked "Add to cart" / "Place order" from sending two requests — and
     therefore reporting two conversion events. The listener runs in the bubble
     phase and ignores submissions a page handler already cancelled (AJAX forms,
     confirm() prompts, client-side validation), so those forms stay usable.
     Opt out entirely with data-allow-multi-submit.
--}}
<script>
(function () {
    function submitControls(form) {
        return form.querySelectorAll('button:not([type=button]), input[type=submit], input[type=image]');
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form || form.tagName !== 'FORM') { return; }
        if ((form.method || 'get').toLowerCase() === 'get') { return; }
        if (event.defaultPrevented) { return; }
        if (form.hasAttribute('data-allow-multi-submit')) { return; }

        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = '1';

        // Deferred so the browser has already serialised the form — disabling a
        // control synchronously would drop its name/value from the payload.
        setTimeout(function () {
            submitControls(form).forEach(function (control) {
                control.disabled = true;
                control.classList.add('opacity-60', 'cursor-not-allowed');
            });
        }, 0);
    });

    // Back/forward cache restores the DOM as it was left — re-arm the forms.
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) { return; }
        document.querySelectorAll('form[data-submitting="1"]').forEach(function (form) {
            delete form.dataset.submitting;
            submitControls(form).forEach(function (control) {
                control.disabled = false;
                control.classList.remove('opacity-60', 'cursor-not-allowed');
            });
        });
    });
})();
</script>
