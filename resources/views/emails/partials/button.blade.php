<div style="text-align:center; margin:30px 0 14px;">
    <!--[if mso]>
    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $href }}" style="height:48px;v-text-anchor:middle;width:220px;" arcsize="50%" fillcolor="#601526" stroke="f">
    <center style="color:#E8C39A;font-family:sans-serif;font-size:14px;font-weight:700;">{{ $label }}</center>
    </v:roundrect>
    <![endif]-->
    <!--[if !mso]><!-->
    <a href="{{ $href }}" class="dj-email-btn" style="display:inline-block; background:#601526; color:#E8C39A; font-weight:700; font-size:14px; padding:15px 38px; border-radius:999px; text-decoration:none; font-family:sans-serif; box-shadow:0 4px 14px rgba(96,21,38,0.28);">
        {{ $label }}
    </a>
    <!--<![endif]-->
</div>
