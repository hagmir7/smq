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
                'code' => 'IC276',
                'full_name' => 'Administrateur',
                'email' => 'webmaster@intercocina.com',
                'service' => 'RH & SI',
            ],
            [
                'code' => 'IC33',
                'full_name' => 'ESSIKILI Jihad',
                'email' => 'essjihad@intercocina.com',
                'service' => 'Direction Générale',
            ],
            [
                'code' => 'IC303',
                'full_name' => 'LAMKADMI Khadija',
                'email' => 'smq@intercocina.com',
                'service' => 'Qualité',
            ],
            [
                'code' => 'IC59',
                'full_name' => 'ACHOUKHI Assim',
                'email' => 'ac.assim@intercocina.com',
                'service' => 'RH & SI',
            ],
            [
                'code' => 'IC219',
                'full_name' => 'LAHCHIRI Aimad',
                'email' => 'qualite@intercocina.com',
                'service' => 'Contrôle qualité / HSE',
            ],
            [
                'code' => 'IC229',
                'full_name' => 'JABRAN Nada',
                'email' => 'achat@intercocina.com',
                'service' => 'Achat',
            ],
            [
                'code' => 'IC233',
                'full_name' => 'AKOUAOUCHI Oussama',
                'email' => 'maintenance@intercocina.com',
                'service' => 'Maintenance',
            ],
            [
                'code' => 'IC269',
                'full_name' => 'MRIMOU Ikram',
                'email' => 'fabrication@intercocina.com',
                'service' => 'Fabrication',
            ],
            [
                'code' => 'IC309',
                'full_name' => 'MAAZOUZI Abdellah',
                'email' => 'stock@intercocina.com',
                'service' => 'Stock et Expédition',
            ],
            [
                'code' => 'IC322',
                'full_name' => 'MEHDI Mimouna',
                'email' => 'marketing@intercocina.com',
                'service' => 'Marketing',
            ],
            [
                'code' => 'ST001',
                'full_name' => 'AOURAGH Abid',
                'email' => 'abid@intercocina.com',
                'service' => 'Commercial',
            ],

            [
                'code' => 'ST010',
                'full_name' => 'EL GHOUBNI Abdeladim',
                'email' => 'abid@intercocina.com',
                'service' => 'Commercial',
            ],

            [
                'code' => 'IC239',
                'full_name' => 'MAZOUZ Ilhame',
                'email' => 'abid@intercocina.com',
                'service' => 'Commercial',
            ],
        ];

        foreach ($users as $data) {
            $service = Service::where('name', $data['service'])->first();

            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'code' => $data['code'],
                    'full_name' => $data['full_name'],
                    'password' => Hash::make($data['code']),
                    'is_active' => true,
                    'service_id' => $service?->id,
                    'company_id' => 1,
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