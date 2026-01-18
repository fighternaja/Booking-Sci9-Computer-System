<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองห้องถูกอนุมัติ</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #28a745; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h2 style="color: #ffffff; margin-top: 0;">✅ การจองห้องถูกอนุมัติแล้ว</h2>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
        <p>สวัสดี <strong>{{ $user->name }}</strong>,</p>

        <p>การจองห้องของคุณถูกอนุมัติแล้ว!</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2c3e50;">รายละเอียดการจอง</h3>
            <p><strong>ห้อง:</strong> {{ $room->name }}</p>
            <p><strong>วันที่:</strong> {{ $booking->start_time->format('d/m/Y') }}</p>
            <p><strong>เวลาเริ่ม:</strong> {{ $booking->start_time->format('H:i') }}</p>
            <p><strong>เวลาสิ้นสุด:</strong> {{ $booking->end_time->format('H:i') }}</p>
            <p><strong>วัตถุประสงค์:</strong> {{ $booking->purpose }}</p>
            @if($booking->notes)
                <p><strong>หมายเหตุ:</strong> {{ $booking->notes }}</p>
            @endif
            @if($booking->approval_reason)
                <p><strong>หมายเหตุการอนุมัติ:</strong> {{ $booking->approval_reason }}</p>
            @endif
        </div>

        <p style="color: #666; font-size: 14px;">กรุณามาใช้ห้องตามเวลาที่จองไว้</p>
    </div>

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0; text-align: center; color: #666; font-size: 12px;">
        <p>นี่คืออีเมลอัตโนมัติจากระบบจองห้อง กรุณาอย่าตอบกลับอีเมลนี้</p>
    </div>
</body>
</html>

