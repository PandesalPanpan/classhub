@php
    $buttonColor = $background ?? '#800000';
@endphp

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 24px 0;">
    <tr>
        <td align="center">
            <a href="{{ $url }}" class="email-button" style="display: inline-block; background-color: {{ $buttonColor }}; color: #ffffff; text-decoration: none; border-radius: 6px; padding: 12px 32px; font-size: 15px; font-weight: 700;">
                {{ $label }}
            </a>
        </td>
    </tr>
    <tr>
        <td align="center" style="padding-top: 10px;">
            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #6b7280;">
                If the button above doesn't work, copy and paste this link into your browser:<br>
                <a href="{{ $url }}" style="color: #800000; word-break: break-all;">{{ $url }}</a>
            </p>
        </td>
    </tr>
</table>
