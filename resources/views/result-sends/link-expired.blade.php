{{-- resources/views/result-sends/link-expired.blade.php — public page, no layout --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Report card link</title>
    <style>
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f1f5f9; color: #1e293b; display: flex; min-height: 100vh; align-items: center; justify-content: center; padding: 16px; }
        .box { background: #fff; border-radius: 14px; padding: 28px 24px; max-width: 420px; text-align: center; box-shadow: 0 10px 30px rgba(15,35,66,.08); }
        h1 { font-size: 20px; margin: 0 0 10px; color: #0f2342; }
        p { font-size: 14px; line-height: 1.6; margin: 0; color: #475569; }
    </style>
</head>
<body>
    <div class="box">
        <h1>{{ $reason === 'expired' ? 'This link has expired' : 'Report card not found' }}</h1>
        <p>{{ $reason === 'expired' ? 'For security, report card links only work for a limited time.' : 'This link is not valid.' }}
           Please log in to the school portal to view the result, or contact the school office.</p>
    </div>
</body>
</html>
