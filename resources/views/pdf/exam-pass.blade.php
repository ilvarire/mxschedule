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

        html,
        body {
            width: 105mm;
            height: 148.5mm;
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #000;
        }

        body {
            padding: 6mm;
            font-size: 9px;
            line-height: 1.25;
        }

        .pass {
            width: 93mm;
            height: 136.5mm;
            border: 1.5px solid #000;
            padding: 5mm;
            overflow: hidden;
        }

        .title {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 3mm;
            margin-bottom: 4mm;
        }

        .title h1 {
            font-size: 15px;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 1mm;
        }

        .title p {
            font-size: 9px;
        }

        .row {
            border-bottom: 1px solid #000;
            padding: 2mm 0;
        }

        .label {
            display: block;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 0.75mm;
        }

        .value {
            display: block;
            font-size: 10px;
            font-weight: bold;
            word-wrap: break-word;
        }

        .seat-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3mm;
            border: 1px solid #000;
        }

        .seat-table td {
            width: 50%;
            padding: 2mm;
            vertical-align: top;
        }

        .seat-table td:first-child {
            border-right: 1px solid #000;
        }

        .seat-table .value {
            font-size: 13px;
        }

        .qr {
            text-align: center;
            margin-top: 5mm;
        }

        .qr img {
            width: 42mm;
            height: 42mm;
            display: block;
            margin: 0 auto;
        }

        .qr p {
            font-size: 7px;
            margin-top: 2mm;
        }

        .footer {
            text-align: center;
            font-size: 7px;
            margin-top: 4mm;
            border-top: 1px solid #000;
            padding-top: 2mm;
        }
    </style>
</head>
<body>
    <div class="pass">
        <div class="title">
            <h1>Exam Pass</h1>
            <p>{{ $course->code }} - {{ $course->title }}</p>
            <p>Session {{ $session->session_number }}</p>
        </div>

        <div class="row">
            <span class="label">Student Name</span>
            <span class="value">{{ $user->name }}</span>
        </div>

        <div class="row">
            <span class="label">Matric Number</span>
            <span class="value">{{ $student->matric_number }}</span>
        </div>

        <div class="row">
            <span class="label">Date</span>
            <span class="value">{{ $exam->exam_date->format('l, F j, Y') }}</span>
        </div>

        <div class="row">
            <span class="label">Time</span>
            <span class="value">{{ $session->start_time->format('g:i A') }} - {{ $session->end_time->format('g:i A') }}</span>
        </div>

        <table class="seat-table">
            <tr>
                <td>
                    <span class="label">Hall</span>
                    <span class="value">{{ $hall->name }}</span>
                </td>
                <td>
                    <span class="label">System</span>
                    <span class="value">{{ $system->system_code }}</span>
                </td>
            </tr>
        </table>

        <div class="qr">
            <img src="{{ $qrCodeDataUri }}" alt="Exam pass QR code">
            <p>Scan this QR code at the exam hall entrance.</p>
        </div>

        <div class="footer">
            Pass ID: {{ substr($pass->pass_code, 0, 12) }} | Generated {{ now()->format('M j, Y g:i A') }}
        </div>
    </div>
</body>
</html>
