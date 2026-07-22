<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            margin: 10mm 12mm 14mm 12mm;
        }

        body {
            font-family: Calibri, Arial, sans-serif;
        }
    </style>
</head>

<body class="text-[10pt] text-black leading-snug">

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- HEADER (Logo + Title + Reference) — same as ENR-SMQ-07 --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <table class="w-full border-collapse border border-black mb-3">
        <tbody>
            <tr>
                <td rowspan="3" class="border border-black p-2 text-center align-middle w-40">
                    <img src="{{ public_path('imgs/inter-icon.webp') }}" class="mx-auto w-32 block">
                </td>
                <td rowspan="2" class="border border-black px-4 py-2 text-center align-middle">
                    <span class="text-[11pt] font-bold">SYSTEME DE MANAGEMENT DE LA QUALITE</span>
                </td>
                <td class="border border-black px-3 py-1.5 text-center align-middle w-28">
                    <span class="text-[9pt]">EN R-SMQ-07</span>
                </td>
            </tr>
            <tr>
                <td class="border border-black px-3 py-1.5 text-center align-middle">
                    <span class="text-[9pt]">Version : 1.0</span>
                </td>
            </tr>
            <tr>
                <td class="border border-black px-4 py-2 text-center align-middle">
                    <span class="text-[13pt] font-bold">FICHE D'AMELIORATION</span>
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
    {{-- Date / Fiche N° --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <table class="w-full border-collapse mb-3">
        <tr>
            <td class="p-0 font-bold">
                Fiche N° : {{ $improvementSheet->code }}
            </td>

            <td class="p-0 font-bold">
                Date :
                {{ $improvementSheet->created_at ? \Carbon\Carbon::parse($improvementSheet->created_at)->format('d/m/Y') : '' }}
            </td>

        </tr>
    </table>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECTION : Initiation --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    @php
$natureOptions = ['AC' => 'Action corrective', 'AM' => 'Amélioration'];
$isRisqueOpp = !in_array($improvementSheet->finding_source, $natureOptions);
    @endphp

    <table class="w-full border-collapse border border-black mb-2">
        <tr>
            <td class="p-1.5 text-center" style="background-color:#365F91;">
                <span class="text-white font-bold text-[14pt]">Initiation</span>
            </td>
        </tr>
    </table>

    <table class="w-full border-collapse border border-black mb-2">

        <tr>
            <td class="border border-black p-2">
                <p class="mb-1">
                    <strong>Emetteur :</strong> {{ $improvementSheet->responsable->full_name ?? '' }}
                </p>
            </td>
            <td class="border border-black p-2">

                <p class="mb-1">
                    <strong>Processus :</strong> {{ $improvementSheet->service->name ?? '' }}
                </p>
            </td>
        </tr>
    </table>

    <table class="w-full border-collapse border border-black mb-2">

        <tr>
            <td class="border border-black p-2">
                <p class="mb-1">
                    <strong>Nature de l'action :</strong>
                    &nbsp;&nbsp;
                    {{ $improvementSheet->finding_source === 'Action corrective' ? '☑' : '☐' }} AC
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    {{ $isRisqueOpp ? '☑' : '☐' }} Actions face aux risques et opportunités
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    {{ $improvementSheet->finding_source === 'Amélioration' ? '☑' : '☐' }} AM
                </p>
            </td>
        </tr>
    </table>

    <table class="w-full border-collapse border border-black mb-2">
        <tr>
            <td class="border border-black p-2">
                <p class="mb-1"><strong>Description de la non-conformité ou suggestion d'amélioration :</strong></p>
                <p class="mb-3">{{ $improvementSheet->description }}</p>
               
            </td>
        </tr>
    </table>

    <div class="flex justify-between mb-4">
        <div class="text-start w-full">
        <p class="mb-3"><strong>Responsable désigné :</strong> {{ $improvementSheet->responsable->full_name ?? '' }}</p>
        <p class="mb-3">
            <strong>Délai de fin prévu :</strong>
            {{ optional($improvementSheet->improvementActions->first())->due_date ? \Carbon\Carbon::parse($improvementSheet->improvementActions->first()->due_date)->format('d/m/Y') : '' }}
        </p>
        </div>
       <div class="text-start w-full">
         <p class="mb-3"><strong>Visa émetteur :</strong> </p>
         <p class="mb-3"> <strong>Visa Pilote : :</strong> </p>
       </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECTION : Elaboration du plan d'action / Réalisation --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <table class="w-full border-collapse border border-black mb-2">
        <tr>
            <td class="p-1.5 text-center" style="background-color:#365F91;">
                <span class="text-white font-bold text-[14pt]">Elaboration du plan d'action / Réalisation</span>
            </td>
        </tr>
    </table>

<table class="w-full border-collapse border border-black mb-2">
    <tr>
        <td class="border border-black p-2 text-left align-top">
            <strong>Causes identifiées :</strong>

            <pre class="mt-1 whitespace-pre-wrap break-words text-left font-sans m-0">
{{ $improvementSheet->cause_analysis }}
            </pre>
        </td>
    </tr>
</table>

    <table class="w-full border-collapse border border-black mb-3">
        <tr>
            <td class="border border-black p-1 font-bold text-center text-white"
                style="background-color:#365F91; width:26%;">Actions</td>
            <td class="border border-black p-1 font-bold text-center text-white"
                style="background-color:#365F91; width:18%;">Responsable</td>
            <td class="border border-black p-1 font-bold text-center text-white"
                style="background-color:#365F91; width:18%;">Critère d'efficacité</td>
            <td class="border border-black p-1 font-bold text-center text-white"
                style="background-color:#365F91; width:19%;">Date d'échéance</td>
            <td class="border border-black p-1 font-bold text-center text-white"
                style="background-color:#365F91; width:19%;">Date de réalisation</td>
        </tr>
        @forelse($improvementSheet->improvementActions ?? [] as $action)
            <tr>
                <td class="border border-black p-1">{{ $action->description }}</td>
                <td class="border border-black p-1 text-center">{{ $action->responsable->full_name ?? '' }}</td>
                <td class="border border-black p-1">{{ $action->effectiveness_criteria }}</td>
                <td class="border border-black p-1 text-center">
                    {{ $action->due_date ? \Carbon\Carbon::parse($action->due_date)->format('d/m/Y') : '' }}
                </td>
                <td class="border border-black p-1 text-center">
                    {{ $action->completion_date ? \Carbon\Carbon::parse($action->completion_date)->format('d/m/Y') : '' }}
                </td>
            </tr>
        @empty
            <tr>
                <td class="border border-black p-3" colspan="5">&nbsp;</td>
            </tr>
        @endforelse
    </table>


    <p class="mt-2 pb-3 mb-0"><strong>Visa approbateur :</strong></p>

    {{-- ═══════════════════════════════════════════════════════ --}}
    {{-- SECTION : Evaluation de l'efficacité --}}
    {{-- ═══════════════════════════════════════════════════════ --}}
    <table class="w-full border-collapse border border-black mb-2">
        <tr>
            <td class="p-1.5 text-center" style="background-color:#365F91;">
                <span class="text-white font-bold text-[14pt]">Evaluation de l'efficacité</span>
            </td>
        </tr>
    </table>

    <table class="w-full border-collapse border border-black mb-2">
        <tr>
            <td class="border border-black p-2">
                <p class="mb-1">
                    <strong>Date d'évaluation :</strong>
                    {{ $improvementSheet->observation_date ? \Carbon\Carbon::parse($improvementSheet->observation_date)->format('d/m/Y') : '' }}
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <strong>Efficacité :</strong>
                    &nbsp;&nbsp;
                    {{ $improvementSheet->effectiveness === true ? '☑' : '☐' }} Oui
                    &nbsp;&nbsp;
                    {{ $improvementSheet->effectiveness === false ? '☑' : '☐' }} Non
                </p>
                <p class="mb-1">
                    <strong>Preuve d'efficacité :</strong>
                    &nbsp;&nbsp;
                    ☐ Document : ……………………………
                    &nbsp;&nbsp;
                    ☐ Indicateur : ……………………………
                    &nbsp;&nbsp;
                    ☐ Audit : ……………………………
                </p>
                <p class="mb-1">
                    <strong>Action clôturée :</strong>
                    &nbsp;&nbsp;
                    {{ $improvementSheet->closed === true ? '☑' : '☐' }} Oui
                    &nbsp;&nbsp;
                    {{ $improvementSheet->closed === false ? '☑' : '☐' }} Non
                    &nbsp;&nbsp;&nbsp;&nbsp;
                    <strong>Action complémentaire N° :</strong>
                </p>
                <p class="mb-1"><strong>Observations :</strong> {{ $improvementSheet->observation_description }}</p>
                <p class="mb-5"><strong>Visa Responsable management qualité :</strong></p>
            </td>
        </tr>
    </table>


</body>

</html>