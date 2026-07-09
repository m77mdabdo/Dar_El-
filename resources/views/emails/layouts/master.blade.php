<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Dar El-Jamila') }}</title>
    <style>
        .dj-email-btn:hover { background:#4a1a2c !important; box-shadow:0 10px 24px -8px rgba(60,11,23,0.45) !important; }
        .dj-email-link:hover { opacity:.75; }
        @media only screen and (max-width:480px) {
            .dj-email-card { padding:30px 22px !important; }
            .dj-email-header { padding:32px 20px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background:#F1E4D3; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif; color:#2A1015;">
    <div style="max-width:600px; margin:0 auto; padding:40px 16px;">
        <div style="border-radius:22px; overflow:hidden; box-shadow:0 22px 48px -20px rgba(60,11,23,0.28); border:1px solid rgba(60,11,23,0.06);">
            @include('emails.partials.header')

            <div class="dj-email-card" style="background:#ffffff; padding:44px 40px; text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }}; border-left:1px solid #F1E4D3; border-right:1px solid #F1E4D3;">
                @isset($icon)
                    @include('emails.partials.icon', ['icon' => $icon])
                @endisset

                @yield('content')
            </div>

            @include('emails.partials.footer')
        </div>

        <p style="text-align:center; font-size:10.5px; letter-spacing:.4px; color:#B79A85; margin:22px 0 0; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
            {{ __('Dar El-Jamila') }} &middot; {{ __('invoice.tagline') }}
        </p>
    </div>
</body>
</html>
