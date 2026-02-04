<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserStoreRequest
{
    #[Assert\NotBlank(message: 'The name field is required.')]
    public string $name = '';

    #[Assert\NotBlank(message: 'The email field is required.')]
    #[Assert\Email(message: 'The email must be a valid email address.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'The password field is required.')]
    #[Assert\Length(min: 6, minMessage: 'The password must be at least {{ limit }} characters.')]
    public string $password = '';

    #[Assert\Choice(choices: ['super_admin', 'admin', 'editor'], message: 'The role must be a valid value.')]
    public ?string $role = null;

    public ?string $phone = null;
}
