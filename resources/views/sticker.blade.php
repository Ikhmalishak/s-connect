<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: sans-serif;
            font-size: 10pt;
        }

        table {
            width: 100%;
            height: 30mm;
            /* full sticker height */
            border-collapse: collapse;
            border-spacing: 0;
        }

        td {
            vertical-align: middle;
            text-align: center;
            padding: 0;
            margin: 0;
        }

        /* --- ROW 1: DATETIME --- */
        tr.datetime-row td {
            height: 5mm;
            font-size: 5pt;
            font-weight: bold;
            vertical-align: bottom;
            /* stick to bottom of the cell */
            padding-bottom: 0.3mm;
            /* adjust so it doesn’t touch the edge */
        }

        /* --- ROW 2: MAIN CONTENT --- */
        tr.main-row td {
            height: 17mm;
            /* reduced from 15mm */
        }

        /* --- ROW 3: NOTICE --- */
        /* --- ROW 3: NOTICE --- */
        tr.notice-row td {
            height: 8mm;
            padding: 0 2mm;
            vertical-align: top;
            /* stick to the top of the row */
        }

        .logo {
            width: 11.5mm;
            height: auto;
            /* keeps proportions */
        }

        .pax-number {
            font-size: 35pt;
            font-weight: bold;
            line-height: 1;
            display: block;
        }

        .pax-word {
            font-size: 8pt;
            display: block;
        }

        .qr {
            width: 14.5mm;
            height: 14.5mm;
        }

        .notice-title {
            font-size: 4.5pt;
            font-weight: bold;
            margin: 0;
            text-align: center;
        }

        .notice {
            font-size: 3.7pt;
            line-height: 0.5;
            margin: 1mm 0 0 0;
            text-align: justify;
            padding: 0 2mm;
        }
    </style>
</head>

<body>
    <table>
        <!-- ROW 1: DATE & TIME -->
        <tr class="datetime-row">
            <td>Visitor Gate Pass</td>
            <td>{{ date('H:i d M Y') }}</td>
            <td>{{ $ack_number }}</td>
        </tr>

        <!-- ROW 2: MAIN CONTENT -->
        <tr class="main-row">
            <td><img src="data:image/png;base64,{{ $logo }}" class="logo" /></td>
            <td>
                <span class="pax-number">{{ $total_pax }}</span>
                <span class="pax-word">PAX</span>
            </td>
            <td><img src="data:image/png;base64,{{ $qr }}" class="qr" /></td>
        </tr>

        <!-- ROW 3: NOTICE -->
        <tr class="notice-row">
            <td colspan="3">
                <h1 class="notice-title">⚠ Visitor Notice</h1>
                <p class="notice">
                    This QR Tag is part of your visitor pass. Your host will scan it to confirm the visit is completed.
                    Please return the pass with the QR Tag to Security for exit clearance before leaving the premises.
                </p>
            </td>
        </tr>
    </table>
</body>

</html>