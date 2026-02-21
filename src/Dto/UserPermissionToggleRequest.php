<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UserPermissionToggleRequest
{
    #[Assert\NotBlank(message: 'The userUuid field is required.')]
    #[Assert\Uuid(message: 'The userUuid must be a valid UUID.')]
    public string $userUuid = '';

    #[Assert\NotNull(message: 'The modulePermissionId field is required.')]
    #[Assert\Positive(message: 'The modulePermissionId must be a positive integer.')]
    public int $modulePermissionId = 0;

    #[Assert\NotNull(message: 'The isChecked field is required.')]
    #[Assert\Type(type: 'bool', message: 'The isChecked field must be true or false.')]
    public bool $isChecked = false;
}

