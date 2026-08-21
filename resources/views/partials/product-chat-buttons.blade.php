{{-- WhatsApp / Messenger enquiry buttons — configured in Store Settings → Product Chat Buttons --}}
@if($chatLinks['enabled'])
    <div class="w-full mt-3">
        <p class="text-xs text-gray-400 mb-2">{{ __('front.chat_prompt') }}</p>

        <div class="flex flex-col sm:flex-row gap-2">
            @if($chatLinks['whatsapp'])
                <a href="{{ $chatLinks['whatsapp'] }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="flex-1 flex items-center justify-center gap-2 border-2 border-[#25D366] text-[#128C7E] hover:bg-[#25D366] hover:text-white font-semibold py-3 rounded-xl transition-colors duration-150 text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a12.8 12.8 0 0 0-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.247-.694.247-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884a9.82 9.82 0 0 1 6.988 2.896 9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                    </svg>
                    {{ __('front.order_on_whatsapp') }}
                </a>
            @endif

            @if($chatLinks['messenger'])
                {{-- Messenger has no pre-fill parameter, so the details are copied
                     to the clipboard for the customer to paste. --}}
                <a href="{{ $chatLinks['messenger'] }}"
                   id="messenger-chat-btn"
                   data-chat-message="{{ $chatLinks['message'] }}"
                   target="_blank"
                   rel="noopener noreferrer"
                   class="flex-1 flex items-center justify-center gap-2 border-2 border-[#0084FF] text-[#0064BF] hover:bg-[#0084FF] hover:text-white font-semibold py-3 rounded-xl transition-colors duration-150 text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 0C5.373 0 0 4.974 0 11.111c0 3.498 1.744 6.614 4.469 8.652V24l4.088-2.242c1.092.301 2.246.464 3.443.464 6.627 0 12-4.974 12-11.111C24 4.974 18.627 0 12 0Zm1.191 14.963-3.055-3.26-5.963 3.26L10.732 8l3.131 3.26L19.75 8l-6.559 6.963Z"/>
                    </svg>
                    {{ __('front.order_on_messenger') }}
                </a>
            @endif
        </div>

        @if($chatLinks['messenger'])
            <p id="messenger-copy-note"
               class="hidden mt-2 text-xs text-emerald-600 font-medium">{{ __('front.messenger_details_copied') }}</p>
        @endif
    </div>

    @if($chatLinks['messenger'])
        @push('scripts')
        <script>
        (function () {
            var btn  = document.getElementById('messenger-chat-btn');
            var note = document.getElementById('messenger-copy-note');

            if (!btn) return;

            btn.addEventListener('click', function () {
                var text = btn.getAttribute('data-chat-message') || '';

                // navigator.clipboard needs a secure context; fall back on plain HTTP.
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(reveal, fallback);
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
                    try { document.execCommand('copy'); reveal(); } catch (e) { /* clipboard unavailable */ }
                    document.body.removeChild(field);
                }

                function reveal() {
                    if (note) note.classList.remove('hidden');
                }
            });
        })();
        </script>
        @endpush
    @endif
@endif
