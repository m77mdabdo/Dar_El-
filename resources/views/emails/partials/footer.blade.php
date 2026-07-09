@php
    $djSupportEmail = \App\Models\Setting::get('support_email');
    $djWhatsapp = \App\Models\Setting::get('whatsapp_number');
    $djFacebookUrl = \App\Models\Setting::get('facebook_url');
    $djInstagramUrl = \App\Models\Setting::get('instagram_url');
@endphp

<div style="padding:26px 24px 0; text-align:center;">
    @isset($securityNote)
        <p style="font-size:12px; color:#9C5064; line-height:1.7; margin:0 0 16px; font-family:sans-serif;">
            {{ $securityNote }}
        </p>
    @endisset

    <p style="margin:0 0 10px;">
        <a href="{{ route('home') }}" style="color:#601526; font-size:12px; text-decoration:none; font-weight:600; font-family:sans-serif;">{{ __('Dar El-Jamila') }}</a>
    </p>

    @if ($djSupportEmail)
        <p style="font-size:12px; color:#a67b83; margin:0 0 4px; font-family:sans-serif;">
            {{ __('emails.footer_contact', ['email' => $djSupportEmail]) }}
        </p>
    @endif

    @if ($djWhatsapp)
        <p style="font-size:12px; color:#a67b83; margin:0 0 10px; font-family:sans-serif;">
            {{ $djWhatsapp }}
        </p>
    @endif

    @if ($djFacebookUrl || $djInstagramUrl)
        <p style="margin:0 0 14px;">
            @if ($djFacebookUrl)
                <a href="{{ $djFacebookUrl }}" style="color:#601526; font-size:12px; text-decoration:none; margin:0 6px; font-family:sans-serif;">Facebook</a>
            @endif
            @if ($djInstagramUrl)
                <a href="{{ $djInstagramUrl }}" style="color:#601526; font-size:12px; text-decoration:none; margin:0 6px; font-family:sans-serif;">Instagram</a>
            @endif
        </p>
    @endif

    <p style="font-size:11.5px; color:#a67b83; margin:0 0 6px; font-family:sans-serif;">
        &copy; {{ date('Y') }} {{ __('emails.footer_copyright') }}
    </p>

    <p style="font-size:10.5px; color:#c2a1a6; margin:0 0 28px; line-height:1.6; font-family:sans-serif;">
        {{ __('emails.footer_disclaimer') }}
    </p>
</div>
