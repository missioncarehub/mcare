<?php
    $faviconVersion = @filemtime(public_path('favicon.png')) ?: time();
    $favicon16 = asset('favicon-16.png').'?v='.$faviconVersion;
    $favicon32 = asset('favicon-32.png').'?v='.$faviconVersion;
    $faviconPng = asset('favicon.png').'?v='.$faviconVersion;
    $faviconLarge = asset('assets/images/favicon-180.png').'?v='.$faviconVersion;
    $faviconIco = asset('favicon.ico').'?v='.$faviconVersion;
?>
<link rel="icon" href="<?php echo e($favicon16); ?>" type="image/png" sizes="16x16">
<link rel="icon" href="<?php echo e($favicon32); ?>" type="image/png" sizes="32x32">
<link rel="icon" href="<?php echo e($faviconPng); ?>" type="image/png" sizes="48x48">
<link rel="icon" href="<?php echo e($faviconIco); ?>" sizes="any">
<link rel="apple-touch-icon" href="<?php echo e($faviconLarge); ?>">
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\site-favicon.blade.php ENDPATH**/ ?>