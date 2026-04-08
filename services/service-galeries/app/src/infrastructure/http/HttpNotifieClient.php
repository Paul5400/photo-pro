<?php
namespace photopro\galeries\infra\http;

use GuzzleHttp\Client;

class HttpNotifieClient
{
    private $client;

    public function __construct(string $notifierUrl)
    {
        $this->client = new Client([
            'base_uri' => $notifierUrl,
            'timeout'  => 5.0,
        ]);
    }

    public function send(array $data)
    {
        $this->client->post('/notifications', [
            'json' => $data
        ]);
    }
}