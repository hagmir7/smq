<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Direction Générale',
                'description' => 'Supervision et pilotage stratégique de l’entreprise.',
                'responsible_email' => 'direction@example.com',
            ],
            [
                'name' => 'Qualité',
                'description' => 'Gestion du système de management de la qualité.',
                'responsible_email' => 'qualite@example.com',
            ],
            [
                'name' => 'Production',
                'description' => 'Gestion des activités de production.',
                'responsible_email' => 'production@example.com',
            ],
            [
                'name' => 'Maintenance',
                'description' => 'Maintenance préventive et corrective.',
                'responsible_email' => 'maintenance@example.com',
            ],
            [
                'name' => 'Logistique',
                'description' => 'Gestion des stocks et des expéditions.',
                'responsible_email' => 'logistique@example.com',
            ],
            [
                'name' => 'Achats',
                'description' => 'Gestion des achats et des fournisseurs.',
                'responsible_email' => 'achats@example.com',
            ],
            [
                'name' => 'Commercial',
                'description' => 'Développement commercial et relation client.',
                'responsible_email' => 'commercial@example.com',
            ],
            [
                'name' => 'Ressources Humaines',
                'description' => 'Gestion des ressources humaines.',
                'responsible_email' => 'rh@example.com',
            ],
            [
                'name' => 'Finance',
                'description' => 'Gestion financière et comptable.',
                'responsible_email' => 'finance@example.com',
            ],
            [
                'name' => 'Informatique',
                'description' => 'Gestion des systèmes d’information.',
                'responsible_email' => 'it@example.com',
            ],
        ];

        foreach ($services as $data) {
            $user = User::where('email', $data['responsible_email'])->first();

            Service::updateOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'responsible_id' => $user?->id,
                ]
            );
        }
    }
}