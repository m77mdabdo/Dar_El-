@php
    // Plain glyphs, not inline <svg> — Gmail's webmail rendering and
    // Outlook desktop's Word-based engine both fail to render inline SVG
    // (Gmail strips it entirely, Outlook shows nothing/breaks layout), so
    // every one of these icons was previously invisible in two of the
    // three clients this project needs to support. Text glyphs render
    // everywhere with zero image/SVG dependency.
    $djIconGlyphs = [
        'shield' => '&#128274;',
        'shield-device' => '&#128274;',
        'bag' => '&#128717;',
        'heart' => '&#10084;&#65039;',
        'credit-card' => '&#128179;',
        'check-circle' => '&#9989;',
        'warning-triangle' => '&#9888;&#65039;',
        'star' => '&#11088;',
        'document' => '&#128196;',
        'envelope' => '&#9993;&#65039;',
        'user' => '&#128100;',
        'dashboard' => '&#128202;',
        'box' => '&#128230;',
        'wallet' => '&#128179;',
        'chat' => '&#128172;',
        'truck' => '&#128666;',
    ];

    $djBadgeSize = 80;
@endphp

<div style="text-align:center; margin:0 0 28px;">
    <div style="width:{{ $djBadgeSize }}px; height:{{ $djBadgeSize }}px; margin:0 auto; border-radius:999px; background:#F7EFE4; border:1px solid #EFE2CE; display:table;">
        <div style="display:table-cell; vertical-align:middle; text-align:center;">
            <div style="width:58px; height:58px; margin:0 auto; border-radius:999px; background-color:#3C0B17; background:linear-gradient(135deg,#601526,#3C0B17); display:table; box-shadow:0 12px 22px -8px rgba(60,11,23,0.5);">
                <div style="display:table-cell; vertical-align:middle; text-align:center; font-size:24px; line-height:1;">
                    {!! $djIconGlyphs[$icon] ?? $djIconGlyphs['shield'] !!}
                </div>
            </div>
        </div>
    </div>
</div>
