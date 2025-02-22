<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InterventionsImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\DB;
use App\Models\ImportHistory;

class InterventionController extends Controller
{
    public function import(Request $request)
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,txt,xls,xlsx|max:16384'
            ]);

            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun fichier n\'a été envoyé'
                ], 422);
            }

            $file = $request->file('file');

            if (!$file->isReadable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier ne peut pas être lu'
                ], 422);
            }

            if ($file->getSize() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le fichier est vide'
                ], 422);
            }

            Log::info('Début de l\'import du fichier: ' . $file->getClientOriginalName());

            // Créer l'historique de l'import d'abord
            $importHistory = ImportHistory::create([
                'filename' => $file->getClientOriginalName(),
                'records_count' => 0, // Sera mis à jour après l'import
                'import_date' => now(),
                'status' => 'pending',
                'errors' => []
            ]);

            $import = new InterventionsImport($importHistory->id);
            Excel::import($import, $file);

            $errors = $import->getErrors();
            $count = $import->getRowCount();

            // Mettre à jour l'historique de l'import
            $status = count($errors) === 0 ? 'success' : (count($errors) < $count ? 'partial' : 'failed');
            $importHistory->update([
                'records_count' => $count - count($errors),
                'status' => $status,
                'errors' => $errors
            ]);

            if (count($errors) > 0) {
                if ($count > count($errors)) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Import partiellement réussi',
                        'count' => $count - count($errors),
                        'errors' => $errors
                    ], 207);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Échec de l\'import',
                    'errors' => $errors
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Import réussi',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'import: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function stats(Request $request)
    {
        try {
            // Construire la requête de base
            $query = Intervention::query();

            // Appliquer les filtres de date si présents
            if ($request->has('debut') && $request->has('fin')) {
                $query->whereBetween('date_intervention', [
                    Carbon::parse($request->debut),
                    Carbon::parse($request->fin)
                ]);
            }

            // Statistiques globales avec le filtre de date
            $global = [
                'total_interventions' => (int) $query->count(),
                'total_revenus' => (float) $query->sum('prix'),
                'total_revenus_percus' => (float) $query->sum('revenus_percus')
            ];

            // Statistiques par technicien avec le filtre de date
            $parTechnicien = $query->select('technicien')
                ->selectRaw('COUNT(*) as interventions')
                ->selectRaw('SUM(prix) as revenus')
                ->selectRaw('SUM(revenus_percus) as revenus_percus')
                ->groupBy('technicien')
                ->get()
                ->map(function ($item) {
                    return [
                        'technicien' => $item->technicien,
                        'interventions' => (int) $item->interventions,
                        'revenus' => (float) $item->revenus,
                        'revenus_percus' => (float) $item->revenus_percus
                    ];
                });

            // Statistiques par type d'intervention avec le filtre de date
            $parService = $query->select('type_intervention')
                ->selectRaw('COUNT(*) as interventions')
                ->selectRaw('SUM(prix) as revenus')
                ->groupBy('type_intervention')
                ->get()
                ->map(function ($item) {
                    return [
                        'type_service' => $item->type_intervention,
                        'interventions' => (int) $item->interventions,
                        'revenus' => (float) $item->revenus,
                        'heures' => 0
                    ];
                });

            // Évolution mensuelle avec le filtre de date
            $evolution = $query->selectRaw('to_char(date_intervention, \'YYYY-MM\') as mois')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(prix) as revenus')
                ->selectRaw('COUNT(CASE WHEN type_intervention = \'SAV\' THEN 1 END) as sav')
                ->selectRaw('COUNT(CASE WHEN type_intervention = \'RACC\' THEN 1 END) as racc')
                ->selectRaw('COUNT(CASE WHEN type_intervention = \'PRESTA\' THEN 1 END) as presta')
                ->selectRaw('COUNT(CASE WHEN type_operation = \'raccordement\' THEN 1 END) as raccordements')
                ->selectRaw('COUNT(CASE WHEN type_operation = \'reconnexion\' THEN 1 END) as reconnexions')
                ->selectRaw('COUNT(CASE WHEN type_habitation = \'immeuble\' THEN 1 END) as immeubles')
                ->selectRaw('COUNT(CASE WHEN type_habitation = \'pavillon\' THEN 1 END) as pavillons')
                ->groupBy('mois')
                ->orderBy('mois')
                ->get()
                ->map(function ($item) {
                    return [
                        'mois' => $item->mois,
                        'interventions' => (int) $item->total,
                        'revenus' => (float) $item->revenus,
                        'heures' => 0,
                        'details' => [
                            'sav' => (int) $item->sav,
                            'racc' => (int) $item->racc,
                            'presta' => (int) $item->presta,
                            'raccordements' => (int) $item->raccordements,
                            'reconnexions' => (int) $item->reconnexions,
                            'immeubles' => (int) $item->immeubles,
                            'pavillons' => (int) $item->pavillons
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'global' => $global,
                'par_technicien' => $parTechnicien,
                'par_service' => $parService,
                'evolution' => $evolution
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du chargement des statistiques: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function statsTechnicien(Request $request, string $technicien)
    {
        try {
            $query = Intervention::where('technicien', $technicien);

            // Appliquer les filtres de date
            if ($request->has('debut') && $request->has('fin')) {
                $query->whereBetween('date_intervention', [
                    Carbon::parse($request->debut),
                    Carbon::parse($request->fin)
                ]);
            }

            // Statistiques globales
            $global = [
                'total_interventions' => (int) $query->count(),
                'total_revenus' => (float) $query->sum('prix'),
                'total_revenus_percus' => (float) $query->sum('revenus_percus')
            ];

            // Statistiques par type d'intervention
            $parService = Intervention::where('technicien', $technicien)
                ->select('type_intervention')
                ->selectRaw('COUNT(*) as interventions')
                ->selectRaw('SUM(prix) as revenus')
                ->groupBy('type_intervention')
                ->get()
                ->map(function ($item) {
                    return [
                        'type_service' => $item->type_intervention,
                        'interventions' => (int) $item->interventions,
                        'revenus' => (float) $item->revenus
                    ];
                });

            // Évolution mensuelle détaillée
            $evolution = Intervention::where('technicien', $technicien)
                ->selectRaw('to_char(date_intervention, \'YYYY-MM\') as mois')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('COUNT(CASE WHEN type_intervention = \'SAV\' THEN 1 END) as sav')
                ->selectRaw('SUM(CASE WHEN type_intervention = \'SAV\' THEN prix ELSE 0 END) as revenus_sav')
                ->selectRaw('COUNT(CASE WHEN type_intervention = \'RACC\' AND type_operation = \'reconnexion\' THEN 1 END) as reconnexions')
                ->selectRaw('SUM(CASE WHEN type_intervention = \'RACC\' AND type_operation = \'reconnexion\' THEN prix ELSE 0 END) as revenus_reconnexions')
                ->selectRaw('COUNT(CASE WHEN type_intervention = \'RACC\' AND type_operation = \'raccordement\' AND type_habitation = \'immeuble\' THEN 1 END) as raccordements_immeuble')
                ->selectRaw('SUM(CASE WHEN type_intervention = \'RACC\' AND type_operation = \'raccordement\' AND type_habitation = \'immeuble\' THEN prix ELSE 0 END) as revenus_raccordements_immeuble')
                ->selectRaw('COUNT(CASE WHEN type_intervention = \'RACC\' AND type_operation = \'raccordement\' AND type_habitation = \'pavillon\' THEN 1 END) as raccordements_pavillon')
                ->selectRaw('SUM(CASE WHEN type_intervention = \'RACC\' AND type_operation = \'raccordement\' AND type_habitation = \'pavillon\' THEN prix ELSE 0 END) as revenus_raccordements_pavillon')
                ->selectRaw('COUNT(CASE WHEN type_intervention = \'PRESTA\' AND type_habitation = \'immeuble\' THEN 1 END) as presta_immeuble')
                ->selectRaw('SUM(CASE WHEN type_intervention = \'PRESTA\' AND type_habitation = \'immeuble\' THEN prix ELSE 0 END) as revenus_presta_immeuble')
                ->selectRaw('COUNT(CASE WHEN type_intervention = \'PRESTA\' AND type_habitation = \'pavillon\' THEN 1 END) as presta_pavillon')
                ->selectRaw('SUM(CASE WHEN type_intervention = \'PRESTA\' AND type_habitation = \'pavillon\' THEN prix ELSE 0 END) as revenus_presta_pavillon')
                ->selectRaw('SUM(prix) as revenus_total')
                ->selectRaw('MAX(revenus_percus) as revenus_percus')
                ->groupBy('mois')
                ->orderBy('mois')
                ->get()
                ->map(function ($item) {
                    return [
                        'mois' => $item->mois,
                        'total' => (int) $item->total,
                        'sav' => (int) $item->sav,
                        'revenus_sav' => (float) $item->revenus_sav,
                        'reconnexions' => (int) $item->reconnexions,
                        'revenus_reconnexions' => (float) $item->revenus_reconnexions,
                        'raccordements_immeuble' => (int) $item->raccordements_immeuble,
                        'revenus_raccordements_immeuble' => (float) $item->revenus_raccordements_immeuble,
                        'raccordements_pavillon' => (int) $item->raccordements_pavillon,
                        'revenus_raccordements_pavillon' => (float) $item->revenus_raccordements_pavillon,
                        'presta_immeuble' => (int) $item->presta_immeuble,
                        'revenus_presta_immeuble' => (float) $item->revenus_presta_immeuble,
                        'presta_pavillon' => (int) $item->presta_pavillon,
                        'revenus_presta_pavillon' => (float) $item->revenus_presta_pavillon,
                        'revenus_total' => (float) $item->revenus_total,
                        'revenus_percus' => (float) $item->revenus_percus
                    ];
                });

            return response()->json([
                'success' => true,
                'technicien' => $technicien,
                'global' => $global,
                'par_service' => $parService,
                'evolution' => $evolution
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du chargement des statistiques du technicien: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques du technicien',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function exportPDF(Request $request, $technicien)
    {
        try {
            $stats = $this->statsTechnicien($request, $technicien)->getData(true);

            $pdf = PDF::loadView('pdf.rapport-technicien', [
                'technicien' => $technicien,
                'stats' => $stats,
                'periode' => [
                    'debut' => $request->get('debut', 'Début'),
                    'fin' => $request->get('fin', 'Aujourd\'hui')
                ]
            ]);

            return $pdf->download("rapport_{$technicien}.pdf");
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'export PDF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'export PDF',
                'error' => $e->getMessage()
            ], 500, [
                'Content-Type' => 'application/json;charset=UTF-8'
            ]);
        }
    }

    public function updatePrestaRevenue(Request $request)
    {
        $request->validate([
            'mois' => 'required|date_format:Y-m',
            'type_habitation' => 'required|in:immeuble,pavillon',
            'revenus' => 'required|numeric|min:0',
            'nombre' => 'required|integer|min:0',
            'technicien' => 'required|string'
        ]);

        $startDate = $request->mois . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        try {
            DB::beginTransaction();

            // Supprimer toutes les prestations existantes pour ce mois/type en une seule requête
            Intervention::where('type_intervention', 'PRESTA')
                ->where('type_habitation', $request->type_habitation)
                ->where('technicien', $request->technicien)
                ->whereBetween('date_intervention', [$startDate, $endDate])
                ->delete();

            // Créer toutes les prestations en une seule requête
            if ($request->nombre > 0) {
                $prestations = array_fill(0, $request->nombre, [
                    'date_intervention' => $startDate,
                    'technicien' => $request->technicien,
                    'type_intervention' => 'PRESTA',
                    'type_habitation' => $request->type_habitation,
                    'prix' => $request->revenus,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Insertion en masse
                Intervention::insert($prestations);
            }

            DB::commit();
            return response()->json(['success' => true, 'count' => $request->nombre]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour des prestations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des prestations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateRevenuPercu(Request $request)
    {
        $request->validate([
            'mois' => 'required|date_format:Y-m',
            'technicien' => 'required|string',
            'revenus_percus' => 'required|numeric|min:0'
        ]);

        $startDate = $request->mois . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        try {
            DB::beginTransaction();

            // Mise à jour en une seule requête avec CASE
            DB::statement("
                UPDATE interventions
                SET revenus_percus = CASE
                    WHEN id = (
                        SELECT id
                        FROM interventions
                        WHERE technicien = ?
                        AND date_intervention BETWEEN ? AND ?
                        ORDER BY date_intervention
                        LIMIT 1
                    ) THEN ?
                    ELSE 0
                END
                WHERE technicien = ?
                AND date_intervention BETWEEN ? AND ?
            ", [
                $request->technicien,
                $startDate,
                $endDate,
                $request->revenus_percus,
                $request->technicien,
                $startDate,
                $endDate
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la mise à jour des revenus perçus: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour des revenus perçus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function importHistory()
    {
        try {
            $history = ImportHistory::orderBy('import_date', 'desc')->get();
            return response()->json([
                'success' => true,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique des imports: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'historique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteImport($id)
    {
        try {
            DB::beginTransaction();

            $import = ImportHistory::findOrFail($id);

            // Supprimer uniquement les interventions liées à cet import
            Intervention::where('import_id', $id)->delete();

            // Marquer l'import comme supprimé
            $import->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Import supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la suppression de l\'import: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'import',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
