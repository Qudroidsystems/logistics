{{-- resources/views/emails/timetable-notification.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Notification</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 24px;
            text-align: center;
            color: white;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        .content {
            padding: 24px;
        }
        .teacher-info {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .teacher-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
        }
        .teacher-details h3 {
            margin: 0;
            font-size: 18px;
        }
        .teacher-details p {
            margin: 4px 0 0;
            color: #6c757d;
            font-size: 13px;
        }
        .slot-table {
            width: 100%;
            border-collapse: collapse;
            margin: 16px 0;
        }
        .slot-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }
        .slot-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .badge-primary {
            background: #e3f2fd;
            color: #1976d2;
        }
        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .footer {
            background: #f8f9fa;
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 24px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>📋 {{ match($data['type'] ?? 'daily_summary') {
                    'daily_summary' => 'Today\'s Timetable',
                    'weekly_preview' => 'Weekly Timetable Preview',
                    'change_alert' => 'Timetable Change Alert',
                    default => 'Timetable Notification'
                } }}</h1>
                <p>{{ $data['session'] ?? '' }} | {{ $data['term'] ?? '' }}</p>
            </div>

            <div class="content">
                <div class="teacher-info">
                    @if($data['teacher_picture'] ?? false)
                        <img src="{{ $data['teacher_picture'] }}" alt="Teacher" class="teacher-avatar">
                    @else
                        <div class="teacher-avatar" style="background: #667eea; display: flex; align-items: center; justify-content: center; color: white; font-size: 28px;">
                            👨‍🏫
                        </div>
                    @endif
                    <div class="teacher-details">
                        <h3>Hello, {{ $data['teacher'] ?? 'Teacher' }}!</h3>
                        <p>Class: {{ $data['class'] ?? 'N/A' }}</p>
                    </div>
                </div>

                @if(count($data['slots'] ?? []) > 0)
                    <table class="slot-table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Period</th>
                                <th>Time</th>
                                <th>Subject</th>
                                @if($data['slots'][0]['room'] ?? false)<th>Room</th>@endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['slots'] as $slot)
                                <tr>
                                    <td>
                                        <span class="badge badge-primary">{{ $slot['day'] }}</span>
                                    </td>
                                    <td>{{ $slot['period'] }}</td>
                                    <td>{{ $slot['time'] }}</td>
                                    <td>
                                        <span class="badge badge-success">{{ $slot['subject'] }}</span>
                                    </td>
                                    @if($data['slots'][0]['room'] ?? false)
                                        <td>{{ $slot['room'] ?? '—' }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="text-align: center; color: #6c757d;">No classes scheduled for this period.</p>
                @endif

                <div style="text-align: center;">
                    <a href="{{ url('/timetable/teacher') }}" class="btn">View Full Timetable →</a>
                </div>
            </div>

            <div class="footer">
                <p>This is an automated notification from your school's timetable system.</p>
                <p>Generated on {{ $data['generated'] ?? now()->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>
</body>
</html>
