@php
    $left = $accent ?? '#2563eb';
    $bg = $background ?? '#eff6ff';
@endphp

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 24px 0;">
    <tr>
        <td style="background-color: {{ $bg }}; border-left: 4px solid {{ $left }}; border-radius: 0 6px 6px 0; padding: 16px;">
            @if (! empty($title ?? null))
                <p style="margin: 0 0 8px 0; color: #111827; font-size: 14px; font-weight: 700;">{{ $title }}</p>
            @endif
            <div style="margin: 0; color: #374151; font-size: 14px; line-height: 1.55;">
                {!! $slot ?? ($content ?? '') !!}
            </div>
        </td>
    </tr>
</table>
