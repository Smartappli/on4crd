<article class="card tool-panel is-hidden" id="tool-unit-conversions" data-tool-panel>
    <h2><?= e((string) ($t['unit_conv_title'] ?? 'Conversion d’unités')) ?></h2>
    <p class="help"><?= e((string) ($t['unit_conv_help'] ?? 'Sélectionnez une conversion :')) ?></p>
    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.5rem;">
        <?php
        $unitTools = [
            'tool-kw-w','tool-w-kw','tool-hz-khz','tool-khz-mhz','tool-mhz-ghz','tool-in-mm','tool-ft-m',
            'tool-c-f','tool-f-c','tool-pa-db','tool-db-pa','tool-j-wh','tool-wh-j','tool-ms-s','tool-s-ms',
            'tool-rpm-rps','tool-rps-rpm','tool-sunit-dbuv','tool-dbuv-sunit','tool-vpp-vrms','tool-vrms-vpp','tool-vpk-vrms'
        ];
        foreach ($unitTools as $unitToolId):
            $unitLabel = '';
            foreach ($conversionTools as $tool) {
                if ((string) ($tool['id'] ?? '') === $unitToolId) { $unitLabel = (string) ($tool['title'] ?? ''); break; }
            }
            if ($unitLabel === '') { $unitLabel = $unitToolId; }
        ?>
            <a class="button secondary" href="#<?= e($unitToolId) ?>" data-tool-target="<?= e($unitToolId) ?>"><?= e($unitLabel) ?></a>
        <?php endforeach; ?>
    </div>
</article>
