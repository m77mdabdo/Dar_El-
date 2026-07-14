@php
    $djSupportEmail = \App\Models\Setting::get('support_email');
    $djWhatsapp = \App\Models\Setting::get('whatsapp_number');
    $djFacebookUrl = \App\Models\Setting::get('facebook_url');
    $djInstagramUrl = \App\Models\Setting::get('instagram_url');
    $djTiktokUrl = \App\Models\Setting::get('tiktok_url');
    $djSocialLinks = array_filter([
        'Facebook' => $djFacebookUrl,
        'Instagram' => $djInstagramUrl,
        'TikTok' => $djTiktokUrl,
    ]);
@endphp

<div style="background:#F7EFE4; border-left:1px solid #F1E4D3; border-right:1px solid #F1E4D3; border-top:1px solid #EFE2CE; padding:38px 28px 32px; text-align:center;">
    @isset($securityNote)
        <p style="font-size:12px; color:#8a3f4d; line-height:1.7; margin:0 0 20px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ $securityNote }}
        </p>
    @endisset

    {{-- Brand block: small logo + name + fixed tagline (kept in Latin
         script in both locales, matching how the source logo asset
         itself renders "DAR EL JAMILA · COUTURE ABAYAS" — a brand mark,
         not translatable UI copy). --}}
    <img src="{{ asset('assets/branding/favicon-192.png') }}" width="36" height="36" alt="" style="display:block; margin:0 auto 12px; border-radius:50%; border:0;">
    <p style="margin:0 0 3px;">
        <a href="{{ route('home') }}" class="dj-email-link" style="color:#601526; font-size:15px; text-decoration:none; font-weight:700; letter-spacing:.5px; font-family: Georgia, 'Times New Roman', serif;">{{ __('Dar El Jamila') }}</a>
    </p>
    <p style="margin:0 0 14px; font-size:10.5px; letter-spacing:1.5px; text-transform:uppercase; color:#9C5064; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        Luxury Abayas &amp; Couture
    </p>

    <p style="margin:0 0 18px;">
        <a href="{{ route('home') }}" class="dj-email-link" style="color:#601526; font-size:12px; text-decoration:none; font-weight:600; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ __('emails.footer_visit_website') }}</a>
        <br>
        <a href="{{ route('home') }}" class="dj-email-link" dir="ltr" style="display:inline-block; margin-top:2px; color:#9C5064; font-size:11px; text-decoration:none; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">https://dareljamila.com</a>
    </p>

    <div style="width:36px; height:1px; background:#E8C39A; margin:0 auto 18px; opacity:.6;"></div>

    @if ($djSupportEmail)
        <p style="font-size:12.5px; color:#7a4a52; margin:0 0 5px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ __('emails.footer_contact', ['email' => $djSupportEmail]) }}
        </p>
    @endif

    @if ($djWhatsapp)
        <p style="font-size:12.5px; color:#7a4a52; margin:0 0 16px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;" dir="ltr">
            {{ __('emails.footer_whatsapp', ['number' => $djWhatsapp]) }}
        </p>
    @endif

    @if (! empty($djSocialLinks))
        <p style="margin:0 0 20px;">
            @foreach ($djSocialLinks as $djSocialLabel => $djSocialUrl)
                <a href="{{ $djSocialUrl }}" class="dj-email-link" style="display:inline-block; color:#601526; font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; text-decoration:none; margin:0 8px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ $djSocialLabel }}</a>
                @if (! $loop->last)<span style="color:#c9a97e;">&middot;</span>@endif
            @endforeach
        </p>
    @endif

    <div style="width:36px; height:1px; background:#E8C39A; margin:0 auto 18px; opacity:.6;"></div>

    <p style="font-size:11px; color:#7a4a52; margin:0 0 6px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        &copy; {{ date('Y') }} {{ __('emails.footer_copyright') }}
    </p>

    <p style="font-size:10.5px; color:#96707a; margin:0; line-height:1.6; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        {{ __('emails.footer_disclaimer') }}
    </p>
</div>
