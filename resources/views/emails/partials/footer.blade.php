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

<div style="background:#F7EFE4; border-left:1px solid #F1E4D3; border-right:1px solid #F1E4D3; border-top:1px solid #EFE2CE; padding:30px 24px 26px; text-align:center;">
    @isset($securityNote)
        <p style="font-size:12px; color:#9C5064; line-height:1.7; margin:0 0 18px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ $securityNote }}
        </p>
    @endisset

    <p style="margin:0 0 4px;">
        <a href="{{ route('home') }}" class="dj-email-link" style="color:#601526; font-size:13px; text-decoration:none; font-weight:700; letter-spacing:.5px; font-family: Georgia, 'Times New Roman', serif;">{{ __('Dar El-Jamila') }}</a>
    </p>
    <p style="margin:0 0 16px;">
        <a href="{{ route('home') }}" class="dj-email-link" style="color:#9C5064; font-size:11.5px; text-decoration:none; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ __('emails.footer_visit_website') }}</a>
    </p>

    @if ($djSupportEmail)
        <p style="font-size:12px; color:#a67b83; margin:0 0 4px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ __('emails.footer_contact', ['email' => $djSupportEmail]) }}
        </p>
    @endif

    @if ($djWhatsapp)
        <p style="font-size:12px; color:#a67b83; margin:0 0 16px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;" dir="ltr">
            {{ __('emails.footer_whatsapp', ['number' => $djWhatsapp]) }}
        </p>
    @endif

    @if (! empty($djSocialLinks))
        <p style="margin:0 0 18px;">
            @foreach ($djSocialLinks as $djSocialLabel => $djSocialUrl)
                <a href="{{ $djSocialUrl }}" class="dj-email-link" style="display:inline-block; color:#601526; font-size:11px; font-weight:700; letter-spacing:.5px; text-transform:uppercase; text-decoration:none; margin:0 8px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ $djSocialLabel }}</a>
                @if (! $loop->last)<span style="color:#EFE2CE;">&middot;</span>@endif
            @endforeach
        </p>
    @endif

    <div style="width:36px; height:1px; background:#E8C39A; margin:0 auto 18px; opacity:.6;"></div>

    <p style="font-size:11px; color:#a67b83; margin:0 0 6px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        &copy; {{ date('Y') }} {{ __('emails.footer_copyright') }}
    </p>

    <p style="font-size:10.5px; color:#c2a1a6; margin:0; line-height:1.6; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        {{ __('emails.footer_disclaimer') }}
    </p>
</div>
