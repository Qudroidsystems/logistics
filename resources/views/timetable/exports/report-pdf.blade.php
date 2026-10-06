<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 16px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color:#1E293B; }
    .school-header { border:2px solid #1e3a5f; border-radius:6px; overflow:hidden; margin-bottom:10px; }
    .school-header table { width:100%; border-collapse:collapse; }
    .school-header .logo-cell { width:56px; text-align:center; vertical-align:middle; padding:6px; }
    .school-header .logo-cell img { width:46px; height:46px; border-radius:50%; object-fit:contain; }
    .school-header-top { background:#1e3a5f; color:#fff; }
    .school-header-top .name { font-size:15px; font-weight:700; text-transform:uppercase; text-align:center; }
    .school-header-top .addr { font-size:8px; opacity:.8; text-align:center; }
    .school-header-bottom { background:#2563eb; color:#fff; text-align:center; padding:6px; font-size:11px; font-weight:700; letter-spacing:1px; }
    .report-meta { font-size:9px; color:#64748B; text-align:center; margin-bottom:12px; }
    .summary-strip { display:table; width:100%; border:1px solid #CBD5E1; border-radius:6px; background:#F8FAFC; margin-bottom:12px; }
    .summary-strip .s-cell { display:table-cell; text-align:center; padding:8px 10px; border-right:1px solid #CBD5E1; }
    .summary-strip .s-cell:last-child { border-right:none; }
    .summary-strip .s-lbl { font-size:8px; color:#64748B; text-transform:uppercase; }
    .summary-strip .s-val { font-size:14px; font-weight:700; color:#1e3a5f; margin-top:2px; }
    table.rep-table { width:100%; border-collapse:collapse; font-size:9px; }
    table.rep-table th { background:#1e3a5f; color:#fff; padding:6px 6px; text-align:left; font-size:8.5px; text-transform:uppercase; }
    table.rep-table td { border:1px solid #E2E8F0; padding:5px 6px; }
    table.rep-table tbody tr:nth-child(odd)  { background:#ffffff; }
    table.rep-table tbody tr:nth-child(even) { background:#F8FAFC; }
    .empty-note { text-align:center; padding:30px; color:#94A3B8; font-size:11px; }
</style>
</head>
<body>
<div class="school-header">
    <table>
        <tr class="school-header-top">
            <td class="logo-cell">
                @if(!empty($schoolInfo?->logo_base64))
                    <img src="{{ $schoolInfo->logo_base64 }}" alt="Logo">
                @endif
            </td>
            <td>
                <div class="name">{{ $schoolInfo->school_name ?? 'School' }}</div>
                @if(!empty($schoolInfo?->school_address))
                    <div class="addr">{{ $schoolInfo->school_address }}</div>
                @endif
            </td>
            <td style="width:56px;"></td>
        </tr>
    </table>
    <div class="school-header-bottom">{{ $report->report_name }}</div>
</div>
<div class="report-meta">
    Session: {{ $report->session->session ?? '—' }}
    · Term: {{ $report->term->term ?? 'All Terms' }}
    · Generated: {{ $generatedAt }}
</div>
@if(!empty($summary))
<div class="summary-strip">
    @foreach($summary as $item)
        <div class="s-cell">
            <div class="s-lbl">{{ $item['label'] }}</div>
            <div class="s-val">{{ $item['value'] }}</div>
        </div>
    @endforeach
</div>
@endif
@if(empty($flatData))
    <div class="empty-note">No data available for this report and scope.</div>
@else
    <table class="rep-table">
        <thead>
            <tr>
                @foreach(array_keys($flatData[0]) as $header)
                    <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($flatData as $row)
                <tr>
                    @foreach($row as $val)
                        <td>{{ is_array($val) ? implode(', ', $val) : ($val ?? '—') }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
</body>
</html>