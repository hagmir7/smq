<?php

namespace App\Notifications;

use App\Models\Reclamation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReclamationCreated extends Notification
{
    use Queueable;

    public function __construct(public Reclamation $reclamation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvelle réclamation enregistrée : {$this->reclamation->code}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle réclamation a été enregistrée et nécessite votre traitement.")
            ->line("Code : {$this->reclamation->code}")
            ->line("Réclamant : {$this->reclamation->claimant_name}")
            ->line("Objet : {$this->reclamation->object}")
            ->line("Date de réception : {$this->reclamation->received_at?->format('d/m/Y')}")
            ->action(
                'Voir la réclamation',
                url("/reclamations/{$this->reclamation->id}")
            )
            ->line("Merci de procéder à son analyse.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'reclamation_id' => $this->reclamation->id,
            'code'           => $this->reclamation->code,
            'claimant_name'  => $this->reclamation->claimant_name,
            'object'         => $this->reclamation->object,
            'received_at'    => $this->reclamation->received_at?->format('Y-m-d'),
            'message'        => "Une nouvelle réclamation a été enregistrée : {$this->reclamation->code}.",
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}