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
     * Create a new notification instance.
     */
    public function __construct(public CorrectiveAction $correctiveAction)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Corrective Action Assigned: {$this->correctiveAction->code}")
            ->greeting("Hi {$notifiable->name},")
            ->line("A new corrective action has been assigned to you.")
            ->line("Code: {$this->correctiveAction->code}")
            ->line("Description: {$this->correctiveAction->description}")
            ->line("Due date: {$this->correctiveAction->due_date?->format('Y-m-d')}")
            ->action('View Corrective Action', url("/corrective-actions/{$this->correctiveAction->id}"))
            ->line('Please review and take the necessary steps.');
    }

    /**
     * Get the array representation of the notification.
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
            'message'              => "You have been assigned a new corrective action: {$this->correctiveAction->code}.",
        ];
    }

    /**
     * Optional: customize the database payload separately from the array representation.
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}