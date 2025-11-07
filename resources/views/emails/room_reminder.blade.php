<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
</head>

<body style="font-family: Arial, sans-serif;">
    <h2>Room Reservation Reminder</h2>

    <p>Hi {{ $reservation->user_name }},</p>

    <p>This is a reminder that you have a room reservation coming up:</p>

    <ul>
        <li><strong>Room:</strong> {{ $reservation->room->name }}</li>
        <li><strong>Date:</strong> {{ \Carbon\Carbon::parse($reservation->date)->format('d M Y') }}</li>
        <li><strong>Time:</strong>
            {{ $reservation->start_time->format('h:i A') }} -
            {{ $reservation->end_time->format('h:i A') }}
        </li>
        <li><strong>Purpose:</strong> {{ $reservation->purpose }}</li>
    </ul>

    <p>Please ensure you arrive on time.</p>

    <br>
    <p>Thank you.</p>
</body>

</html>