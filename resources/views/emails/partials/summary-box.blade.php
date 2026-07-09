{{--
    Premium order-summary box (Subtotal / Discount / Shipping / Tax / Grand
    Total). Expects $djRows: array of ['label' => string, 'value' => string],
    and $djTotal: ['label' => string, 'value' => string].
--}}
@php
    $djEnd = app()->getLocale() === 'ar' ? 'left' : 'right';
@endphp
<div style="background:#F7EFE4; border:1px solid #EFE2CE; border-radius:14px; padding:20px 22px; margin:8px 0 26px;">
    <table style="width:100%; border-collapse:collapse; font-size:13px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        @foreach ($djRows as $djRow)
            <tr>
                <td style="padding:5px 0; color:#9C5064;">{{ $djRow['label'] }}</td>
                <td style="padding:5px 0; text-align:{{ $djEnd }}; color:#5a4448;">{{ $djRow['value'] }}</td>
            </tr>
        @endforeach
        <tr>
            <td style="padding-top:12px; border-top:1px solid #EFE2CE;"></td>
            <td style="padding-top:12px; border-top:1px solid #EFE2CE;"></td>
        </tr>
        <tr>
            <td style="padding:4px 0; font-size:17px; font-weight:700; color:#601526; font-family: Georgia, 'Times New Roman', serif;">{{ $djTotal['label'] }}</td>
            <td style="padding:4px 0; text-align:{{ $djEnd }}; font-size:17px; font-weight:700; color:#601526; font-family: Georgia, 'Times New Roman', serif;">{{ $djTotal['value'] }}</td>
        </tr>
    </table>
</div>
