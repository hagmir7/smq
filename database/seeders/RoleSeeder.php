<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'commercial',
            'dr_commercial',
            'smq',
            'admin',
            'dr_general',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
            ]);
        }

        $permissions = [
            'voir.reclamations',
            'voir.reclamation',
            'creer.reclamation',
            'modifier.reclamation',
            'supprimer.reclamation',
            'restaurer.reclamation',
            'assigner.reclamation',
            'valider.reclamation',
            'analyse.reclamation',
            'changer_statut.reclamation',
            'cloturer.reclamation',
            'telecharger_pieces.reclamation',
            'reouvrir.reclamation',

            'voir.actions_correctives',
            'voir.action_corrective',
            'creer.action_corrective',
            'modifier.action_corrective',
            'supprimer.action_corrective',
            'assigner.action_corrective',
            'changer_statut.action_corrective',
            'cloturer.action_corrective',
            'reouvrir.action_corrective',

            'voir.fiches_amelioration',
            'voir.fiche_amelioration',
            'creer.fiche_amelioration',
            'modifier.fiche_amelioration',
            'supprimer.fiche_amelioration',
            'cloturer.fiche_amelioration',

            'voir.journal_amelioration',
            'creer.journal_amelioration',
            'modifier.journal_amelioration',
            'supprimer.journal_amelioration',

            'voir.registre_reclamations',
            'creer.registre_reclamation',
            'modifier.registre_reclamation',
            'supprimer.registre_reclamation',

            'voir.utilisateurs',
            'voir.utilisateur',
            'creer.utilisateur',
            'modifier.utilisateur',
            'supprimer.utilisateur',
            'restaurer.utilisateur',
            'activer.utilisateur',
            'desactiver.utilisateur',
            'reinitialiser_mot_de_passe.utilisateur',
            'assigner_roles.utilisateur',
            'gerer_permissions.utilisateur',
            'exporter.utilisateurs',
            'importer.utilisateurs',

            'voir.connexions',
            'creer.connexion',
            'modifier.connexion',
            'supprimer.connexion',
            'tester.connexion',

            'voir.roles',
            'voir.role',
            'creer.role',
            'modifier.role',
            'supprimer.role',
            'restaurer.role',
            'supprimer_definitivement.role',
            'assigner_permissions.role',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());
    }
}