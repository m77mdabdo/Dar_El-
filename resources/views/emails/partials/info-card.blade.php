{{--
    Small bordered info card with an icon + title header and label/value
    rows below. Used for "Customer", "Shipping Address", "Payment Method",
    order metadata, and admin notification detail blocks.

    Expects: $djIcon (key below), $djTitle (string), $djRows (array of
    ['label' => string, 'value' => string]).
--}}
@php
    // Plain glyphs, not inline <svg> — see emails/partials/icon.blade.php
    // for why (Gmail strips inline SVG, Outlook desktop doesn't render it;
    // both showed a blank badge here for every info card — "Shipping To",
    // "Customer", "Payment Method" — until this changed).
    $djInfoIconGlyphs = [
        'user' => '&#128100;',
        'location' => '&#128205;',
        'wallet' => '&#128179;',
        'document' => '&#128196;',
        'tag' => '&#127991;&#65039;',
        'envelope' => '&#9993;&#65039;',
        'shield' => '&#128274;',
    ];
@endphp
<div style="background:#ffffff; border:1px solid #EFE2CE; border-radius:14px; padding:16px 18px; margin-bottom:12px;">
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
        <tr>
            <td style="width:26px; vertical-align:middle;">
                <div style="width:26px; height:26px; border-radius:999px; background:#F7EFE4; display:table;">
                    <div style="display:table-cell; vertical-align:middle; text-align:center; font-size:13px; line-height:1;">
                        {!! $djInfoIconGlyphs[$djIcon] ?? $djInfoIconGlyphs['document'] !!}
                    </div>
                </div>
            </td>
            <td style="padding:0 10px; vertical-align:middle;">
                <span style="font-size:11px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:#601526; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ $djTitle }}</span>
            </td>
        </tr>
    </table>
    <table style="width:100%; border-collapse:collapse; font-size:12.5px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        @foreach ($djRows as $djRow)
            <tr>
                <td style="padding:3px 0; color:#9C5064; width:38%; vertical-align:top;">{{ $djRow['label'] }}</td>
                <td style="padding:3px 0; color:#2A1015;">{{ $djRow['value'] }}</td>
            </tr>
        @endforeach
    </table>
</div>
