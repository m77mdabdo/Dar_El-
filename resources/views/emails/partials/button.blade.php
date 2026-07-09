@php
    $djArrow = app()->getLocale() === 'ar' ? '&larr;' : '&rarr;';
@endphp
<div style="text-align:center; margin:34px 0 8px;">
    <!--[if mso]>
    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $href }}" style="height:50px;v-text-anchor:middle;width:240px;" arcsize="50%" fillcolor="#601526" stroke="f">
    <center style="color:#F7EFE4;font-family:sans-serif;font-size:13px;font-weight:700;letter-spacing:1px;">{{ strtoupper($label) }}</center>
    </v:roundrect>
    <![endif]-->
    <!--[if !mso]><!-->
    <a href="{{ $href }}" class="dj-email-btn" style="display:inline-block; background-color:#601526; background:linear-gradient(135deg,#601526,#3C0B17); color:#F7EFE4; font-weight:700; font-size:13px; letter-spacing:1px; text-transform:uppercase; padding:16px 42px; border-radius:999px; text-decoration:none; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; box-shadow:0 14px 28px -10px rgba(96,21,38,0.5);">
        {{ $label }}&nbsp;&nbsp;{!! $djArrow !!}
    </a>
    <!--<![endif]-->
</div>
