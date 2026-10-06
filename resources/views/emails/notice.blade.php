{{-- resources/views/emails/notice.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $subjectLine }}</title></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1e293b">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px">
<tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#0f2342;padding:20px 24px;color:#ffffff">
            <div style="font-size:18px;font-weight:bold">{{ config('app.name') }}</div>
        </td></tr>
        <tr><td style="padding:24px">
            <h2 style="margin:0 0 16px;font-size:18px;color:#0f2342">{{ $subjectLine }}</h2>
            <div style="font-size:14px;line-height:1.6">{!! nl2br(e($text)) !!}</div>
        </td></tr>
        <tr><td style="padding:14px 24px;background:#f8fafc;font-size:11px;color:#64748b">
            You are receiving this because you have an account with {{ config('app.name') }}.
        </td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
