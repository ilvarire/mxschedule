<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Pass</title>
    <style>
        @page {
            margin: 0;
            size: 105mm 148.5mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            width: 105mm;
            height: 148.5mm;
            margin: 0;
            padding: 0;
        }

        body {
            width: 105mm;
            height: 148.5mm;
            margin: 0;
            padding: 5mm;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            line-height: 1.25;
        }

        .pass-container {
            width: 95mm;
            height: 138.5mm;
            overflow: hidden;
            border: 2px solid #1e3a5f;
            border-radius: 6px;
            padding: 3.5mm;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 2.5mm;
            margin-bottom: 2.5mm;
        }

        .header h1 {
            font-size: 14px;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header h2 {
            font-size: 9px;
            color: #555;
            margin-top: 1mm;
            line-height: 1.25;
        }

        .badge {
            display: inline-block;
            background: #1e3a5f;
            color: #fff;
            padding: 1.5mm 3mm;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            margin-top: 1.5mm;
        }

        .detail-row {
            width: 100%;
            padding: 1.5mm 0;
            border-bottom: 1px dotted #ccc;
        }

        .detail-label {
            font-size: 7px;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5mm;
        }

        .detail-value {
            font-size: 10px;
            font-weight: bold;
            color: #111;
            word-wrap: break-word;
        }

        .highlight {
            background: #f0f7ff;
            padding: 2mm;
            border-radius: 4px;
            margin: 2mm 0 1.5mm;
        }

        .seat-table {
            width: 100%;
            border-collapse: collapse;
        }

        .seat-table td {
            width: 50%;
            vertical-align: top;
        }

        .seat-table td:last-child {
            text-align: right;
        }

        .highlight .detail-value {
            color: #1e3a5f;
            font-size: 13px;
        }

        .qr-section {
            text-align: center;
            margin-top: 2mm;
            padding-top: 2mm;
            border-top: 2px solid #1e3a5f;
        }

        .qr-code {
            width: 39mm;
            height: 39mm;
            margin: 0 auto;
        }

        .qr-code svg {
            width: 39mm;
            height: 39mm;
            display: block;
        }

        .qr-section p {
            font-size: 7px;
            color: #999;
            margin-top: 1.5mm;
        }

        .footer {
            text-align: center;
            font-size: 7px;
            color: #999;
            margin-top: 1.5mm;
        }
    </style>
</head>
<body>
    <div class="pass-container">
        <div class="header">
            <h1>Exam Pass</h1>
            <h2>{{ $course->code }} &mdash; {{ $course->title }}</h2>
            <span class="badge">Session {{ $session->session_number }}</span>
        </div>

        <div class="detail-row">
            <div class="detail-label">Student Name</div>
            <div class="detail-value">{{ $user->name }}</div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Matric Number</div>
            <div class="detail-value">{{ $student->matric_number }}</div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Date</div>
            <div class="detail-value">{{ $exam->exam_date->format('l, F j, Y') }}</div>
        </div>

        <div class="detail-row">
            <div class="detail-label">Time</div>
            <div class="detail-value">{{ $session->start_time->format('g:i A') }} &ndash; {{ $session->end_time->format('g:i A') }}</div>
        </div>

        <div class="highlight">
            <table class="seat-table">
                <tr>
                    <td>
                        <div class="detail-label">Hall</div>
                        <div class="detail-value">{{ $hall->name }}</div>
                    </td>
                    <td>
                        <div class="detail-label">System</div>
                        <div class="detail-value">{{ $system->system_code }}</div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="qr-section">
            <div class="qr-code">{!! $qrCodeSvg !!}</div>
            <p>Scan this QR code at the exam hall entrance</p>
        </div>

        <div class="footer">
            Pass ID: {{ substr($pass->pass_code, 0, 12) }}... &bull; Generated {{ now()->format('M j, Y g:i A') }}
        </div>
    </div>
</body>
</html>
