@if (! empty($rows ?? []))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width: 100%; border-collapse: collapse; margin-top: 16px; margin-bottom: 4px;">
        @foreach ($rows as $label => $value)
            <tr>
                <td style="width: 35%; padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-size: 13px; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; vertical-align: top;">
                    {{ $label }}
                </td>
                <td style="padding: 10px 0; border-bottom: 1px solid #e5e7eb; color: #111827; font-size: 15px; line-height: 1.45; font-weight: 500; vertical-align: top;">
                    {!! $value !!}
                </td>
            </tr>
        @endforeach
    </table>
@endif
