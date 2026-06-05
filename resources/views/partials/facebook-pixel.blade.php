@php
    $pixelEnabled = \App\Models\Setting::get('facebook_pixel_enabled', '0');
    $pixelId      = \App\Models\Setting::get('facebook_pixel_id', '');

    // Advanced Matching — hash customer data server-side for logged-in users
    $pixelUser = auth()->user();
    $pixelAdvanced = [];
    if ($pixelUser) {
        $pixelAdvanced['em'] = hash('sha256', strtolower(trim($pixelUser->email)));
        if (!empty($pixelUser->phone)) {
            $pixelAdvanced['ph'] = hash('sha256', preg_replace('/\D/', '', $pixelUser->phone));
        }
        $nameParts = explode(' ', trim($pixelUser->name), 2);
        if (!empty($nameParts[0])) {
            $pixelAdvanced['fn'] = hash('sha256', strtolower($nameParts[0]));
        }
        if (!empty($nameParts[1])) {
            $pixelAdvanced['ln'] = hash('sha256', strtolower($nameParts[1]));
        }
        $pixelAdvanced['external_id'] = hash('sha256', (string) $pixelUser->id);
    }
@endphp
@if($pixelEnabled && $pixelId)
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '{{ $pixelId }}'@if(!empty($pixelAdvanced)), {!! json_encode($pixelAdvanced) !!}@endif);
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id={{ $pixelId }}&ev=PageView&noscript=1"/></noscript>
@stack('pixel-events')
@endif
