<div class="dj-email-header" style="background-color:#3C0B17; background:linear-gradient(135deg,#601526,#3C0B17); padding:44px 24px 38px; text-align:center; border-top:3px solid #E8C39A;">
    {{--
        A raster PNG, not the SVG logo — Outlook desktop's Word-based
        rendering engine does not support SVG in emails at all and would
        show a broken-image icon. No PNG export of the full wordmark
        exists yet (only this circular mark + the SVG wordmark), so the
        logo mark is paired with a styled text wordmark for the same
        reason (renders correctly even with images blocked, which is the
        default in most mail clients) — together they read as "the logo"
        the same way the source SVG itself pairs an icon with wordmark
        text.
    --}}
    <img src="{{ asset('assets/branding/favicon-192.png') }}" width="64" height="64" alt="{{ __('Dar El Jamila') }}" style="display:block; margin:0 auto 16px; border-radius:50%; border:0;">
    <h1 style="margin:0; color:#F7EFE4; font-size:28px; font-weight:700; letter-spacing:2.5px; font-family: Georgia, 'Times New Roman', serif;">
        {{ __('Dar El Jamila') }}
    </h1>
    <div style="width:40px; height:1px; background:#E8C39A; margin:16px auto; opacity:.7;"></div>
    @isset($headerTagline)
        <p style="margin:0; color:#E8C39A; font-size:11.5px; letter-spacing:2px; text-transform:uppercase; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ $headerTagline }}</p>
    @endisset
</div>
