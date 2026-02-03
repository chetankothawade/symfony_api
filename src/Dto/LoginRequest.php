<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank(message: 'The email field is required.')]
    #[Assert\Email(message: 'The email must be a valid email address.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'The password field is required.')]
    public string $password = '';
}
