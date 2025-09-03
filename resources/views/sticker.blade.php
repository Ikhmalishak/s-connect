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
            border-collapse: collapse;
            border-spacing: 0;
            /* no gaps between rows */
        }

        td {
            vertical-align: middle;
            text-align: center;
            padding: 1mm 2mm;
            /* less vertical padding */
        }

        .qr {
            width: 13mm;
            height: 13mm;
        }

        .datetime {
            font-size: 6pt;
            transform: rotate(90deg);
            transform-origin: center;
        }

        .header {
            font-size: 6pt;
            margin: 1mm 0;
        }

        .text {
            font-size: 5.5pt;
            margin: 1mm 0;
        }

        .pax-number {
            font-size: 20pt;
            /* bigger */
            font-weight: bold;
        }

        .pax-word {
            font-size: 4pt;
            /* smaller */
            margin-left: 2px;
        }

        .logo {
            width: 15mm;
            height: 15mm;
        }
    </style>
</head>

<body>
    <table>
        <tr>
            <td><img src="data:image/png;base64,{{ $logo }}" class="logo" /></td>
            <td class="datetime">
                {{ date('d/m/Y H:i') }} <br>
                <span class="pax-number">{{ $total_pax }}</span>
                <span class="pax-word">Pax</span>
            </td>
            <td><img src="data:image/png;base64,{{ $qr }}" class="qr" /></td>
        </tr>
        <tr>
            <td colspan="3">
                <h1 class="header">Visitor Notice</h1>
                <p class="text">
                    This QR Tag is part of your visitor pass. Your host will scan it to confirm the visit is
                    completed. Please return the pass with the QR Tag to Security for exit clearance before leaving
                    the premises.
                </p>
            </td>
        </tr>
    </table>
</body>

</html>