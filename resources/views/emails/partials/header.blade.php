<div class="dj-email-header" style="background-color:#3C0B17; background:linear-gradient(135deg,#601526,#3C0B17); padding:40px 24px 34px; text-align:center; border-top:3px solid #E8C39A;">
    {{--
        A raster PNG, not the SVG logo — Outlook desktop's Word-based
        rendering engine does not support SVG in emails at all and would
        show a broken-image icon. The text wordmark below is kept as the
        primary brand mark for the same reason (renders correctly even
        with images blocked, which is the default in most mail clients).
    --}}
    <img src="{{ asset('assets/branding/favicon-192.png') }}" width="52" height="52" alt="" style="display:block; margin:0 auto 14px; border-radius:50%; border:0;">
    <h1 style="margin:0; color:#F7EFE4; font-size:26px; font-weight:700; letter-spacing:2px; font-family: Georgia, 'Times New Roman', serif;">
        {{ __('Dar El Jamila') }}
    </h1>
    <div style="width:36px; height:1px; background:#E8C39A; margin:14px auto; opacity:.6;"></div>
    @isset($headerTagline)
        <p style="margin:0; color:#E8C39A; font-size:11.5px; letter-spacing:2px; text-transform:uppercase; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ $headerTagline }}</p>
    @endisset
</div>
