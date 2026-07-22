<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page { margin: 10mm 12mm 14mm 12mm; }
        body { font-family: Calibri, Arial, sans-serif; }
    </style>
</head>
<body class="text-[10pt] text-black leading-snug">

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- HEADER (Logo + Title + Reference) — same as Rec001.docx --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<table class="w-full border-collapse border border-black mb-4">
  <tbody>
    <tr>
      <td rowspan="3" class="border border-black p-2 text-center align-middle w-40">
        <img src="{{ public_path('imgs/inter-icon.webp') }}" class="mx-auto w-32 block">
      </td>
      <td rowspan="2" class="border border-black px-4 py-2 text-center align-middle">
        <span class="text-[11pt] font-bold">SYSTEME DE MANAGEMENT DE LA QUALITE</span>
      </td>
      <td class="border border-black px-3 py-1.5 text-center align-middle w-28">
        <span class="text-[9pt]">EN R-SMQ-05</span>
      </td>
    </tr>
    <tr>
      <td class="border border-black px-3 py-1.5 text-center align-middle">
        <span class="text-[9pt]">Version : 1.0</span>
      </td>
    </tr>
    <tr>
      <td class="border border-black px-4 py-2 text-center align-middle">
        <span class="text-[13pt] font-bold">FICHE DE RECLAMATION / SUGGESTION</span>
      </td>
      <td class="border border-black px-3 py-1.5 text-center align-middle">
        <span class="text-[9pt]">
          Page <span class="pageNumber">1</span> | <span class="totalPages">1</span>
        </span>
      </td>
    </tr>
  </tbody>
</table>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- I. PARTIE A REMPLIR PAR LE RECLAMANT                    --}}
{{-- ═══════════════════════════════════════════════════════ --}}
@php
    $objetGauche = [
        'Dimensions non conformes au commande (largueur ; longueur, épaisseur)',
        'Perçage du produit non conforme',
        'Commande incomplet',
        'Mauvaise qualité des matériaux',
        'Finition défectueuse (rayures, peinture,)',
        'Problème de montage / assemblage',
        'Erreur de modèle ou de référence',
        'Non-respect des spécifications techniques',
    ];
    $objetDroite = [
        'Retard de livraison',
        'Produit endommagé à la réception',
        "Non-conformité esthétique (couleur, ...)",
        "Problème d'emballage",
        'Etiquette de produit',
        'Accessoires manquants',
        'Service client insatisfaisant',
    ];
    // The recorded object doesn't map to one of the predefined checkboxes,
    // so it is reported under "Autre".
    $objetPredefini = collect(array_merge($objetGauche, $objetDroite))
        ->first(fn($label) => str_contains(mb_strtolower($label), mb_strtolower($reclamation->object ?? '__none__')));
@endphp

<p class="italic font-bold text-[11pt] mb-2">I PARTIE A REMPLIR PAR LE RECLAMANT</p>

<p class="font-bold mb-1">
    Date de la réclamation ou de la suggestion :
    {{ $reclamation->claimant_date ? \Carbon\Carbon::parse($reclamation->claimant_date)->format('d/m/Y') : '' }}
</p>

<table class="w-full border-collapse border border-black mb-3">
    <tr>
        <td class="border border-black p-2">
            <p class="mb-1"><strong>Nom et prénom :</strong> {{ $reclamation->claimant_name }}</p>
            <p class="mb-1"><strong>Référence Client :</strong> {{ $reclamation->client_code }}</p>
            <p class="mb-1"><strong>Mode de réception de la réclamation/ Suggestion :</strong></p>
            <p class="mb-0.5">{{ $reclamation->reception_method === 'Téléphone' ? '☑' : '☐' }} Par téléphone</p>
            <p class="mb-0.5">{{ $reclamation->reception_method === 'Email' ? '☑' : '☐' }} Par email</p>
            <p class="mb-0.5">{{ $reclamation->reception_method === 'En personne' ? '☑' : '☐' }} En personne</p>
            <p class="mb-0.5">
                {{ !in_array($reclamation->reception_method, ['Téléphone', 'Email', 'En personne']) ? '☑' : '☐' }}
                Autre : {{ !in_array($reclamation->reception_method, ['Téléphone', 'Email', 'En personne']) ? $reclamation->reception_method : '..................' }}
            </p>
        </td>
    </tr>
</table>

<table class="w-full border-collapse border border-black mb-3">
    <tr>
        <td colspan="2" class="border border-black p-1.5 font-bold">
            DESCRIPTION DE LA RECLAMATION OU DE LA SUGGESTION
        </td>
    </tr>
    <tr>
        <td class="border border-black p-2 align-top w-1/2">
            <p class="font-bold mb-1">Objet de la réclamation :</p>
            @foreach($objetGauche as $item)
                <p class="mb-0.5">{{ $objetPredefini === $item ? '☑' : '☐' }} {{ $item }}</p>
            @endforeach
        </td>
        <td class="border border-black p-2 align-top w-1/2">
            <p class="mb-1">&nbsp;</p>
            @foreach($objetDroite as $item)
                <p class="mb-0.5">{{ $objetPredefini === $item ? '☑' : '☐' }} {{ $item }}</p>
            @endforeach
            <p class="mb-0.5">
                {{ !$objetPredefini ? '☑' : '☐' }} Autre :
                {{ !$objetPredefini ? $reclamation->object : '..................' }}
            </p>
        </td>
    </tr>
    <tr>
        <td class="border border-black p-2 font-bold align-top">Décrire la réclamation /suggestion :</td>
        <td class="border border-black p-2 align-top">{{ $reclamation->description }}</td>
    </tr>
</table>

<p class="font-bold mb-1">Remplis par : {{ $reclamation->user->full_name ?? '' }}</p>
<p class="font-bold mb-1">
    Recevable :
    {{ $reclamation->is_recevable === true ? 'oui ☑' : 'oui ☐' }}
    &nbsp;&nbsp;
    {{ $reclamation->is_recevable === false ? 'non ☑' : 'non ☐' }}
</p>
<p class="font-bold mb-1">
    Remis le :
    {{ $reclamation->registration_date ? \Carbon\Carbon::parse($reclamation->registration_date)->format('d/m/Y') : '...........................................' }}
</p>
<p class="mb-4"><strong>Visa du Directeur :</strong></p>

{{-- ═══════════════════════════════════════════════════════ --}}
{{-- II. PARTIE A REMPLIR PAR LE RESPONSABLE DU TRAITEMENT   --}}
{{-- ═══════════════════════════════════════════════════════ --}}
<p class="italic font-bold underline mb-2">II PARTIE A REMPLIR PAR LE RESPONSABLE DU TRAITEMENT</p>

<table class="w-full border-collapse border border-black mb-3">
    <tr>
        <td class="border border-black p-2">
            <p class="mb-1">
                <strong>Date d'enregistrement :</strong>
                {{ $reclamation->registration_date ? \Carbon\Carbon::parse($reclamation->registration_date)->format('d/m/Y') : '......./......./..............' }}
            </p>
            <p class="mb-1">
                <strong>N° d'enregistrement :</strong> {{ $reclamation->code }}
                &nbsp;&nbsp;-&nbsp;&nbsp;<strong>Emetteur :</strong> {{ $reclamation->user->full_name ?? '' }}
            </p>
            <p class="mb-0">
                <strong>Transmis au Service Concerné le :</strong>
                {{ $reclamation->received_at ? \Carbon\Carbon::parse($reclamation->received_at)->format('d/m/Y') : '......../......../..............' }}
            </p>
        </td>
    </tr>
</table>

<table class="w-full border-collapse border border-black mb-3">
    <tr>
        <td class="border border-black p-1.5 font-bold">1) Analyse de la réclamation ou de la suggestion</td>
    </tr>
    <tr>
        <td class="border border-black p-2 align-top" style="min-height:80px;">
            <p class="mb-3">{{ $reclamation->post_analysis }}</p>
            <p class="mb-1">
                <strong>Pour les réclamations :</strong>
                {{ $reclamation->is_justifiee === true ? '☑' : '☐' }} Réclamation justifiée
                &nbsp;&nbsp;
                {{ $reclamation->is_justifiee === false ? '☑' : '☐' }} Réclamation non justifiée
            </p>
        </td>
    </tr>
</table>

<table class="w-full border-collapse border border-black mb-3">
    <tr>
        <td class="border border-black p-1.5 font-bold">2) Action décidée</td>
    </tr>
    <tr>
        <td class="border border-black p-2">
            <table class="w-full border-collapse border border-black">
                <tr>
                    <td class="border border-black p-1 font-bold text-center w-[34%]">Actions</td>
                    <td class="border border-black p-1 font-bold text-center w-[22%]">Responsable</td>
                    <td class="border border-black p-1 font-bold text-center w-[22%]">Date de réalisation</td>
                    <td class="border border-black p-1 font-bold text-center w-[22%]">N° de la fiche d'amélioration</td>
                </tr>
                @forelse($reclamation->correctiveActions ?? [] as $action)
                <tr>
                    <td class="border border-black p-1">{{ $action->description }}</td>
                    <td class="border border-black p-1 text-center">{{ $action->responsable->full_name ?? '' }}</td>
                    <td class="border border-black p-1 text-center">
                        {{ $action->completion_date ? \Carbon\Carbon::parse($action->completion_date)->format('d/m/Y') : '' }}
                    </td>
                    <td class="border border-black p-1 text-center">{{ $action?->improvementSheets?->code }}</td>
                </tr>
                @empty
                <tr>
                    <td class="border border-black p-3" colspan="4">&nbsp;</td>
                </tr>
                @endforelse
            </table>
            <p class="mt-2 mb-1">
                <strong>Responsable :</strong>
                {{ optional($reclamation->correctiveActions->first())->responsable->full_name ?? '..................................................................................' }}
            </p>
            <p class="mb-0">
                Date :
                {{ optional($reclamation->correctiveActions->first())->completion_date ? \Carbon\Carbon::parse($reclamation->correctiveActions->first()->completion_date)->format('d/m/Y') : '...../...../...............' }}
                - Signature du Responsable :
            </p>
        </td>
    </tr>
</table>

<table class="w-full border-collapse border border-black mb-3">
    <tr>
        <td class="border border-black p-1.5 font-bold">3) Réponse au client en cas de réclamation justifiée</td>
    </tr>
    <tr>
        <td class="border border-black p-2">
            Type de réponse : {{ $reclamation->processing_analysis ?? '...................................................' }}
            &nbsp;&nbsp;
            Date d'envoi : {{ $reclamation->received_at ? \Carbon\Carbon::parse($reclamation->received_at)->format('d/m/Y') : '......./......./...........................' }}
        </td>
    </tr>
</table>

<table class="w-full border-collapse border border-black mb-3">
    <tr>
        <td class="border border-black p-1.5 font-bold">4) Clôture de la fiche</td>
    </tr>
    <tr>
        <td class="border border-black p-2">
            Date de la clôture:
            {{ $reclamation->closing_date ? \Carbon\Carbon::parse($reclamation->closing_date)->format('d/m/Y') : '......./......./............' }}
        </td>
    </tr>
</table>
</body>
</html>