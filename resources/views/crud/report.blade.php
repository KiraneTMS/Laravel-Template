<!DOCTYPE html>
<html>
<head>
    <title>{{ strtoupper($entity->name) }} REPORT</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            color: #333;
        }
        h1, p {
            text-align: center;
        }
        .report-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .report-header h1 {
            margin-bottom: 5px;
        }
        .report-header p {
            margin: 5px 0;
            color: #555;
        }
        .table-wrapper {
            width: 100%;
            overflow-x: auto; /* Enable horizontal scrolling */
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            min-width: 100px; /* Fixed min-width for table */
            font-size: 12px; /* Fixed font size for table */
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #777;
        }
        .signature-section {
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }
        .signature-box {
            width: 250px;
            padding-top: 50px;
            text-align: center;
            position: relative;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 100%;
            margin-top: 5px;
        }
        .signature-label {
            font-weight: bold;
            margin-top: 10px;
        }
        .left-signature {
            float: left;
            text-align: left;
        }
        .right-signature {
            float: right;
            text-align: right;
        }

/* Font size adjustments based on paper size */
@media print and (max-width: 420mm) { /* A3: 420 mm */
    .report-header h1 { font-size: 48px; } /* Was 24px, now 2x */
    .report-header p { font-size: 28px; } /* Was 14px, now 2x */
    .footer { font-size: 24px; } /* Was 12px, now 2x */
    .signature-label { font-size: 32px; } /* Was 14px, now 2x */
    .signature-date { font-size: 32px; } /* Was 14px, now 2x */
}
@media print and (max-width: 594mm) { /* A2: 594 mm */
    .report-header h1 { font-size: 56px; } /* Was 28px, now 2x */
    .report-header p { font-size: 32px; } /* Was 16px, now 2x */
    .footer { font-size: 28px; } /* Was 14px, now 2x */
    .signature-label { font-size: 32px; } /* Was 16px, now 2x */
    .signature-date { font-size: 32px; } /* Was 16px, now 2x */
}
@media print and (max-width: 841mm) { /* A1: 841 mm */
    .report-header h1 { font-size: 64px; } /* Was 32px, now 2x */
    .report-header p { font-size: 36px; } /* Was 18px, now 2x */
    .footer { font-size: 32px; } /* Was 16px, now 2x */
    .signature-label { font-size: 32px; } /* Was 18px, now 2x */
    .signature-date { font-size: 32px; } /* Was 18px, now 2x */
}
@media print and (max-width: 1189mm) { /* A0: 1189 mm */
    .report-header h1 { font-size: 72px; } /* Was 36px, now 2x */
    .report-header p { font-size: 40px; } /* Was 20px, now 2x */
    .footer { font-size: 36px; } /* Was 18px, now 2x */
    .signature-label { font-size: 32px; } /* Was 20px, now 2x */
    .signature-date { font-size: 32px; } /* Was 20px, now 2x */
}
@media print and (max-width: 1682mm) { /* 2A0: 1682 mm */
    .report-header h1 { font-size: 80px; } /* Was 40px, now 2x */
    .report-header p { font-size: 48px; } /* Was 24px, now 2x */
    .footer { font-size: 40px; } /* Was 20px, now 2x */
    .signature-label { font-size: 32px; } /* Was 24px, now 2x */
    .signature-date { font-size: 32px; } /* Was 24px, now 2x */
}
@media print and (max-width: 2378mm) { /* 4A0: 2378 mm */
    .report-header h1 { font-size: 88px; } /* Was 44px, now 2x */
    .report-header p { font-size: 56px; } /* Was 28px, now 2x */
    .footer { font-size: 48px; } /* Was 24px, now 2x */
    .signature-label { font-size: 32px; } /* Was 28px, now 2x */
    .signature-date { font-size: 32px; } /* Was 28px, now 2x */
}
    </style>
</head>
<body>
    <div class="report-header">
        <h1>{{ strtoupper($entity->name) }} REPORT</h1>
        <p><strong>Company Name: {{ $entity->company_name ?? 'Your Company Name Here' }}</strong></p>
        <p>Address: {{ $entity->address ?? 'Company Address Here' }}</p>
        <p>Contact: {{ $entity->contact ?? 'Phone/Email Here' }}</p>
        <p>Generated on: {{ now()->format('F d, Y') }}</p>
        <p>Generated by: {{ auth()->user()->name }}</p>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th>{{ ucfirst($column) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        @foreach($columns as $column)
                            <td>
                                @if(Str::contains($item->$column, '00:00:00'))
                                    {{ \Carbon\Carbon::parse($item->$column)->format('Y-m-d') }}
                                @else
                                    {{ $item->$column }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="signature-section">
        <div class="left-signature">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Signature</div>
                <div class="signature-date">Date: ___________</div>
            </div>
        </div>
        <div class="right-signature">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Signature</div>
                <div class="signature-date">Date: ___________</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Confidential Report - {{ now()->year }} © {{ $entity->company_name ?? 'Your Company Name Here' }}</p>
    </div>
</body>
</html>
