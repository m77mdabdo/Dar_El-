<div class="dj-email-header" style="background-color:#3C0B17; background:linear-gradient(135deg,#601526,#3C0B17); padding:40px 24px 34px; text-align:center; border-top:3px solid #E8C39A;">
    <p style="margin:0 0 10px; color:#E8C39A; font-size:11px; font-weight:600; letter-spacing:3px; text-transform:uppercase; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; opacity:.85;">
        &#10022;
    </p>
    <h1 style="margin:0; color:#F7EFE4; font-size:26px; font-weight:700; letter-spacing:2px; font-family: Georgia, 'Times New Roman', serif;">
        {{ __('Dar El-Jamila') }}
    </h1>
    <div style="width:36px; height:1px; background:#E8C39A; margin:14px auto; opacity:.6;"></div>
    @isset($headerTagline)
        <p style="margin:0; color:#E8C39A; font-size:11.5px; letter-spacing:2px; text-transform:uppercase; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ $headerTagline }}</p>
    @endisset
</div>
