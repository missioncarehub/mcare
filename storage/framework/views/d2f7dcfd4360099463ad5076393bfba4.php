<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 210mm; min-height: 297mm; margin: 0; }
        body { color: #080808; background: #fff; font-family: Arial, Helvetica, sans-serif; font-size: 7.2pt; }
        .page { position: relative; width: 210mm; min-height: 297mm; padding: 7mm 8mm 6mm; }
        .header { display: flex; align-items: center; justify-content: center; gap: 5mm; min-height: 27mm; text-align: center; }
        .logo { width: 20mm; height: 20mm; object-fit: contain; }
        .organization h1 { margin: 0; font-size: 20pt; font-weight: 500; line-height: 1; }
        .organization p { margin: 1.5mm 0 0; font-size: 7pt; line-height: 1.25; }
        .organization strong { display: block; margin-top: 1mm; font-family: Georgia, 'Times New Roman', serif; font-size: 13pt; }
        .purple-rule { height: 1.2mm; margin: 1.5mm 4mm 4mm; background: #9c7ac3; }
        .title { text-align: center; }
        .title h2 { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: 13.5pt; }
        .student-name { margin: 4mm 0 1mm; font-family: Georgia, 'Times New Roman', serif; font-size: 16pt; font-weight: 700; }
        .course { margin: 0; font-family: Georgia, 'Times New Roman', serif; font-size: 12.5pt; font-weight: 700; line-height: 1.15; }
        .course em { display: block; font-size: 10pt; }
        .record-grid { display: grid; grid-template-columns: 52mm 1fr; margin-top: 1.5mm; border-top: .35mm solid #111; border-left: .35mm solid #111; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border-right: .35mm solid #111; border-bottom: .35mm solid #111; padding: 1.2mm 1mm; vertical-align: top; }
        th { font-size: 7.2pt; text-align: center; }
        .student-data { background: #c9c9f8; }
        .student-data td { border-right: .35mm solid #111; font-size: 7pt; }
        .student-data .data-block { height: 17mm; }
        .student-data .school-block { height: 16mm; }
        .student-data .course-block { height: 19mm; }
        .student-data .marks-block { height: 65mm; line-height: 1.52; }
        .student-data .remarks-block { height: 18mm; }
        .field-label { display: block; margin-bottom: 1mm; font-weight: 700; }
        .field-value { display: block; font-weight: 500; line-height: 1.3; }
        .official-marks { columns: 2; column-gap: 4mm; white-space: nowrap; }
        .grades { background: #facbfa; }
        .grades th { height: 10mm; background: #facbfa; font-size: 8pt; vertical-align: middle; }
        .grades td { height: 8.25mm; vertical-align: middle; font-size: 7.5pt; }
        .grades .code { width: 22mm; }
        .grades .grade { width: 22mm; text-align: center; font-weight: 700; }
        .grades .remarks { width: 28mm; text-align: center; font-weight: 700; }
        .grades .nothing { height: 23mm; text-align: center; vertical-align: top; font-size: 8pt; letter-spacing: .8pt; }
        .approval-grid { display: grid; grid-template-columns: 52mm 1fr 1fr; border-left: .35mm solid #111; }
        .approval { position: relative; height: 32mm; border-right: .35mm solid #111; border-bottom: .35mm solid #111; padding: 2mm; background: #facbfa; text-align: center; }
        .approval:first-child { background: #c9c9f8; }
        .approval .label { font-size: 6.5pt; }
        .approval .name { position: absolute; right: 2mm; bottom: 5mm; left: 2mm; font-size: 7.2pt; font-weight: 800; text-transform: uppercase; }
        .approval .role { position: absolute; right: 2mm; bottom: 1.5mm; left: 2mm; font-size: 6.3pt; }
        .document-number { margin-top: 2mm; color: #64748b; font-size: 5.5pt; text-align: right; }
    </style>
</head>
<body>
<?php
    $fullName = trim(collect([$application->first_name, $application->middle_name, $application->last_name, $application->extension_name])->filter()->implode(' '));
    $address = collect([$application->street, $application->barangay, $application->city, $application->province, $application->zip_code])->filter()->implode(', ');
    $completionDate = $application->batch?->training_ends_at;
    $records = $application->competencyRecords->filter(fn($record) => $record->unit?->is_tor_included)->sortBy(fn($record) => $record->unit?->sort_order);
    $certificateTemplate = $certificateTemplate ?? null;
    $certificateValues = $certificateValues ?? [];
?>
<main class="page">
    <header class="header">
        <img class="logo" src="<?php echo e($logoDataUri); ?>" alt="Mission Care">
        <div class="organization"><h1><?php echo e($organization['name']); ?></h1><p><?php echo e($organization['address']); ?><br><?php echo e($organization['phone']); ?></p><strong>REGISTRAR</strong></div>
    </header>
    <div class="purple-rule"></div>
    <section class="title">
        <h2><?php echo e(\App\Models\CertificateTemplate::headingFor(\App\Models\OfficialDocument::TYPE_TOR, $document)); ?></h2>
        <p class="student-name">"<?php echo e(strtoupper($fullName)); ?>"</p>
        <p class="course">CAREGIVING NC II<em>Course<br>(<?php echo e($organization['course_hours']); ?> Hours)</em></p>
    </section>

    <section class="record-grid">
        <table class="student-data">
            <tbody>
                <tr><td class="data-block"><span class="field-label">Entrance Date:</span><span class="field-value"><?php echo e($application->batch?->training_starts_at?->format('F j, Y') ?? '-'); ?></span></td></tr>
                <tr><td class="data-block"><span class="field-label">Address:</span><span class="field-value"><?php echo e($address ?: '-'); ?></span></td></tr>
                <tr><td class="school-block"><span class="field-label">Last School Attended:</span><span class="field-value"><?php echo e($application->school_name ?: '-'); ?></span><span class="field-label" style="margin-top: 3mm">Category:</span><span class="field-value"><?php echo e($application->classification ?: '-'); ?></span></td></tr>
                <tr><td class="course-block"><span class="field-label">TITLE/COURSE:</span><span class="field-value">CAREGIVING NC II</span><span class="field-label" style="margin-top: 2mm; font-style: italic">Date Completed/Graduated:</span><span class="field-value"><?php echo e($completionDate?->format('F j, Y') ?? '-'); ?></span></td></tr>
                <tr><td class="marks-block"><span class="field-label">Official Marks:</span><div class="official-marks">1.00 - 99%<br>1.10 - 98%<br>1.20 - 97%<br>1.25 - 96%<br>1.30 - 95%<br>1.40 - 94%<br>1.50 - 93%<br>1.60 - 92%<br>1.70 - 91%<br>1.75 - 90%<br>1.80 - 89%<br>1.90 - 88%<br>2.00 - 87%<br>2.10 - 86%<br>2.20 - 85%<br>2.25 - 84%<br>2.30 - 83%<br>2.40 - 82%<br>2.50 - 81%<br>2.60 - 80%<br>2.70 - 79%<br>2.75 - 78%<br>2.80 - 77%<br>2.90 - 76%<br>3.00 - 75%</div><span class="field-value" style="margin-top: 2mm">1.00-3.00 Competent<br>4.00-5.00 Not Yet Competent</span></td></tr>
                <tr><td class="remarks-block"><span class="field-label">Remarks:</span><span class="field-value" style="margin-top: 5mm; font-style: italic; font-weight: 700">For Records and Reference Purposes</span><?php if(filled($certificateTemplate?->body)): ?><span class="field-value"><?php echo e(\App\Models\CertificateTemplate::replacePlaceholders($certificateTemplate->body, $certificateValues ?? [])); ?></span><?php endif; ?></td></tr>
            </tbody>
        </table>

        <table class="grades">
            <thead><tr><th class="code">Course Code</th><th>Course Title</th><th class="grade">Final Grade</th><th class="remarks">Remarks</th></tr></thead>
            <tbody>
                <?php $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr><td class="code"><?php echo e($record->unit->code); ?></td><td><?php echo e($record->unit->title); ?></td><td class="grade"><?php echo e($record->tor_grade ? number_format((float)$record->tor_grade, 2) : '-'); ?></td><td class="remarks"><?php echo e($record->status === 'competent' ? 'COMPETENT' : 'NOT YET COMPETENT'); ?></td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <tr><td class="nothing" colspan="4">* * * * * NOTHING FOLLOWS * * * * *</td></tr>
            </tbody>
        </table>
    </section>

    <section class="approval-grid">
        <div class="approval"><span class="label">Prepared by:</span><span class="name"><?php echo e($certificateTemplate?->second_signatory_name ?: $organization['trainer_name']); ?></span><span class="role"><?php echo e($certificateTemplate?->second_signatory_role ?: 'Lead Trainer'); ?></span></div>
        <div class="approval"><span class="label">Certified by:</span><span class="name"><?php echo e($certificateTemplate?->signatory_name ?: $organization['registrar_name']); ?></span><span class="role"><?php echo e($certificateTemplate?->signatory_role ?: 'Registrar'); ?></span></div>
        <div class="approval"><span class="label">Seal</span><span class="role">Not valid without official seal</span></div>
    </section>
    <div class="document-number">Document no. <?php echo e($document->document_number); ?> | Version <?php echo e($document->version); ?></div>
</main>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\documents\pdf\tor.blade.php ENDPATH**/ ?>