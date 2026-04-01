<?php

declare(strict_types=1);

namespace photopro\auth\core\application\dto;

class CreatePhotographeDTO
{
    public string $nom;
    public string $pseudo;
    public string $email;
    public string $password;
    public ?string $telephone;
    public ?string $description;

    public function __construct(
        string $nom,
        string $pseudo,
        string $email,
        string $password,
        ?string $telephone = null,
        ?string $description = null
    ) {
        $this->nom = $nom;
        $this->pseudo = $pseudo;
        $this->email = $email;
        $this->password = $password;
        $this->telephone = $telephone;
        $this->description = $description;
    }
}
