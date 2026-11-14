<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: 11in 8.5in; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 11in; height: 8.5in; margin: 0; }
        body { background: #fff; color: #050505; font-family: Georgia, 'Times New Roman', serif; }
        .page { position: relative; width: 11in; height: 8.5in; overflow: visible; background: #fff center / 100% 100% no-repeat; }
        .dynamic-name { position: absolute; top: 91mm; left: 14mm; display: flex; width: 178.5mm; height: 15.5mm; align-items: flex-end; justify-content: center; border-bottom: .35mm solid #cf62ea; background: #fffcff; padding: 0 3mm 2.2mm; font-size: 25pt; font-weight: 700; line-height: 1; text-align: center; text-transform: uppercase; white-space: nowrap; }
        .dynamic-name.is-long { font-size: 20pt; }
        .dynamic-date { position: absolute; top: 137mm; left: 28mm; display: flex; width: 163mm; height: 11.5mm; align-items: center; justify-content: center; background: #fffcff; font-size: 11pt; line-height: 1.2; text-align: center; white-space: nowrap; }
        .document-number { position: absolute; right: 8mm; bottom: 5.5mm; background: rgb(255 252 255 / .92); padding: 1mm 1.5mm; color: #475569; font-family: Arial, Helvetica, sans-serif; font-size: 5.5pt; }
    </style>
</head>
<body>
<?php
    $fullName = trim(collect([$application->first_name, $application->middle_name, $application->last_name, $application->extension_name])->filter()->implode(' '));
    $completionDate = $application->batch?->training_ends_at ?? $document->generated_at ?? now();
    $useArtwork = $useOfficialArtwork ?? true;
    $dateSentence = isset($certificateTemplate)
        ? \App\Models\CertificateTemplate::replacePlaceholders((string) $certificateTemplate->body, $certificateValues ?? [])
        : 'Given this '.$completionDate->format('jS').' of '.$completionDate->format('F, Y').' at '.$organization['name'];
    $enrollment = trim((string) ($application->enrollment_number ?? ''));
    if ($enrollment === '' && $application->id) {
        $enrollment = 'ENR-'.$application->id;
    }
?>
<main class="page" <?php if($useArtwork): ?> style="background-image: url('<?php echo e($cotcTemplateDataUri); ?>')" <?php endif; ?>>
    <div class="dynamic-name <?php echo e(mb_strlen($fullName) > 30 ? 'is-long' : ''); ?>"><?php echo e($fullName); ?></div>
    <div class="dynamic-date"><?php echo e($dateSentence); ?></div>
    <?php if(isset($certificateTemplate) && strcasecmp(trim(\App\Models\CertificateTemplate::replacePlaceholders((string) $certificateTemplate->title, $certificateValues ?? [])), 'Certificate of Training Completion') !== 0): ?>
        <div class="dynamic-date" style="top: 28mm; height: 16mm; font-size: 18pt; font-weight: 700;"><?php echo e(\App\Models\CertificateTemplate::replacePlaceholders((string) $certificateTemplate->title, $certificateValues ?? [])); ?></div>
    <?php endif; ?>
    <?php if(isset($certificateTemplate)): ?>
        <div class="document-number" style="left: 28mm; right: auto;"><?php echo e($certificateTemplate->second_signatory_name); ?> · <?php echo e($certificateTemplate->second_signatory_role); ?></div>
        <div class="document-number" style="right: 78mm;"><?php echo e($certificateTemplate->signatory_name); ?> · <?php echo e($certificateTemplate->signatory_role); ?></div>
    <?php endif; ?>
    <div class="document-number"><?php echo e($enrollment !== '' ? 'Enrollment ID: '.$enrollment.' | ' : ''); ?><?php echo e($document->document_number); ?> | Version <?php echo e($document->version); ?></div>
</main>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\documents\pdf\cotc.blade.php ENDPATH**/ ?>