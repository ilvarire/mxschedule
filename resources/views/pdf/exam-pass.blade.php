<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Pass</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            /* Quarter A4: 105mm x 148.5mm */
            width: 105mm;
            height: 148.5mm;
            padding: 6mm;
        }
        .pass-container {
            border: 2px solid #1e3a5f;
            border-radius: 6px;
            padding: 4mm;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #1e3a5f;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }
        .header h1 {
            font-size: 13px;
            color: #1e3a5f;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header h2 {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }
        .badge {
            display: inline-block;
            background: #1e3a5f;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
            margin-top: 3px;
        }
        .details {
            flex: 1;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 2mm 0;
            border-bottom: 1px dotted #ccc;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-size: 8px;
            color: #777;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 11px;
            font-weight: bold;
            text-align: right;
        }
        .highlight {
            background: #f0f7ff;
            padding: 2mm 3mm;
            border-radius: 4px;
            margin: 2mm 0;
        }
        .highlight .detail-value {
            color: #1e3a5f;
            font-size: 14px;
        }
        .qr-section {
            text-align: center;
            margin-top: 3mm;
            padding-top: 3mm;
            border-top: 2px solid #1e3a5f;
        }
        .qr-section p {
            font-size: 7px;
            color: #999;
            margin-top: 2mm;
        }
        .footer {
            text-align: center;
            font-size: 7px;
            color: #999;
            margin-top: 2mm;
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

        <div class="details">
            <div class="detail-row">
                <div>
                    <div class="detail-label">Student Name</div>
                    <div class="detail-value">{{ $user->name }}</div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Matric Number</div>
                    <div class="detail-value">{{ $student->matric_number }}</div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Date</div>
                    <div class="detail-value">{{ $exam->exam_date->format('l, F j, Y') }}</div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">Time</div>
                    <div class="detail-value">{{ $session->start_time->format('g:i A') }} &ndash; {{ $session->end_time->format('g:i A') }}</div>
                </div>
            </div>

            <div class="highlight">
                <div class="detail-row" style="border: none;">
                    <div>
                        <div class="detail-label">Hall</div>
                        <div class="detail-value">{{ $hall->name }}</div>
                    </div>
                    <div style="text-align: right;">
                        <div class="detail-label">System</div>
                        <div class="detail-value">{{ $system->system_code }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="qr-section">
            {!! $qrCodeSvg !!}
            <p>Scan this QR code at the exam hall entrance</p>
        </div>

        <div class="footer">
            Pass ID: {{ substr($pass->pass_code, 0, 12) }}… &bull; Generated {{ now()->format('M j, Y g:i A') }}
        </div>
    </div>
</body>
</html>
