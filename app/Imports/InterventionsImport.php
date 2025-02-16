<?php

namespace App\Imports;

use App\Models\Intervention;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use League\Csv\Reader;
use League\Csv\Statement;

class InterventionsImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    WithBatchInserts,
    WithChunkReading,
    WithCustomCsvSettings
{
    private $rowCount = 0;
    private $importErrors = [];
    private $prixMapping = [
        'raccordement_pavillon' => 45,
        'raccordement_immeuble' => 25,
        'reconnexion_pavillon' => 15,
        'reconnexion_immeuble' => 15,
        'sav_pavillon' => 5,
        'sav_immeuble' => 5,
        'default' => 5
    ];

    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'enclosure' => '"',
            'input_encoding' => 'ISO-8859-1'
        ];
    }

    public function model(array $row)
    {
        $this->rowCount++;

        try {
            // Récupération des données des colonnes spécifiques
            $date = $this->transformDate($row['date_de_rdv']); // Colonne A
            $technicien = $this->formatTechnicienName(
                trim($row['nom_technicien']), // Colonne E
                trim($row['prenom_technicien']) // Colonne F
            );
            $typeIntervention = strtoupper(trim($row['type'] ?? '')); // Colonne I
            $typeOperation = strtolower(trim($row['type_operation'] ?? '')); // Colonne BI
            $typeHabitation = strtolower(trim($row['type_habitation'] ?? '')); // Colonne BJ

            // Calcul du prix
            $prix = $this->determinePrix($typeIntervention, $typeOperation, $typeHabitation);

            return new Intervention([
                'date_intervention' => $date,
                'technicien' => $technicien,
                'type_intervention' => $typeIntervention,
                'type_operation' => $typeOperation,
                'type_habitation' => $typeHabitation,
                'prix' => $prix
            ]);
        } catch (\Exception $e) {
            $this->importErrors[] = "Ligne {$this->rowCount}: {$e->getMessage()}";
            return null;
        }
    }

    private function determinePrix($typeIntervention, $typeOperation, $typeHabitation): float
    {
        $key = '';

        if ($typeOperation === 'raccordement') {
            $key = 'raccordement_' . $typeHabitation;
        } elseif ($typeOperation === 'reconnexion') {
            $key = 'reconnexion_' . $typeHabitation;
        } elseif ($typeIntervention === 'SAV') {
            $key = 'sav_' . $typeHabitation;
        }

        return (float) ($this->prixMapping[$key] ?? $this->prixMapping['default']);
    }

    private function formatTechnicienName($nom, $prenom): string
    {
        $nom = trim(str_replace('"', '', $nom));
        $prenom = trim(str_replace('"', '', $prenom));
        return ucwords(strtolower($prenom . ' ' . $nom));
    }

    private function transformDate($value): string
    {
        if (empty($value)) {
            throw new \Exception('La date est requise');
        }
        return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'date_de_rdv' => 'required|date_format:Y-m-d',
            'nom_technicien' => 'required|string',
            'prenom_technicien' => 'required|string',
            'type' => 'required|string'
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }
}
