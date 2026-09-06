<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Effectifs - {{ $classroom->name }}</title>
    <style>
        @page { margin: 22px 26px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1a1a1a; font-size: 12px; margin: 0; }

        .header { text-align: center; margin-bottom: 16px; }
        .terme { font-weight: bold; text-transform: uppercase; font-size: 13px; }
        .devise { font-style: italic; font-size: 10px; color: #555; }
        .school { font-weight: bold; font-size: 14px; text-transform: uppercase; margin-top: 4px; }
        .title { margin-top: 10px; font-size: 16px; font-weight: bold; }
        .subtitle { font-size: 12px; color: #444; margin-top: 2px; }
        .rule { border: none; border-top: 2px solid #1a1a1a; margin: 10px auto 0; width: 40%; }

        .meta { margin: 14px 0; font-size: 11px; color: #444; }
        .meta strong { color: #1a1a1a; }

        table { width: 100%; border-collapse: collapse; }
        thead { background: #1d71b8; color: #fff; }
        thead th { padding: 7px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .03em; }
        thead th.center { text-align: center; }
        tbody td { padding: 6px 8px; font-size: 11px; border-bottom: 1px solid #e2e8f0; }
        tbody td.center { text-align: center; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .badge { font-size: 10px; font-weight: 600; }

        .footer { margin-top: 16px; font-size: 9px; color: #888; display: flex; justify-content: space-between; }
        .sign { margin-top: 36px; text-align: right; font-size: 11px; }
        {{-- En-tête ministérielle unifiée (identique au bulletin). --}}
        {!! $headerCss !!}
    </style>
</head>
<body>
    {!! $headerHtml !!}
    <div class="header">
        <div class="title">LISTE DES ÉLÈVES</div>
        <div class="subtitle">Classe : {{ $classroom->name }} ({{ $classroom->code }}) — Année : {{ $year->year }}</div>
    </div>

    <div class="meta">
        <strong>{{ $students->count() }}</strong> élève(s)
        &nbsp;·&nbsp; Garçons : <strong>{{ $students->filter(fn($e) => $e->student?->gender === 'male')->count() }}</strong>
        &nbsp;·&nbsp; Filles : <strong>{{ $students->filter(fn($e) => $e->student?->gender === 'female')->count() }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width:30px;">#</th>
                <th>Matricule</th>
                <th>Nom et prénom(s)</th>
                <th class="center">Sexe</th>
                <th class="center">Né(e) le</th>
                <th>Statut scolarité</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $i => $e)
            <tr>
                <td class="center" style="color:#94a3b8;">{{ $i + 1 }}</td>
                <td style="font-family:monospace;">{{ $e->student?->matricule ?? '—' }}</td>
                <td><strong>{{ $e->student?->lastname }}</strong> {{ $e->student?->firstname }}</td>
                <td class="center">{{ $e->student?->gender === 'male' ? 'M' : ($e->student?->gender === 'female' ? 'F' : '—') }}</td>
                <td class="center">{{ $e->student?->birth_date ? \Carbon\Carbon::parse($e->student->birth_date)->format('d/m/Y') : '—' }}</td>
                <td class="badge">{{ $statuses[$e->academic_status ?? 'en_cours'] ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">Aucun élève.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="sign">Le Directeur</div>

    <div class="footer">
        <span>{{ $school?->name ?? '' }}</span>
        <span>Généré le {{ now()->format('d/m/Y à H:i') }}</span>
    </div>
</body>
</html>
