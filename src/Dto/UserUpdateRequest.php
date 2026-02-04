<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserUpdateRequest
{
    public ?string $name = null;

    public ?string $phone = null;

    #[Assert\Choice(choices: ['super_admin', 'admin', 'editor'], message: 'The role must be a valid value.')]
    public ?string $role = null;

    #[Assert\Length(min: 6, minMessage: 'The password must be at least {{ limit }} characters.')]
    public ?string $password = null;
}
