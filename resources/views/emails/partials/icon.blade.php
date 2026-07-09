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
    ];

    $djBadgeSize = 56;
@endphp

<div style="width:{{ $djBadgeSize }}px; height:{{ $djBadgeSize }}px; margin:0 auto 18px; border-radius:999px; background:linear-gradient(135deg,#601526,#3C0B17); display:table;">
    <div style="display:table-cell; vertical-align:middle; text-align:center;">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#E8C39A" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;">
            <path d="{{ $djIconPaths[$icon] ?? $djIconPaths['shield'] }}"/>
        </svg>
    </div>
</div>
