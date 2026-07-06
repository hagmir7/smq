<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'code' => 'USR0001',
                'full_name' => 'Administrateur',
                'email' => 'admin@example.com',
                'service' => 'Direction Générale',
            ],
            [
                'code' => 'USR0000',
                'full_name' => 'Hassan Agmir',
                'email' => 'admin@admin.com',
                'service' => 'Direction Générale',
            ],
            [
                'code' => 'USR0002',
                'full_name' => 'Responsable Qualité',
                'email' => 'qualite@example.com',
                'service' => 'Qualité',
            ],
            [
                'code' => 'USR0003',
                'full_name' => 'Responsable Production',
                'email' => 'production@example.com',
                'service' => 'Production',
            ],
            [
                'code' => 'USR0004',
                'full_name' => 'Responsable Maintenance',
                'email' => 'maintenance@example.com',
                'service' => 'Maintenance',
            ],
            [
                'code' => 'USR0005',
                'full_name' => 'Responsable Logistique',
                'email' => 'logistique@example.com',
                'service' => 'Logistique',
            ],
            [
                'code' => 'USR0006',
                'full_name' => 'Responsable Achats',
                'email' => 'achats@example.com',
                'service' => 'Achats',
            ],
            [
                'code' => 'USR0007',
                'full_name' => 'Responsable Commercial',
                'email' => 'commercial@example.com',
                'service' => 'Commercial',
            ],
            [
                'code' => 'USR0008',
                'full_name' => 'Responsable RH',
                'email' => 'rh@example.com',
                'service' => 'Ressources Humaines',
            ],
            [
                'code' => 'USR0009',
                'full_name' => 'Responsable Finance',
                'email' => 'finance@example.com',
                'service' => 'Finance',
            ],
            [
                'code' => 'USR0010',
                'full_name' => 'Responsable Informatique',
                'email' => 'it@example.com',
                'service' => 'Informatique',
            ],
        ];

        foreach ($users as $data) {
            $service = Service::where('name', $data['service'])->first();

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'code' => $data['code'],
                    'full_name' => $data['full_name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'service_id' => $service?->id,
                    'company_id' => 1, // Change if needed
                ]
            );

            // Make this user responsible for the service
            if ($service && $service->responsible_id !== $user->id) {
                $service->update([
                    'responsible_id' => $user->id,
                ]);
            }
        }
    }
}