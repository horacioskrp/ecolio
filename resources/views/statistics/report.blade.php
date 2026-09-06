@php
    $titles = ['effectifs' => 'Effectifs & parité', 'finances' => 'Finances & recouvrement', 'reussite' => 'Réussite & examens officiels'];
    $title = $titles[$section] ?? 'Statistiques';
    $methodLabels = ['CASH' => 'Espèces', 'MOBILE_MONEY' => 'Mobile Money', 'BANK_TRANSFER' => 'Virement', 'CHEQUE' => 'Chèque'];
    $money = fn ($n) => number_format((float) $n, 0, ',', ' ') . ' ' . ($currency ?? 'FCFA');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8">
<style>
    * { font-family: DejaVu Sans, sans-serif; }
    body { font-size: 11px; color: #1e293b; }
    h1 { font-size: 16px; margin: 8px 0 2px; }
    .sub { color: #64748b; font-size: 10px; margin-bottom: 14px; }
    h2 { font-size: 12px; margin: 18px 0 6px; color: #1d4ed8; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    th, td { border: 1px solid #e2e8f0; padding: 5px 7px; text-align: left; }
    th { background: #f1f5f9; font-size: 10px; text-transform: uppercase; }
    td.n, th.n { text-align: right; }
    .kpis td { font-weight: bold; }
    {{-- En-tête ministérielle unifiée (dont le plafond de taille du logo). --}}
    {!! $headerCss !!}
</style>
</head>
<body>
    {!! $header !!}

    <h1>Statistiques — {{ $title }}</h1>
    <div class="sub">Année scolaire {{ $year ?? '—' }} · Édité le {{ $date }}</div>

    @if ($section === 'effectifs')
        <h2>Synthèse</h2>
        <table class="kpis">
            <tr><td>Effectif total</td><td class="n">{{ $data['total'] }}</td>
                <td>IPS (filles/garçons)</td><td class="n">{{ $data['ips'] ?? '—' }}</td></tr>
            <tr><td>Garçons</td><td class="n">{{ $data['gender']['male'] }}</td>
                <td>Filles</td><td class="n">{{ $data['gender']['female'] }}</td></tr>
            <tr><td>Taux de promotion</td><td class="n">{{ $data['rates']['promotion'] }} %</td>
                <td>Taux de redoublement</td><td class="n">{{ $data['rates']['redoublement'] }} %</td></tr>
            <tr><td>Taux d'abandon</td><td class="n">{{ $data['rates']['abandon'] }} %</td>
                <td>Âge moyen</td><td class="n">{{ $data['age_moyen'] ?? '—' }}</td></tr>
            <tr><td>Taux de sur-âge (≥ +{{ $data['over_age']['threshold'] }} ans)</td>
                <td class="n">{{ $data['over_age']['evaluated'] ? $data['over_age']['rate'] . ' %' : '—' }}</td>
                <td>Élèves en sur-âge</td><td class="n">{{ $data['over_age']['count'] }} / {{ $data['over_age']['evaluated'] }}</td></tr>
        </table>

        <h2>Effectifs par classe</h2>
        <table>
            <tr><th>Classe</th><th class="n">Garçons</th><th class="n">Filles</th><th class="n">Total</th></tr>
            @foreach ($data['by_class'] as $c)
                <tr><td>{{ $c['name'] }}</td><td class="n">{{ $c['male'] }}</td><td class="n">{{ $c['female'] }}</td><td class="n">{{ $c['total'] }}</td></tr>
            @endforeach
        </table>

        @if (count($data['by_city']))
            <h2>Origine géographique (top villes)</h2>
            <table>
                <tr><th>Ville</th><th class="n">Élèves</th></tr>
                @foreach ($data['by_city'] as $c)
                    <tr><td>{{ $c['city'] }}</td><td class="n">{{ $c['total'] }}</td></tr>
                @endforeach
            </table>
        @endif
    @elseif ($section === 'finances')
        <h2>Synthèse</h2>
        <table class="kpis">
            <tr><td>Total facturé</td><td class="n">{{ $money($data['billed']) }}</td>
                <td>Total encaissé</td><td class="n">{{ $money($data['collected']) }}</td></tr>
            <tr><td>Reste à recouvrer</td><td class="n">{{ $money($data['remaining']) }}</td>
                <td>Taux de recouvrement</td><td class="n">{{ $data['recovery_rate'] }} %</td></tr>
            <tr><td>Soldées / Partielles / Impayées</td>
                <td class="n">{{ $data['paid_count'] }} / {{ $data['partial_count'] }} / {{ $data['unpaid_count'] }}</td>
                <td>Factures</td><td class="n">{{ $data['total_invoices'] }}</td></tr>
        </table>

        <h2>Recouvrement par classe</h2>
        <table>
            <tr><th>Classe</th><th class="n">Facturé</th><th class="n">Encaissé</th><th class="n">Taux</th></tr>
            @foreach ($data['by_class'] as $c)
                <tr><td>{{ $c['name'] }}</td><td class="n">{{ $money($c['billed']) }}</td><td class="n">{{ $money($c['collected']) }}</td><td class="n">{{ $c['rate'] }} %</td></tr>
            @endforeach
        </table>

        <h2>Répartition par mode de paiement</h2>
        <table>
            <tr><th>Mode</th><th class="n">Montant</th><th class="n">Opérations</th></tr>
            @foreach ($data['by_method'] as $m)
                <tr><td>{{ $methodLabels[$m['method']] ?? $m['method'] }}</td><td class="n">{{ $money($m['total']) }}</td><td class="n">{{ $m['count'] }}</td></tr>
            @endforeach
        </table>
    @elseif ($section === 'reussite')
        <h2>Réussite interne</h2>
        <table class="kpis">
            <tr><td>Bulletins validés</td><td class="n">{{ $data['bulletins'] }}</td>
                <td>Moyenne générale</td><td class="n">{{ $data['moyenne_generale'] ?? '—' }}</td></tr>
            <tr><td>Taux de réussite (moy. ≥ 10)</td><td class="n">{{ $data['pass_rate'] }} %</td>
                <td>Mentions (P/AB/B/TB)</td>
                <td class="n">{{ $data['mentions']['passable'] }} / {{ $data['mentions']['assez_bien'] }} / {{ $data['mentions']['bien'] }} / {{ $data['mentions']['tres_bien'] }}</td></tr>
        </table>

        <h2>Examens officiels</h2>
        <table>
            <tr><th>Examen</th><th>Type</th><th>Centre</th><th class="n">Inscrits</th><th class="n">Admis</th><th class="n">Admission</th></tr>
            @foreach ($data['exams'] as $e)
                <tr><td>{{ $e['name'] }}</td><td>{{ $e['type'] }}</td><td>{{ $e['center'] }}</td>
                    <td class="n">{{ $e['registered'] }}</td><td class="n">{{ $e['admitted'] }}</td><td class="n">{{ $e['admission_rate'] }} %</td></tr>
            @endforeach
        </table>
        <p class="sub">Taux d'admission global : <strong>{{ $data['exams_summary']['admission_rate'] }} %</strong>
            ({{ $data['exams_summary']['admitted'] }} admis / {{ $data['exams_summary']['registered'] }} inscrits)</p>
    @elseif ($section === 'encadrement')
        <h2>Encadrement</h2>
        <table class="kpis">
            <tr><td>Effectif total</td><td class="n">{{ $data['total_students'] }}</td>
                <td>Enseignants affectés</td><td class="n">{{ $data['total_teachers'] }}</td></tr>
            <tr><td>Ratio élèves / enseignant (REM)</td><td class="n">{{ $data['rem'] ?? '—' }}</td>
                <td>Taille moyenne des classes</td><td class="n">{{ $data['avg_class_size'] }}</td></tr>
            <tr><td>Nombre de classes</td><td class="n">{{ $data['class_count'] }}</td>
                <td>Classes pléthoriques (> {{ $data['threshold'] }})</td><td class="n">{{ $data['overcrowded']->count() }}</td></tr>
        </table>

        <h2>Taille des classes</h2>
        <table>
            <tr><th>Classe</th><th class="n">Effectif</th></tr>
            @foreach ($data['class_sizes'] as $c)
                <tr><td>{{ $c['name'] }}</td><td class="n">{{ $c['total'] }}</td></tr>
            @endforeach
        </table>
    @elseif ($section === 'assiduite')
        <h2>Assiduité</h2>
        <table class="kpis">
            <tr><td>Taux de présence</td><td class="n">{{ $data['presence_rate'] }} %</td>
                <td>Taux d'absence</td><td class="n">{{ $data['absence_rate'] }} %</td></tr>
            <tr><td>Taux de retard</td><td class="n">{{ $data['late_rate'] }} %</td>
                <td>Absentéisme chronique (> {{ $data['chronic_threshold'] }})</td><td class="n">{{ $data['chronic_absentees'] }}</td></tr>
            <tr><td>Présents / Absents / Retards / Excusés</td>
                <td class="n" colspan="3">{{ $data['present'] }} / {{ $data['absent'] }} / {{ $data['late'] }} / {{ $data['excused'] }}</td></tr>
        </table>

        @if (count($data['by_period']))
            <h2>Par période</h2>
            <table>
                <tr><th>Période</th><th class="n">Présents</th><th class="n">Absents</th><th class="n">Retards</th></tr>
                @foreach ($data['by_period'] as $p)
                    <tr><td>{{ $p['name'] }}</td><td class="n">{{ $p['present'] }}</td><td class="n">{{ $p['absent'] }}</td><td class="n">{{ $p['late'] }}</td></tr>
                @endforeach
            </table>
        @endif
    @elseif ($section === 'comparaisons')
        <h2>Comparaison pluriannuelle</h2>
        <table>
            <tr><th>Année</th><th class="n">Effectif</th><th class="n">% filles</th><th class="n">Redoubl.</th><th class="n">Abandon</th><th class="n">Recouvr.</th><th class="n">Réussite</th><th class="n">Admission</th></tr>
            @foreach ($data['series'] as $r)
                <tr><td>{{ $r['year'] }}</td><td class="n">{{ $r['effectif'] }}</td><td class="n">{{ $r['part_filles'] }}%</td>
                    <td class="n">{{ $r['redoublement'] }}%</td><td class="n">{{ $r['abandon'] }}%</td><td class="n">{{ $r['recouvrement'] }}%</td>
                    <td class="n">{{ $r['reussite'] }}%</td><td class="n">{{ $r['admission'] }}%</td></tr>
            @endforeach
        </table>
    @else
        <h2>Origine géographique (Togo)</h2>
        <p class="sub">Couverture : {{ $data['coverage'] }} % des élèves localisés ({{ $data['localized'] }} / {{ $data['total'] }})</p>
        <table>
            <tr><th>Région</th><th class="n">Élèves</th></tr>
            @foreach ($data['by_region'] as $r)
                <tr><td>{{ $r['name'] }}</td><td class="n">{{ $r['total'] }}</td></tr>
            @endforeach
        </table>
        @if (count($data['by_prefecture']))
            <h2>Par préfecture</h2>
            <table>
                <tr><th>Préfecture</th><th>Région</th><th class="n">Élèves</th></tr>
                @foreach ($data['by_prefecture'] as $p)
                    <tr><td>{{ $p['name'] }}</td><td>{{ $p['region'] }}</td><td class="n">{{ $p['total'] }}</td></tr>
                @endforeach
            </table>
        @endif
    @endif
</body>
</html>
