@php
    $statusText = $status ?? 'Status';
    $tone = strtolower($color ?? 'slate');

    $palette = [
        'green' => ['bg' => '#16a34a'],
        'red' => ['bg' => '#dc2626'],
        'yellow' => ['bg' => '#d97706'],
        'orange' => ['bg' => '#ea580c'],
        'gray' => ['bg' => '#4b5563'],
        'blue' => ['bg' => '#2563eb'],
        'maroon' => ['bg' => '#800000'],
        'slate' => ['bg' => '#475569'],
    ];

    $selected = $palette[$tone] ?? $palette['slate'];
@endphp

<span style="display: inline-block; background-color: {{ $selected['bg'] }}; color: #ffffff; border-radius: 999px; padding: 6px 12px; font-size: 12px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;">
    {{ $statusText }}
</span>
