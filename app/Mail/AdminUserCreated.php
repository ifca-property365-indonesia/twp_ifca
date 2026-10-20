<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email ke user administrator baru (Admin Users). Dikirim hanya bila ADMIN_USER_MAIL=true
 * (config/admin_users.php), lewat mailer smtp (SMTP Gmail, MAIL_* di .env).
 * $password diisi hanya bila user memakai password default; password yang diisi admin tidak dikirim.
 */
class AdminUserCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $group,
        public ?string $password,
        public string $loginUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('admin/users.mail_subject', ['app' => config('app.name')]));
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin_user_created');
    }
}
