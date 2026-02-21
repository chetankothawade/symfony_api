<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class ModuleUpdateRequest
{
    #[Assert\Uuid(message: 'The uuid must be a valid UUID.')]
    public ?string $uuid = null;

    public ?int $parent_id = null;

    #[Assert\Length(max: 50, maxMessage: 'The name may not be greater than {{ limit }} characters.')]
    public ?string $name = null;

    #[Assert\Length(max: 100, maxMessage: 'The url may not be greater than {{ limit }} characters.')]
    public ?string $url = null;

    #[Assert\Length(max: 100, maxMessage: 'The icon may not be greater than {{ limit }} characters.')]
    public ?string $icon = null;

    public ?int $seq_no = null;

    #[Assert\Choice(choices: ['Y', 'N'], message: 'The is_sub_module must be either Y or N.')]
    public ?string $is_sub_module = null;

    #[Assert\Choice(choices: ['active', 'inactive'], message: 'The status must be active or inactive.')]
    public ?string $status = null;

    #[Assert\Choice(choices: ['Y', 'N'], message: 'The is_permission must be either Y or N.')]
    public ?string $is_permission = null;
}

