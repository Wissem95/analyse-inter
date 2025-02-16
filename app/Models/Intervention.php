<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Intervention extends Model
{
    protected $fillable = [
        'date_intervention',
        'technicien',
        'type_intervention',
        'type_operation',
        'type_habitation',
        'prix'
    ];

    protected $casts = [
        'date_intervention' => 'date',
        'prix' => 'decimal:2'
    ];

    // Retourne les statistiques mensuelles
    public static function getStatsParMois()
    {
        return self::selectRaw('
                strftime("%Y-%m", date_intervention) as mois,
                COUNT(*) as total,
                COUNT(CASE WHEN type_intervention = "SAV" THEN 1 END) as sav,
                COUNT(CASE WHEN type_intervention = "RACC" AND type_operation = "reconnexion" THEN 1 END) as reconnexions,
                COUNT(CASE WHEN type_intervention = "RACC" AND type_operation = "raccordement" AND type_habitation = "immeuble" THEN 1 END) as raccordements_immeuble,
                COUNT(CASE WHEN type_intervention = "RACC" AND type_operation = "raccordement" AND type_habitation = "pavillon" THEN 1 END) as raccordements_pavillon,
                COUNT(CASE WHEN type_intervention = "PRESTA" AND type_habitation = "immeuble" THEN 1 END) as presta_immeuble,
                COUNT(CASE WHEN type_intervention = "PRESTA" AND type_habitation = "pavillon" THEN 1 END) as presta_pavillon,
                SUM(CASE WHEN type_intervention = "SAV" THEN prix END) as revenus_sav,
                SUM(CASE WHEN type_intervention = "RACC" AND type_operation = "reconnexion" THEN prix END) as revenus_reconnexions,
                SUM(CASE WHEN type_intervention = "RACC" AND type_operation = "raccordement" AND type_habitation = "immeuble" THEN prix END) as revenus_raccordements_immeuble,
                SUM(CASE WHEN type_intervention = "RACC" AND type_operation = "raccordement" AND type_habitation = "pavillon" THEN prix END) as revenus_raccordements_pavillon,
                SUM(CASE WHEN type_intervention = "PRESTA" AND type_habitation = "immeuble" THEN prix END) as revenus_presta_immeuble,
                SUM(CASE WHEN type_intervention = "PRESTA" AND type_habitation = "pavillon" THEN prix END) as revenus_presta_pavillon,
                SUM(prix) as revenus_total
            ')
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();
    }
}
