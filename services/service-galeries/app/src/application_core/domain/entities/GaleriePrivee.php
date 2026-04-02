<?php
namespace photopro\galeries\core\domain\entities;
use Ramsey\Uuid\UuidInterface;
  class GaleriePrivee {
    private UuidInterface $id;
    private UuidInterface $galerie_id;
    private string $nom_client;
    private string $email_client;
    private string $telephone_client;
    private string $code_acces;
    private string $url_acces;
    public function __construct(
        UuidInterface $id,
        UuidInterface $galerie_id,
        string $nom_client,
        string $email_client,
        string $telephone_client,
        string $code_acces,
        string $url_acces
    ) {
        $this->id = $id;
        $this->galerie_id = $galerie_id;
        $this->nom_client = $nom_client;
        $this->email_client = $email_client;
        $this->telephone_client = $telephone_client;
        $this->code_acces = $code_acces;
        $this->url_acces = $url_acces;
    }
    // Getters
    public function getId(): UuidInterface
    {
        return $this->id;
    }
    public function getGalerieId(): UuidInterface
    {
        return $this->galerie_id;
    }
    public function getNomClient(): string
    {
        return $this->nom_client;
    }
    public function getEmailClient(): string
    {
        return $this->email_client;
    }
    public function getTelephoneClient(): string
    {
        return $this->telephone_client;
    }
    public function getCodeAcces(): string
    {
        return $this->code_acces;
    }
    public function getUrlAcces(): string
    {
        return $this->url_acces;
    }

  }
