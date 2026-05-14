<?php declare(strict_types=1); ?>
<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    if (window.__qslScriptsInitialized) {
        return;
    }
    window.__qslScriptsInitialized = true;

<?php include __DIR__ . '/qsl_script_nav.js.php'; ?>
<?php include __DIR__ . '/qsl_script_assistant.js.php'; ?>
<?php include __DIR__ . '/qsl_script_draw_assistant.js.php'; ?>
<?php include __DIR__ . '/qsl_script_qso_toggle.js.php'; ?>
<?php include __DIR__ . '/qsl_script_manual_preview.js.php'; ?>
<?php include __DIR__ . '/qsl_script_card_preview.js.php'; ?>
<?php include __DIR__ . '/qsl_script_dropzone.js.php'; ?>
})();
</script>
