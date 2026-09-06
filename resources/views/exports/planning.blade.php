<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Planning des examens — {{ $classroom->name }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 13px; color: #1e293b; background: #fff; padding: 32px; }

    .header { text-align: center; border-bottom: 3px solid #1e293b; padding-bottom: 16px; margin-bottom: 24px; }
    .school-name { font-size: 18px; font-weight: 800; color: #1e293b; letter-spacing: .02em; }
    .doc-title { font-size: 15px; font-weight: 700; color: #3b82f6; margin-top: 6px; }
    .meta { font-size: 12px; color: #64748b; margin-top: 4px; }

    .info-bar { display: flex; gap: 24px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; }
    .info-item { display: flex; flex-direction: column; }
    .info-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; }
    .info-value { font-size: 13px; font-weight: 700; color: #1e293b; margin-top: 2px; }

    table { width: 100%; border-collapse: collapse; margin-top: 0; }
    thead { background: #1e293b; color: #fff; }
    thead th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    thead th.center { text-align: center; }
    tbody tr { border-bottom: 1px solid #e2e8f0; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 9px 12px; font-size: 12.5px; vertical-align: middle; }
    tbody td.center { text-align: center; }
    tbody td.date { font-weight: 700; color: #1d4ed8; }
    tbody td.no-date { color: #ef4444; font-style: italic; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
    .badge-done { background: #d1fae5; color: #065f46; }
    .badge-scheduled { background: #fef3c7; color: #92400e; }

    .footer { margin-top: 28px; font-size: 11px; color: #94a3b8; display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 12px; }

    .print-btn { position: fixed; top: 16px; right: 16px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 600; cursor: pointer; z-index: 999; }
    .print-btn:hover { background: #2563eb; }

    @media print {
        .print-btn { display: none; }
        body { padding: 16px; }
        @page { margin: 1cm; size: A4 landscape; }
    }
    /* En-tête ministérielle unifiée (identique au bulletin). */
    {!! $headerCss !!}
</style>
</head>
<body>

<button class="print-btn" onclick="window.print()">🖨 Imprimer / PDF</button>

{!! $headerHtml !!}
<div class="header" style="border-bottom:none; margin-bottom:20px; padding-bottom:0;">
    <div class="doc-title">Planning des examens</div>
    <div class="meta">
        Classe : <strong>{{ $classroom->name }} ({{ $classroom->code }})</strong>
        @if($activeYear) &nbsp;·&nbsp; Année : <strong>{{ $activeYear->year }}</strong>@endif
        &nbsp;·&nbsp; Imprimé le : <strong>{{ now()->format('d/m/Y') }}</strong>
    </div>
</div>

@php
    $totalCount     = $evaluations->count();
    $withDate       = $evaluations->whereNotNull('date')->count();
    $completedCount = $evaluations->where('status', 'completed')->count();
    $periods        = $evaluations->pluck('template.academic_period.name')->unique()->filter()->values();
@endphp

<div class="info-bar">
    <div class="info-item">
        <span class="info-label">Évaluations</span>
        <span class="info-value">{{ $totalCount }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Planifiées (avec date)</span>
        <span class="info-value">{{ $withDate }}</span>
    </div>
    <div class="info-item">
        <span class="info-label">Terminées</span>
        <span class="info-value">{{ $completedCount }}</span>
    </div>
    @if($periods->count() > 0)
    <div class="info-item">
        <span class="info-label">Période(s)</span>
        <span class="info-value">{{ $periods->implode(', ') }}</span>
    </div>
    @endif
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Matière</th>
            <th>Évaluation</th>
            <th>Type</th>
            <th>Période</th>
            <th class="center">Coeff.</th>
            <th class="center">Barème</th>
            <th class="center">Date planifiée</th>
            <th class="center">Heure</th>
            <th class="center">Statut</th>
        </tr>
    </thead>
    <tbody>
        @forelse($evaluations as $i => $ev)
        <tr>
            <td class="center" style="color:#94a3b8;">{{ $i + 1 }}</td>
            <td><strong>{{ $ev->classSubject->subject->name }}</strong></td>
            <td>{{ $ev->template->name }}</td>
            <td style="color:#64748b;">{{ $ev->template->evaluationType->name }}</td>
            <td style="color:#64748b;">{{ $ev->template->academicPeriod->name }}</td>
            <td class="center" style="font-weight:700; color:#7c3aed;">{{ number_format($ev->template->coefficient, 2) }}</td>
            <td class="center" style="color:#475569;">{{ number_format($ev->template->max_score, 0) }}</td>
            <td class="center {{ $ev->date ? 'date' : 'no-date' }}">
                {{ $ev->date ? \Carbon\Carbon::parse($ev->date)->format('d/m/Y') : 'Non planifiée' }}
            </td>
            <td class="center" style="color:#475569;">
                {{ $ev->start_time ? \Carbon\Carbon::parse($ev->start_time)->format('H\hi') : '—' }}
            </td>
            <td class="center">
                <span class="badge {{ $ev->status === 'completed' ? 'badge-done' : 'badge-scheduled' }}">
                    {{ $ev->status === 'completed' ? 'Terminée' : 'Planifiée' }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="10" style="text-align:center; padding:24px; color:#94a3b8;">Aucune évaluation.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    <span>{{ $school?->name ?? '' }}</span>
    <span>Document généré le {{ now()->format('d/m/Y à H:i') }}</span>
</div>

</body>
</html>
