<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Imports\InterventionsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ImportOsirisCommand extends Command
{
    protected $signature = 'import:osiris {file}';
    protected $description = 'Importe les interventions depuis un fichier CSV Osiris';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Le fichier {$filePath} n'existe pas.");
            return 1;
        }

        try {
            $this->info('Début de l\'importation...');

            $import = new InterventionsImport();
            Excel::import($import, $filePath);

            $errors = $import->getErrors();
            $count = $import->getRowCount();

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->warn($error);
                }

                if ($count > count($errors)) {
                    $this->info("Import partiellement réussi : " . ($count - count($errors)) . " lignes importées avec succès.");
                } else {
                    $this->error("Échec de l'import.");
                    return 1;
                }
            } else {
                $this->info("Import réussi : {$count} lignes importées.");
            }

            return 0;
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'import Osiris : ' . $e->getMessage());
            $this->error('Une erreur est survenue lors de l\'import : ' . $e->getMessage());
            return 1;
        }
    }
}
