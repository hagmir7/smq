<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ---- Roles ----
        $roles = [
            'commercial'    => 'Commercial',
            'dr_commercial' => 'Directeur Commercial',
            'smq'           => 'SMQ',
            'admin'         => 'Administrateur',
            'dr_general'    => 'Directeur Général',
        ];

        foreach ($roles as $name => $label) {
            Role::updateOrCreate(
                ['name' => $name],
                ['label' => $label]
            );
        }

        // ---- Permission categories (grouping key => French label) ----
        $categoryLabels = [
            'reclamation'          => 'Réclamations',
            'action_corrective'    => 'Actions Correctives',
            'fiche_amelioration'   => "Fiches d'Amélioration",
            'action_amelioration'  => "Actions d'Amélioration",
            'journal_amelioration' => "Journal d'Amélioration",
            'registre_reclamation' => 'Registre des Réclamations',
            'utilisateur'          => 'Utilisateurs',
            'connexion'            => 'Connexions',
            'processus'            => 'Processus',
            'role'                 => 'Rôles',
        ];

        // ---- Permissions: name => category key ----
        $permissions = [
            // Réclamations
            'voir.reclamations'              => 'reclamation',
            'voir.reclamation'               => 'reclamation',
            'creer.reclamation'              => 'reclamation',
            'modifier.reclamation'           => 'reclamation',
            'supprimer.reclamation'          => 'reclamation',
            'restaurer.reclamation'          => 'reclamation',
            'assigner.reclamation'           => 'reclamation',
            'valider.reclamation'            => 'reclamation',
            'analyse.reclamation'            => 'reclamation',
            'changer_statut.reclamation'     => 'reclamation',
            'cloturer.reclamation'           => 'reclamation',
            'telecharger_pieces.reclamation' => 'reclamation',
            'reouvrir.reclamation'           => 'reclamation',
            'imprimer.reclamation'           => 'reclamation',

            // Actions Correctives
            'voir.actions_correctives'       => 'action_corrective',
            'voir.action_corrective'         => 'action_corrective',
            'creer.action_corrective'        => 'action_corrective',
            'modifier.action_corrective'     => 'action_corrective',
            'supprimer.action_corrective'    => 'action_corrective',
            'assigner.action_corrective'     => 'action_corrective',
            'changer_statut.action_corrective' => 'action_corrective',
            'cloturer.action_corrective'     => 'action_corrective',
            'reouvrir.action_corrective'     => 'action_corrective',

            // Fiches d'Amélioration
            'voir.fiches_amelioration'       => 'fiche_amelioration',
            'voir.fiche_amelioration'        => 'fiche_amelioration',
            'creer.fiche_amelioration'       => 'fiche_amelioration',
            'modifier.fiche_amelioration'    => 'fiche_amelioration',
            'supprimer.fiche_amelioration'   => 'fiche_amelioration',
            'cloturer.fiche_amelioration'    => 'fiche_amelioration',
            'evaluer.fiche_amelioration'     => 'fiche_amelioration',
            'imprimer.fiche_amelioration'    => 'fiche_amelioration',

            // Actions d'Amélioration
            'voir.action_ameliorations'      => 'action_amelioration',
            'voir.action_amelioration'       => 'action_amelioration',
            'creer.action_amelioration'      => 'action_amelioration',
            'modifier.action_amelioration'   => 'action_amelioration',
            'supprimer.action_amelioration'  => 'action_amelioration',
            'cloturer.action_amelioration'   => 'action_amelioration',
            'evaluer.action_amelioration'    => 'action_amelioration',

            // Journal d'Amélioration
            'voir.journal_amelioration'      => 'journal_amelioration',
            'creer.journal_amelioration'     => 'journal_amelioration',
            'modifier.journal_amelioration'  => 'journal_amelioration',
            'supprimer.journal_amelioration' => 'journal_amelioration',

            // Registre des Réclamations
            'voir.registre_reclamations'     => 'registre_reclamation',
            'creer.registre_reclamation'     => 'registre_reclamation',
            'modifier.registre_reclamation'  => 'registre_reclamation',
            'supprimer.registre_reclamation' => 'registre_reclamation',

            // Utilisateurs
            'voir.utilisateurs'                       => 'utilisateur',
            'voir.utilisateur'                        => 'utilisateur',
            'creer.utilisateur'                       => 'utilisateur',
            'modifier.utilisateur'                    => 'utilisateur',
            'supprimer.utilisateur'                   => 'utilisateur',
            'restaurer.utilisateur'                   => 'utilisateur',
            'activer.utilisateur'                     => 'utilisateur',
            'desactiver.utilisateur'                  => 'utilisateur',
            'reinitialiser_mot_de_passe.utilisateur'  => 'utilisateur',
            'assigner_roles.utilisateur'              => 'utilisateur',
            'gerer_permissions.utilisateur'           => 'utilisateur',
            'exporter.utilisateurs'                   => 'utilisateur',
            'importer.utilisateurs'                   => 'utilisateur',

            // Connexions
            'voir.connexions'      => 'connexion',
            'creer.connexion'      => 'connexion',
            'modifier.connexion'   => 'connexion',
            'supprimer.connexion'  => 'connexion',
            'tester.connexion'     => 'connexion',

            // Processus
            'voir.processus'       => 'processus',
            'creer.processus'      => 'processus',
            'modifier.processus'   => 'processus',
            'supprimer.processus'  => 'processus',

            // Rôles
            'voir.roles'                       => 'role',
            'voir.role'                        => 'role',
            'creer.role'                       => 'role',
            'modifier.role'                    => 'role',
            'supprimer.role'                   => 'role',
            'restaurer.role'                   => 'role',
            'supprimer_definitivement.role'    => 'role',
            'assigner_permissions.role'        => 'role',
        ];

        foreach ($permissions as $name => $categoryKey) {
            Permission::updateOrCreate(
                ['name' => $name],
                [
                    'category'       => $categoryKey,
                    'category_label' => $categoryLabels[$categoryKey] ?? ucfirst($categoryKey),
                ]
            );
        }

        // ---- Grant admin everything ----
        $adminRole = Role::findByName('admin');
        $adminRole->syncPermissions(Permission::all());
    }
}