<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Intervention;
use App\Models\ImportHistory;
use Illuminate\Support\Facades\DB;

class AssociateHistoricalImportCommand extends Command
{
    protected $signature = 'interventions:associate-historical-import';
    protected $description = 'Associe les interventions existantes sans import_id à un nouvel import historique';

    public function handle()
    {
        try {
            DB::beginTransaction();

            // Compter les interventions sans import_id
            $count = Intervention::whereNull('import_id')->count();

            if ($count === 0) {
                $this->info('Aucune intervention à associer.');
                return 0;
            }

            // Créer un nouvel import historique
            $import = ImportHistory::create([
                'filename' => 'Import historique',
                'records_count' => $count,
                'import_date' => now(),
                'status' => 'success',
                'errors' => null
            ]);

            // Associer toutes les interventions sans import_id à ce nouvel import
            Intervention::whereNull('import_id')->update([
                'import_id' => $import->id
            ]);

            DB::commit();

            $this->info("$count interventions ont été associées à l'import historique #" . $import->id);
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Une erreur est survenue : ' . $e->getMessage());
            return 1;
        }
    }
}
