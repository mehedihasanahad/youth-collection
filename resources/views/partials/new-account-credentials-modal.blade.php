{{-- One-time account credentials for a customer who checked out without an email.
     Rendered only when the confirmation controller pulled them out of the session. --}}
@if(!empty($newCredentials))
<div id="credentials-modal"
     class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 bg-gray-900/60"
     role="dialog" aria-modal="true" aria-labelledby="credentials-modal-title">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">

        {{-- Header --}}
        <div class="bg-amber-50 border-b border-amber-200 px-6 py-5 flex items-start gap-3">
            <svg class="w-6 h-6 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h2 id="credentials-modal-title" class="text-base font-bold text-amber-900">
                    {{ __('front.credentials_modal_title') }}
                </h2>
                <p class="text-sm text-amber-700 mt-1">{{ __('front.credentials_modal_warning') }}</p>
            </div>
        </div>

        {{-- Credentials --}}
        <div class="px-6 py-5 space-y-4">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                    {{ __('front.credentials_login_id') }}
                </p>
                <p class="font-mono text-sm text-gray-900 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5">
                    {{ $newCredentials['phone'] }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">
                    {{ __('front.password') }}
                </p>
                <div class="flex gap-2">
                    <p id="credentials-password"
                       class="flex-1 font-mono text-sm tracking-wider text-gray-900 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 select-all break-all">{{ $newCredentials['password'] }}</p>
                    <button type="button"
                            id="credentials-copy-btn"
                            class="shrink-0 inline-flex items-center gap-1.5 border-2 border-primary-500 text-primary-600 hover:bg-primary-600 hover:text-white font-semibold px-4 rounded-xl transition-colors text-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span id="credentials-copy-label">{{ __('front.copy') }}</span>
                    </button>
                </div>
            </div>

            <p class="text-xs text-gray-500">{{ __('front.credentials_modal_hint') }}</p>
        </div>

        {{-- Acknowledge --}}
        <div class="px-6 pb-6">
            <label class="flex items-start gap-2 mb-4 cursor-pointer">
                <input type="checkbox" id="credentials-ack"
                       class="mt-0.5 w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-gray-600">{{ __('front.credentials_modal_ack') }}</span>
            </label>

            <button type="button"
                    id="credentials-close-btn"
                    disabled
                    class="w-full bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-xl transition-colors text-sm">
                {{ __('front.credentials_modal_done') }}
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var modal    = document.getElementById('credentials-modal');
    var ack      = document.getElementById('credentials-ack');
    var closeBtn = document.getElementById('credentials-close-btn');
    var copyBtn  = document.getElementById('credentials-copy-btn');
    var label    = document.getElementById('credentials-copy-label');
    var password = document.getElementById('credentials-password');

    if (!modal) return;

    // The page behind the modal must not scroll while it is open.
    document.body.style.overflow = 'hidden';

    ack.addEventListener('change', function () {
        closeBtn.disabled = !ack.checked;
    });

    closeBtn.addEventListener('click', function () {
        modal.remove();
        document.body.style.overflow = '';
    });

    copyBtn.addEventListener('click', function () {
        var text = password.textContent.trim();

        var done = function () {
            label.textContent = @json(__('front.copied'));
            setTimeout(function () { label.textContent = @json(__('front.copy')); }, 2000);
        };

        // navigator.clipboard needs a secure context; fall back for plain HTTP.
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(fallback);
        } else {
            fallback();
        }

        function fallback() {
            var field = document.createElement('textarea');
            field.value = text;
            field.setAttribute('readonly', '');
            field.style.position = 'absolute';
            field.style.left = '-9999px';
            document.body.appendChild(field);
            field.select();
            try { document.execCommand('copy'); done(); } catch (e) { /* clipboard unavailable */ }
            document.body.removeChild(field);
        }
    });
})();
</script>
@endpush
@endif
