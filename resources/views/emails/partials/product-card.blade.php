{{--
    Reusable premium product line item, shared by order confirmation, cart
    reminder, and wishlist reminder emails.

    Expects $djRows: array of ['image' => ?string, 'name' => string,
    'meta' => string[] (e.g. "Size: L", "SKU: ABC-1"), 'price' => string
    (already formatted, e.g. "2,500 EGP")].
--}}
@foreach ($djRows as $djRow)
    <div style="background:#FBF6EE; border:1px solid #EFE2CE; border-radius:14px; padding:14px; margin-bottom:12px; box-shadow:0 6px 16px -10px rgba(60,11,23,0.18);">
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="width:64px; vertical-align:top;">
                    @if ($djRow['image'] ?? null)
                        <img src="{{ $djRow['image'] }}" width="58" height="58" style="border-radius:10px; object-fit:cover; display:block;" alt="">
                    @else
                        <div style="width:58px; height:58px; border-radius:10px; background:#F1E4D3;"></div>
                    @endif
                </td>
                <td style="padding:0 14px; vertical-align:top;">
                    <div style="font-size:14px; font-weight:700; color:#2A1015; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ $djRow['name'] }}</div>
                    @foreach ($djRow['meta'] ?? [] as $djMetaLine)
                        <div style="font-size:11.5px; color:#9C5064; margin-top:3px; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ $djMetaLine }}</div>
                    @endforeach
                </td>
                <td style="vertical-align:top; text-align:{{ app()->getLocale() === 'ar' ? 'left' : 'right' }}; white-space:nowrap;">
                    <span style="font-size:14px; font-weight:700; color:#601526; font-family: -apple-system, 'Helvetica Neue', Helvetica, Arial, sans-serif;">{{ $djRow['price'] }}</span>
                </td>
            </tr>
        </table>
    </div>
@endforeach
