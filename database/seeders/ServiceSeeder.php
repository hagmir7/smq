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
                'name' => 'Direction',
                'description' => 'Supervision et pilotage stratégique de l’entreprise.',
                'responsible_email' => 'essjihad@intercocina.com',
                'code' => 'DSP'
            ],

            [
                'name' => 'Amélioration du SMQ',
                'description' => 'SSystèmes de management de la qualité : introduction',
                'responsible_email' => 'smq@intercocina.com',
                'code' => 'SMQ'
            ],

            [
                'name' => 'Marketing',
                'description' => 'Gestion des stratégies marketing et de la communication.',
                'responsible_email' => 'marketing@intercocina.com',
                'code' => 'MRK'
            ],

            [
                'name' => 'Préparation de la Production & Approvisionnement',
                'description' => 'Gestion de la planification de la production et de l’approvisionnement.',
                'responsible_email' => 'stock@intercocina.com',
                'code' => 'PPA'
            ],

            [
                'name' => 'Fabrication',
                'description' => 'Gestion des activités de production et de fabrication.',
                'responsible_email' => 'fabrication@intercocina.com',
                'code' => 'FAB'
            ],

            [
                'name' => 'Contrôle Qualité & HSE',
                'description' => 'Assurer la qualité des produits et la sécurité au travail.',
                'responsible_email' => 'qualite@intercocina.com',
                'code' => 'CQI'
            ],

            [
                'name' => 'Expédition & Livraison',
                'description' => 'Gestion des expéditions et des livraisons des produits.',
                'responsible_email' => 'livraison@intercocina.com',
                'code' => 'EXL'
            ],
            [
                'name' => 'Maintenance et des Infrastructures ',
                'description' => 'Maintenance préventive et corrective.',
                'responsible_email' => 'maintenance@intercocina.com',
                'code' => 'GIM'
            ],
            [
                'name' => 'Ressources Humaines',
                'description' => 'Gestion des ressources humaines.',
                'responsible_email' => 'rh@intercocina.com',
                'code' => 'GRH'
            ],

            [
                'name' => 'Systèmes d’Information',
                'description' => 'Gestion des systèmes d’information.',
                'responsible_email' => 'ac.assime@example.com',
                'code' => 'GSI'
            ],

            [
                'name' => 'Achats',
                'description' => 'Gestion des achats et des fournisseurs.',
                'responsible_email' => 'achats@example.com',
                'code' => 'ACH'
            ],

        ];

        foreach ($services as $data) {
            $user = User::where('email', $data['responsible_email'])->first();

            Service::updateOrCreate(
                ['name' => $data['name']],
                [
                    'description' => $data['description'],
                    'responsible_id' => $user?->id,
                    'code' => $data['code']
                ]
            );
        }
    }
}
