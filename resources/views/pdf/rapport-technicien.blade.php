<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport - {{ $technicien }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .total {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport d'activité - {{ $technicien }}</h1>
        <p>Période : {{ $periode['debut'] }} au {{ $periode['fin'] }}</p>
    </div>

    <div class="section">
        <h2>Statistiques par type de service</h2>
        <table>
            <thead>
                <tr>
                    <th>Type de service</th>
                    <th>Interventions</th>
                    <th>Heures</th>
                    <th>Revenus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats->par_service as $type => $stat)
                    <tr>
                        <td>{{ ucfirst($type) }}</td>
                        <td>{{ $stat->interventions }}</td>
                        <td>{{ $stat->heures }}h</td>
                        <td>{{ number_format($stat->revenus, 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Évolution mensuelle</h2>
        <table>
            <thead>
                <tr>
                    <th>Mois</th>
                    <th>Interventions</th>
                    <th>Heures</th>
                    <th>Revenus</th>
                </tr>
            </thead>
            <tbody>
                @foreach($stats->evolution as $mois)
                    <tr>
                        <td>{{ $mois->mois }}</td>
                        <td>{{ $mois->interventions }}</td>
                        <td>{{ $mois->heures }}h</td>
                        <td>{{ number_format($mois->revenus, 2, ',', ' ') }} €</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Totaux</h2>
        <table>
            <tr>
                <th>Total Interventions</th>
                <td>{{ collect($stats->evolution)->sum('interventions') }}</td>
            </tr>
            <tr>
                <th>Total Heures</th>
                <td>{{ collect($stats->evolution)->sum('heures') }}h</td>
            </tr>
            <tr>
                <th>Total Revenus</th>
                <td>{{ number_format(collect($stats->evolution)->sum('revenus'), 2, ',', ' ') }} €</td>
            </tr>
        </table>
    </div>
</body>
</html>
