<?php

namespace App\Notifications;

use App\Models\CorrectiveAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrectiveActionCreated extends Notification
{
    use Queueable;

    /**
     * Crée une nouvelle instance de notification.
     */
    public function __construct(public CorrectiveAction $correctiveAction)
    {
    }

    /**
     * Détermine les canaux de diffusion de la notification.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Représentation de la notification par e-mail.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvelle action corrective assignée : {$this->correctiveAction->code}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Une nouvelle action corrective vous a été assignée.")
            ->line("Code : {$this->correctiveAction->code}")
            ->line("Description : {$this->correctiveAction->description}")
            ->line("Date d'échéance : {$this->correctiveAction->due_date?->format('d/m/Y')}")
            ->action('Voir l’action corrective', url("/corrective-actions/{$this->correctiveAction->id}"))
            ->line("Merci de la consulter et de prendre les mesures nécessaires.");
    }

    /**
     * Représentation de la notification sous forme de tableau.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'corrective_action_id' => $this->correctiveAction->id,
            'code'                 => $this->correctiveAction->code,
            'description'          => $this->correctiveAction->description,
            'due_date'             => $this->correctiveAction->due_date?->format('Y-m-d'),
            'service_id'           => $this->correctiveAction->service_id,
            'reclamation_id'       => $this->correctiveAction->reclamation_id,
            'message'              => "Une nouvelle action corrective vous a été assignée : {$this->correctiveAction->code}.",
        ];
    }

    /**
     * Personnalise le contenu enregistré en base de données.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}