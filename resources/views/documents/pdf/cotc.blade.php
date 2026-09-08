<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: Letter landscape; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 279.4mm; height: 215.9mm; margin: 0; }
        body { background: #fff; color: #050505; font-family: Georgia, 'Times New Roman', serif; }
        .page { position: relative; width: 279.4mm; height: 215.9mm; overflow: hidden; background: #fff center / 100% 100% no-repeat; }
        .dynamic-name { position: absolute; top: 91mm; left: 14mm; display: flex; width: 178.5mm; height: 15.5mm; align-items: flex-end; justify-content: center; border-bottom: .35mm solid #cf62ea; background: #fffcff; padding: 0 3mm 2.2mm; font-size: 25pt; font-weight: 700; line-height: 1; text-align: center; text-transform: uppercase; white-space: nowrap; }
        .dynamic-name.is-long { font-size: 20pt; }
        .dynamic-date { position: absolute; top: 137mm; left: 28mm; display: flex; width: 163mm; height: 11.5mm; align-items: center; justify-content: center; background: #fffcff; font-size: 11pt; line-height: 1.2; text-align: center; white-space: nowrap; }
        .document-number { position: absolute; right: 8mm; bottom: 5.5mm; background: rgb(255 252 255 / .92); padding: 1mm 1.5mm; color: #475569; font-family: Arial, Helvetica, sans-serif; font-size: 5.5pt; }
    </style>
</head>
<body>
@php
    $fullName = trim(collect([$application->first_name, $application->middle_name, $application->last_name, $application->extension_name])->filter()->implode(' '));
    $completionDate = $application->batch?->training_ends_at ?? $document->generated_at ?? now();
@endphp
<main class="page" style="background-image: url('{{ $cotcTemplateDataUri }}')">
    <div class="dynamic-name {{ mb_strlen($fullName) > 30 ? 'is-long' : '' }}">{{ $fullName }}</div>
    <div class="dynamic-date">Given this {{ $completionDate->format('jS') }} of {{ $completionDate->format('F, Y') }} at {{ $organization['name'] }}</div>
    <div class="document-number">{{ $document->document_number }} | Version {{ $document->version }}</div>
</main>
</body>
</html>
