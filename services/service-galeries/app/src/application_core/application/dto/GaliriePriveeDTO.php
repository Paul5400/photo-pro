<?php
namespace photopro\notifications\core\application\dto;

class GaliriePriveeDTO {
    
    public string $galerie_id;
    public string $galerie_titre;
    public string $email_client;
    public string $code_acces;
    public string $url_acces;

    public function __construct(
        string $galerie_id,
        string $galerie_titre,
        string $email_client,
        string $code_acces,
        string $url_acces
    ) {
        $this->galerie_id = $galerie_id;
        $this->galerie_titre = $galerie_titre;
        $this->email_client = $email_client;
        $this->code_acces = $code_acces;
        $this->url_acces = $url_acces;
    }
}