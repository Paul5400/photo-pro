<?php
declare(strict_types=1);

namespace photopro\notifications\infra\mailer;

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;
use photopro\notifications\core\application\dto\NotificationEventDTO;
use photopro\notifications\core\application\ports\MailerInterface;
use photopro\notifications\core\domain\value_objects\TypeEvenement;

class SymfonyMailerAdapter implements MailerInterface
{
    private Mailer $mailer;
    private string $fromAddress;

    public function __construct(string $dsn, string $fromAddress = 'noreply@photopro.net')
    {
        $transport        = Transport::fromDsn($dsn);
        $this->mailer     = new Mailer($transport);
        $this->fromAddress = $fromAddress;
    }

    public function send(NotificationEventDTO $event): void
    {
        match ($event->typeEvenement) {
            TypeEvenement::PUBLISHED   => $this->sendPublished($event),
            TypeEvenement::UNPUBLISHED => $this->sendUnpublished($event),
            TypeEvenement::MODIFIED    => $this->sendModified($event),
        };
    }

    // Publication -> 2 mails distincts

    private function sendPublished(NotificationEventDTO $event): void
    {
        // Mail 1 : URL d'accès direct
        $mailUrl = (new Email())
            ->from($this->fromAddress)
            ->to($event->clientEmail)
            ->subject("Votre galerie \"{$event->galerieTitre}\" est disponible")
            ->html($this->templatePublishedUrl($event));

        $this->mailer->send($mailUrl);

        // Mail 2 : code d'accès
        $mailCode = (new Email())
            ->from($this->fromAddress)
            ->to($event->clientEmail)
            ->subject("Votre code d'accès — \"{$event->galerieTitre}\"")
            ->html($this->templatePublishedCode($event));

        $this->mailer->send($mailCode);
    }

    // Dépublication -> 1 mail 

    private function sendUnpublished(NotificationEventDTO $event): void
    {
        $mail = (new Email())
            ->from($this->fromAddress)
            ->to($event->clientEmail)
            ->subject("Votre galerie \"{$event->galerieTitre}\" n'est plus disponible")
            ->html($this->templateUnpublished($event));

        $this->mailer->send($mail);
    }

    // Modification -> 1 mail

    private function sendModified(NotificationEventDTO $event): void
    {
        $mail = (new Email())
            ->from($this->fromAddress)
            ->to($event->clientEmail)
            ->subject("Votre galerie \"{$event->galerieTitre}\" a été mise à jour")
            ->html($this->templateModified($event));

        $this->mailer->send($mail);
    }

    //  Templates HTML 

    private function templatePublishedUrl(NotificationEventDTO $event): string
    {
        return <<<HTML
        <h2>Votre galerie est disponible !</h2>
        <p>Bonjour,</p>
        <p>La galerie <strong>{$event->galerieTitre}</strong> a été publiée et est maintenant accessible.</p>
        <p>Accédez directement à votre galerie en cliquant sur le lien ci-dessous :</p>
        <p><a href="{$event->urlAcces}">{$event->urlAcces}</a></p>
        <p>PhotoPro.net</p>
        HTML;
    }

    private function templatePublishedCode(NotificationEventDTO $event): string
    {
        return <<<HTML
        <h2>Votre code d'accès</h2>
        <p>Bonjour,</p>
        <p>Voici votre code d'accès pour la galerie <strong>{$event->galerieTitre}</strong> :</p>
        <p style="font-size:2em; font-weight:bold; letter-spacing:0.2em;">{$event->codeAcces}</p>
        <p>Ce code vous permet d'accéder à votre galerie depuis la plateforme PhotoPro.net.</p>
        <p>PhotoPro.net</p>
        HTML;
    }

    private function templateUnpublished(NotificationEventDTO $event): string
    {
        return <<<HTML
        <h2>Galerie temporairement indisponible</h2>
        <p>Bonjour,</p>
        <p>La galerie <strong>{$event->galerieTitre}</strong> n'est plus disponible pour le moment.</p>
        <p>Vous serez notifié dès qu'elle sera de nouveau accessible.</p>
        <p>PhotoPro.net</p>
        HTML;
    }

    private function templateModified(NotificationEventDTO $event): string
    {
        return <<<HTML
        <h2>Votre galerie a été mise à jour</h2>
        <p>Bonjour,</p>
        <p>La galerie <strong>{$event->galerieTitre}</strong> vient d'être mise à jour par le photographe.</p>
        <p>Accédez à votre galerie : <a href="{$event->urlAcces}">{$event->urlAcces}</a></p>
        <p>PhotoPro.net</p>
        HTML;
    }
}
