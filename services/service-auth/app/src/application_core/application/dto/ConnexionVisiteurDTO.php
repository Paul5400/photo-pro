<?php

declare(strict_types=1);

namespace photopro\auth\core\application\dto;

class ConnexionVisiteurDTO
{
    public string $url_acces;
    public string $code_acces;

    public function __construct(string $url_acces, string $code_acces)
    {
        $this->url_acces = $url_acces;
        $this->code_acces = $code_acces;
    }
}
