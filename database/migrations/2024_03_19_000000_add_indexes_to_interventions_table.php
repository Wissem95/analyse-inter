<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vérifier et créer les index s'ils n'existent pas
        $indexes = [
            'interventions_technicien_index' => ['technicien'],
            'interventions_date_intervention_index' => ['date_intervention'],
            'interventions_technicien_date_intervention_index' => ['technicien', 'date_intervention'],
            'interventions_type_intervention_type_operation_type_habitation_index' => ['type_intervention', 'type_operation', 'type_habitation'],
            'interventions_technicien_date_intervention_prix_index' => ['technicien', 'date_intervention', 'prix'],
            'interventions_technicien_date_intervention_revenus_percus_index' => ['technicien', 'date_intervention', 'revenus_percus']
        ];

        foreach ($indexes as $indexName => $columns) {
            // Vérifier si l'index existe
            $indexExists = DB::select("
                SELECT 1
                FROM pg_indexes
                WHERE tablename = 'interventions'
                AND indexname = ?
            ", [$indexName]);

            if (empty($indexExists)) {
                $columnList = implode('", "', $columns);
                DB::statement("CREATE INDEX {$indexName} ON interventions (\"{$columnList}\")");
            }
        }
    }

    public function down(): void
    {
        $indexes = [
            'interventions_technicien_index',
            'interventions_date_intervention_index',
            'interventions_technicien_date_intervention_index',
            'interventions_type_intervention_type_operation_type_habitation_index',
            'interventions_technicien_date_intervention_prix_index',
            'interventions_technicien_date_intervention_revenus_percus_index'
        ];

        foreach ($indexes as $indexName) {
            // Vérifier si l'index existe avant de le supprimer
            $indexExists = DB::select("
                SELECT 1
                FROM pg_indexes
                WHERE tablename = 'interventions'
                AND indexname = ?
            ", [$indexName]);

            if (!empty($indexExists)) {
                DB::statement("DROP INDEX IF EXISTS {$indexName}");
            }
        }
    }
};
