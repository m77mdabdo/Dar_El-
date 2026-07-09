<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Dar El-Jamila') }}</title>
    <style>
        .dj-email-btn:hover { background:#D4A574 !important; color:#3C0B17 !important; }
        @media only screen and (max-width:480px) {
            .dj-email-card { padding:26px 20px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#F7EFE4; font-family: sans-serif; color:#2A1015;">
    <div style="max-width:600px; margin:0 auto; padding:32px 16px;">
        <div style="border-radius:20px; overflow:hidden; box-shadow:0 8px 28px rgba(60,11,23,0.10);">
            @include('emails.partials.header')

            <div class="dj-email-card" style="background:#ffffff; padding:36px 30px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; border-left:1px solid #EFE2CE; border-right:1px solid #EFE2CE;">
                @isset($icon)
                    @include('emails.partials.icon', ['icon' => $icon])
                @endisset

                @yield('content')
            </div>
        </div>

        @include('emails.partials.footer')
    </div>
</body>
</html>
