<?php

declare(strict_types=1);

namespace photopro\auth\core\domain\entities;

class Photographe
{
    public string $id;
    public string $nom;
    public string $pseudo;
    public string $email;
    public string $password;
    public ?string $telephone;
    public ?string $description;
    public ?string $created_at;

    public function __construct(
        $id = null,
        $nom = '',
        $pseudo = '',
        $email = '',
        $password = '',
        $telephone = null,
        $description = null,
        $created_at = null
    ) {
        if ($id !== null) {
            $this->id = $id;
        }
        $this->nom = $nom;
        $this->pseudo = $pseudo;
        $this->email = $email;
        $this->password = $password;
        $this->telephone = $telephone;
        $this->description = $description;
        $this->created_at = $created_at;
    }
}
