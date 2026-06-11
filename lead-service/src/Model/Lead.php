<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class Lead
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\Length(max: 20)]
    public ?string $phone = null;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    public ?int $propertyId = null;
}
