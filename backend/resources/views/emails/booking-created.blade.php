<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การจองห้อง</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
        <h2 style="color: #2c3e50; margin-top: 0;">
            @if($booking->status === 'approved')
                ✅ การจองห้องสำเร็จ
            @else
                📋 ส่งคำขอจองห้องแล้ว
            @endif
        </h2>
    </div>

    <div style="background-color: #ffffff; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px;">
        <p>สวัสดี <strong>{{ $user->name }}</strong>,</p>

        @if($booking->status === 'approved')
            <p>การจองห้องของคุณถูกอนุมัติแล้ว!</p>
        @else
            <p>คำขอจองห้องของคุณถูกส่งแล้ว กรุณารอการอนุมัติจากผู้ดูแลระบบ</p>
        @endif

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
            <p><strong>สถานะ:</strong> 
                @if($booking->status === 'approved')
                    <span style="color: #28a745; font-weight: bold;">อนุมัติแล้ว</span>
                @else
                    <span style="color: #ffc107; font-weight: bold;">รออนุมัติ</span>
                @endif
            </p>
        </div>

        @if($booking->status === 'pending')
            <p style="color: #666; font-size: 14px;">คุณจะได้รับอีเมลแจ้งเตือนเมื่อการจองของคุณถูกอนุมัติหรือปฏิเสธ</p>
        @endif
    </div>

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e0e0e0; text-align: center; color: #666; font-size: 12px;">
        <p>นี่คืออีเมลอัตโนมัติจากระบบจองห้อง กรุณาอย่าตอบกลับอีเมลนี้</p>
    </div>
</body>
</html>

