{{--
    Small bordered info card with an icon + title header and label/value
    rows below. Used for "Customer", "Shipping Address", "Payment Method",
    order metadata, and admin notification detail blocks.

    Expects: $djIcon (key below), $djTitle (string), $djRows (array of
    ['label' => string, 'value' => string]).
--}}
@php
    $djInfoIconPaths = [
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM5 20a7 7 0 0 1 14 0',
        'location' => 'M12 21s7-6.1 7-11.5A7 7 0 0 0 5 9.5C5 14.9 12 21 12 21zM12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
        'wallet' => 'M3 7a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v2h1a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V7zM16 13.5a1 1 0 1 0 2 0 1 1 0 0 0-2 0Z',
        'document' => 'M7 3h7l4 4v14H7zM14 3v4h4M9 12h6M9 16h6',
        'tag' => 'M20.5 12.5 12 21l-9-9V4h8l9.5 8.5z M7 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2z',
        'envelope' => 'M4 6h16v12H4zM4 6l8 7 8-7',
        'shield' => 'M12 3l7 3v5c0 4.5-3 8.4-7 9.5-4-1.1-7-5-7-9.5V6l7-3z',
    ];
@endphp
<div style="background:#ffffff; border:1px solid #EFE2CE; border-radius:14px; padding:16px 18px; margin-bottom:12px;">
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
        <tr>
            <td style="width:26px; vertical-align:middle;">
                <div style="width:26px; height:26px; border-radius:999px; background:#F7EFE4; display:table;">
                    <div style="display:table-cell; vertical-align:middle; text-align:center;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#601526" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $djInfoIconPaths[$djIcon] ?? $djInfoIconPaths['document'] }}"/>
                        </svg>
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
