<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;
    public $hoursBefore;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, int $hoursBefore = 1)
    {
        $this->booking = $booking;
        $this->hoursBefore = $hoursBefore;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'เตือน: การจองห้องของคุณจะเริ่มในอีก ' . $this->hoursBefore . ' ชั่วโมง - ' . $this->booking->room->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-reminder',
            with: [
                'booking' => $this->booking,
                'user' => $this->booking->user,
                'room' => $this->booking->room,
                'hoursBefore' => $this->hoursBefore,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

