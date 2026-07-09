@php
    $djIconPaths = [
        'shield' => 'M12 3l7 3v5c0 4.5-3 8.4-7 9.5-4-1.1-7-5-7-9.5V6l7-3z',
        'shield-device' => 'M12 3l6.5 2.8v4.6c0 4.1-2.7 7.7-6.5 8.7-3.8-1-6.5-4.6-6.5-8.7V5.8L12 3zM9 10.5h6v3.5H9z',
        'bag' => 'M6 7h12l1 13H5L6 7zM9 7a3 3 0 0 1 6 0',
        'heart' => 'M12 20s-7-4.4-9.3-8.8C1.3 8 2.6 4.8 5.7 4.1 7.8 3.6 9.9 4.5 12 7c2.1-2.5 4.2-3.4 6.3-2.9 3.1.7 4.4 3.9 3 7.1C19 15.6 12 20 12 20z',
        'credit-card' => 'M3 6h18v12H3zM3 10h18',
        'check-circle' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zm-1.5 12.5-3.5-3.5 1.4-1.4 2.1 2.1 5.1-5.1 1.4 1.4z',
        'warning-triangle' => 'M12 4l9.5 16H2.5L12 4zM12 10v4.5M12 17h.01',
        'star' => 'M12 3l2.6 5.9 6.4.6-4.8 4.3 1.4 6.3L12 17l-5.6 3.1 1.4-6.3-4.8-4.3 6.4-.6z',
        'document' => 'M7 3h7l4 4v14H7zM14 3v4h4M9 12h6M9 16h6',
        'envelope' => 'M4 6h16v12H4zM4 6l8 7 8-7',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM5 20a7 7 0 0 1 14 0',
        'dashboard' => 'M4 13h6V4H4v9zM14 20h6v-9h-6v9zM14 4v4h6V4h-6zM4 20h6v-4H4v4z',
        'box' => 'M3 8l9-5 9 5-9 5-9-5zM3 8v8l9 5 9-5V8M12 13v8',
        'wallet' => 'M3 7a2 2 0 0 1 2-2h11a2 2 0 0 1 2 2v2h1a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1H5a2 2 0 0 1-2-2V7zM16 13.5a1 1 0 1 0 2 0 1 1 0 0 0-2 0Z',
        'chat' => 'M4 5h16v11H8l-4 4V5z',
        'truck' => 'M3 7h11v9H3zM14 10h4l3 3v3h-7zM7 19.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM18 19.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z',
    ];

    $djBadgeSize = 76;
@endphp

<div style="text-align:center; margin:0 0 26px;">
    <div style="width:{{ $djBadgeSize }}px; height:{{ $djBadgeSize }}px; margin:0 auto; border-radius:999px; background:#F7EFE4; border:1px solid #EFE2CE; display:table;">
        <div style="display:table-cell; vertical-align:middle; text-align:center;">
            <div style="width:56px; height:56px; margin:0 auto; border-radius:999px; background-color:#3C0B17; background:linear-gradient(135deg,#601526,#3C0B17); display:table; box-shadow:0 10px 20px -8px rgba(60,11,23,0.45);">
                <div style="display:table-cell; vertical-align:middle; text-align:center;">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#E8C39A" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;">
                        <path d="{{ $djIconPaths[$icon] ?? $djIconPaths['shield'] }}"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>
