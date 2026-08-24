<?php

namespace App\Notifications;

use App\Models\CorrectiveAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrectiveActionCompletionDate extends Notification
{
    use Queueable;

    public function __construct(
        public CorrectiveAction $correctiveAction
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'corrective_action_id' => $this->correctiveAction->id,
            'reclamation_id' => $this->correctiveAction->reclamation_id,
            'code' => $this->correctiveAction->code,
            'description' => $this->correctiveAction->description,
            'completion_date' => $this->correctiveAction->completion_date?->format('Y-m-d'),
            'due_date' => $this->correctiveAction->due_date?->format('Y-m-d'),
            'message' => "La date de réalisation de l'action corrective {$this->correctiveAction->code} a été renseignée.",
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}