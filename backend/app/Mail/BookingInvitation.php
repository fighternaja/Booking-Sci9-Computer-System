<?php

namespace App\Mail;

use App\Models\BookingAttendee;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $attendee;
    public $booking;

    /**
     * Create a new message instance.
     */
    public function __construct(BookingAttendee $attendee)
    {
        $this->attendee = $attendee;
        $this->booking = $attendee->booking;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'เชิญเข้าร่วมการจองห้อง - ' . $this->booking->room->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-invitation',
            with: [
                'attendee' => $this->attendee,
                'booking' => $this->booking,
                'room' => $this->booking->room,
                'organizer' => $this->booking->user,
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

