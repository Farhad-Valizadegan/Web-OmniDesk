<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class LoginCode extends Mailable
{
    public function __construct(public readonly string $code) {}

    public function build(): static
    {
        return $this->subject('Your OmniDesk sign-in code')
            ->text('mail.login-code');
    }
}
