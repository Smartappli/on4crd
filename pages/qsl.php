<?php
declare(strict_types=1);

$user = require_login();
$memberId = (int) ($user['id'] ?? 0);
$locale = current_locale();
$qslI18n = [
    'fr' => ['studio' => 'QSL Studio · simple, guidé, efficace', 'studio_help' => 'Tout est pensé pour aller vite : importez vos QSO, créez vos cartes et exportez-les sans friction.', 'design' => '1) Designer vos fonds QSL', 'create' => '2) Créer des QSL facilement', 'manage' => '3) QSO importés', 'generated' => 'QSL générées', 'filter' => 'Filtrer', 'reset' => 'Réinitialiser', 'page' => 'Page', 'previous' => 'Précédent', 'next' => 'Suivant', 'nav_design' => '1 · Personnaliser le design', 'nav_design_help' => 'Ajoutez un fond image, une couleur unie, un dégradé ou une palette prête à l’emploi.', 'nav_create' => '2 · Créer / importer', 'nav_create_help' => 'Créez une QSL manuelle ou importez vos ADIF en glisser‑déposer.', 'err_select_bg' => 'Veuillez sélectionner une image de fond.', 'ok_bg_image' => 'Fond image enregistré.', 'err_gradient_invalid' => 'Couleurs de dégradé invalides.', 'ok_bg_gradient' => 'Fond dégradé enregistré.', 'err_solid_invalid' => 'Couleur unie invalide.', 'ok_bg_solid' => 'Fond couleur unie enregistré.', 'err_palette_invalid' => 'Palette prédéfinie invalide.', 'ok_bg_palette' => 'Palette prédéfinie enregistrée.', 'ok_bg_default' => 'Fond par défaut mis à jour.', 'ok_bg_deleted' => 'Fond supprimé.', 'err_no_adif' => 'Aucun fichier ADIF reçu.', 'err_no_valid_adif' => 'Aucun fichier ADIF valide n’a pu être traité.', 'ok_qso_imported' => 'QSO importés depuis les fichiers ADIF.', 'err_qso_none' => 'Aucun nouveau QSO importé.', 'ok_qsl_generated' => 'QSL générées.', 'err_qsl_none' => 'Aucune QSL générée. Sélection vide ou QSL déjà existantes.', 'ok_qsl_created' => 'QSL créée.', 'ok_qso_deleted' => 'QSO supprimé.', 'ok_qsl_deleted' => 'QSL supprimée.', 'err_unknown_action' => 'Action QSL inconnue.', 'label_bg_image' => 'Fond image', 'label_gradient' => 'Dégradé 2 couleurs', 'label_delete' => 'Supprimer', 'empty_qso' => 'Aucun QSO importé pour le moment.', 'empty_qso_filtered' => 'Aucun QSO ne correspond aux filtres actifs.', 'empty_qsl' => 'Aucune QSL générée pour le moment.', 'empty_qsl_filtered' => 'Aucune QSL ne correspond à la recherche.', 'nav_manage' => '3 · Gérer et exporter', 'nav_manage_help' => 'Filtrez vos QSO, générez en lot et exportez vos cartes recto/verso.', 'bulk_generate' => 'Générer les QSL sélectionnées', 'select_all' => 'Tout sélectionner', 'select_none' => 'Tout désélectionner', 'qso_search_ph' => 'Filtrer par call, date, mode...', 'qsl_search_ph' => 'Rechercher une QSL (titre, call, bande...)', 'all_bands' => 'Toutes bandes', 'all_modes' => 'Tous modes'],
    'en' => ['studio' => 'QSL Studio · simple, guided, efficient', 'studio_help' => 'Everything is designed for speed: import your QSOs, create cards and export them seamlessly.', 'design' => '1) Design your QSL backgrounds', 'create' => '2) Create QSL cards easily', 'manage' => '3) Imported QSOs', 'generated' => 'Generated QSL cards', 'filter' => 'Filter', 'reset' => 'Reset', 'page' => 'Page', 'previous' => 'Previous', 'next' => 'Next', 'nav_design' => '1 · Customize design', 'nav_design_help' => 'Add an image background, a solid color, a gradient or a ready-to-use palette.', 'nav_create' => '2 · Create / import', 'nav_create_help' => 'Create a manual QSL or import your ADIF files via drag and drop.', 'err_select_bg' => 'Please select a background image.', 'ok_bg_image' => 'Image background saved.', 'err_gradient_invalid' => 'Invalid gradient colors.', 'ok_bg_gradient' => 'Gradient background saved.', 'err_solid_invalid' => 'Invalid solid color.', 'ok_bg_solid' => 'Solid color background saved.', 'err_palette_invalid' => 'Invalid preset palette.', 'ok_bg_palette' => 'Preset palette saved.', 'ok_bg_default' => 'Default background updated.', 'ok_bg_deleted' => 'Background deleted.', 'err_no_adif' => 'No ADIF file received.', 'err_no_valid_adif' => 'No valid ADIF file could be processed.', 'ok_qso_imported' => 'QSOs imported from ADIF files.', 'err_qso_none' => 'No new QSO imported.', 'ok_qsl_generated' => 'QSL cards generated.', 'err_qsl_none' => 'No QSL generated. Empty selection or cards already exist.', 'ok_qsl_created' => 'QSL created.', 'ok_qso_deleted' => 'QSO deleted.', 'ok_qsl_deleted' => 'QSL deleted.', 'err_unknown_action' => 'Unknown QSL action.', 'label_bg_image' => 'Image background', 'label_gradient' => '2-color gradient', 'label_delete' => 'Delete', 'empty_qso' => 'No imported QSO yet.', 'empty_qso_filtered' => 'No QSO matches active filters.', 'empty_qsl' => 'No generated QSL yet.', 'empty_qsl_filtered' => 'No QSL matches your search.', 'nav_manage' => '3 · Manage and export', 'nav_manage_help' => 'Filter your QSOs, generate in batch and export front/back cards.', 'bulk_generate' => 'Generate selected QSL cards', 'select_all' => 'Select all', 'select_none' => 'Select none', 'qso_search_ph' => 'Filter by callsign, date, mode...', 'qsl_search_ph' => 'Search a QSL (title, call, band...)', 'all_bands' => 'All bands', 'all_modes' => 'All modes'],
    'de' => ['studio' => 'QSL Studio · einfach, geführt, effizient', 'studio_help' => 'Alles ist auf Tempo ausgelegt: QSOs importieren, Karten erstellen und ohne Reibung exportieren.', 'design' => '1) QSL-Hintergründe gestalten', 'create' => '2) QSL-Karten einfach erstellen', 'manage' => '3) Importierte QSOs', 'generated' => 'Erstellte QSL-Karten', 'filter' => 'Filtern', 'reset' => 'Zurücksetzen', 'page' => 'Seite', 'previous' => 'Zurück', 'next' => 'Weiter', 'nav_design' => '1 · Design anpassen', 'nav_design_help' => 'Fügen Sie ein Bild, eine Volltonfarbe, einen Verlauf oder eine fertige Palette hinzu.', 'nav_create' => '2 · Erstellen / importieren', 'nav_create_help' => 'Erstellen Sie eine manuelle QSL oder importieren Sie ADIF per Drag & Drop.', 'err_select_bg' => 'Bitte wählen Sie ein Hintergrundbild aus.', 'ok_bg_image' => 'Bildhintergrund gespeichert.', 'err_gradient_invalid' => 'Ungültige Verlauf-Farben.', 'ok_bg_gradient' => 'Verlaufshintergrund gespeichert.', 'err_solid_invalid' => 'Ungültige Volltonfarbe.', 'ok_bg_solid' => 'Einfarbiger Hintergrund gespeichert.', 'err_palette_invalid' => 'Ungültige vordefinierte Palette.', 'ok_bg_palette' => 'Vordefinierte Palette gespeichert.', 'ok_bg_default' => 'Standardhintergrund aktualisiert.', 'ok_bg_deleted' => 'Hintergrund gelöscht.', 'err_no_adif' => 'Keine ADIF-Datei empfangen.', 'err_no_valid_adif' => 'Keine gültige ADIF-Datei konnte verarbeitet werden.', 'ok_qso_imported' => 'QSOs aus ADIF-Dateien importiert.', 'err_qso_none' => 'Kein neuer QSO importiert.', 'ok_qsl_generated' => 'QSL-Karten erstellt.', 'err_qsl_none' => 'Keine QSL erstellt. Leere Auswahl oder bereits vorhandene Karten.', 'ok_qsl_created' => 'QSL erstellt.', 'ok_qso_deleted' => 'QSO gelöscht.', 'ok_qsl_deleted' => 'QSL gelöscht.', 'err_unknown_action' => 'Unbekannte QSL-Aktion.', 'label_bg_image' => 'Bildhintergrund', 'label_gradient' => '2-Farben-Verlauf', 'label_delete' => 'Löschen', 'empty_qso' => 'Noch kein QSO importiert.', 'empty_qso_filtered' => 'Kein QSO entspricht den aktiven Filtern.', 'empty_qsl' => 'Noch keine QSL erstellt.', 'empty_qsl_filtered' => 'Keine QSL entspricht der Suche.', 'nav_manage' => '3 · Verwalten und exportieren', 'nav_manage_help' => 'Filtern Sie Ihre QSOs, erzeugen Sie Stapel und exportieren Sie Vorder-/Rückseiten.', 'bulk_generate' => 'Ausgewählte QSL-Karten erzeugen', 'select_all' => 'Alle auswählen', 'select_none' => 'Auswahl aufheben', 'qso_search_ph' => 'Nach Rufzeichen, Datum, Modus filtern...', 'qsl_search_ph' => 'QSL suchen (Titel, Rufzeichen, Band...)', 'all_bands' => 'Alle Bänder', 'all_modes' => 'Alle Modi'],
    'nl' => ['studio' => 'QSL Studio · eenvoudig, begeleid, efficiënt', 'studio_help' => 'Alles is gericht op snelheid: importeer je QSO’s, maak kaarten en exporteer zonder frictie.', 'design' => '1) Ontwerp je QSL-achtergronden', 'create' => '2) Maak eenvoudig QSL-kaarten', 'manage' => '3) Geïmporteerde QSO’s', 'generated' => 'Gegenereerde QSL-kaarten', 'filter' => 'Filteren', 'reset' => 'Reset', 'page' => 'Pagina', 'previous' => 'Vorige', 'next' => 'Volgende', 'nav_design' => '1 · Ontwerp aanpassen', 'nav_design_help' => 'Voeg een afbeeldingsachtergrond, effen kleur, verloop of kant-en-klaar palet toe.', 'nav_create' => '2 · Maken / importeren', 'nav_create_help' => 'Maak een manuele QSL of importeer ADIF via drag-and-drop.', 'err_select_bg' => 'Selecteer een achtergrondafbeelding.', 'ok_bg_image' => 'Afbeeldingsachtergrond opgeslagen.', 'err_gradient_invalid' => 'Ongeldige verloopkleuren.', 'ok_bg_gradient' => 'Verloopachtergrond opgeslagen.', 'err_solid_invalid' => 'Ongeldige effen kleur.', 'ok_bg_solid' => 'Effen achtergrond opgeslagen.', 'err_palette_invalid' => 'Ongeldig vooraf ingesteld palet.', 'ok_bg_palette' => 'Vooraf ingesteld palet opgeslagen.', 'ok_bg_default' => 'Standaardachtergrond bijgewerkt.', 'ok_bg_deleted' => 'Achtergrond verwijderd.', 'err_no_adif' => 'Geen ADIF-bestand ontvangen.', 'err_no_valid_adif' => 'Geen geldig ADIF-bestand kon worden verwerkt.', 'ok_qso_imported' => 'QSO’s geïmporteerd uit ADIF-bestanden.', 'err_qso_none' => 'Geen nieuwe QSO geïmporteerd.', 'ok_qsl_generated' => 'QSL-kaarten gegenereerd.', 'err_qsl_none' => 'Geen QSL gegenereerd. Lege selectie of kaarten bestaan al.', 'ok_qsl_created' => 'QSL aangemaakt.', 'ok_qso_deleted' => 'QSO verwijderd.', 'ok_qsl_deleted' => 'QSL verwijderd.', 'err_unknown_action' => 'Onbekende QSL-actie.', 'label_bg_image' => 'Afbeeldingsachtergrond', 'label_gradient' => 'Verloop met 2 kleuren', 'label_delete' => 'Verwijderen', 'empty_qso' => 'Nog geen QSO geïmporteerd.', 'empty_qso_filtered' => 'Geen QSO komt overeen met de actieve filters.', 'empty_qsl' => 'Nog geen QSL gegenereerd.', 'empty_qsl_filtered' => 'Geen QSL komt overeen met de zoekopdracht.', 'nav_manage' => '3 · Beheren en exporteren', 'nav_manage_help' => 'Filter je QSO’s, genereer in bulk en exporteer voor-/achterkant kaarten.', 'bulk_generate' => 'Geselecteerde QSL-kaarten genereren', 'select_all' => 'Alles selecteren', 'select_none' => 'Selectie wissen', 'qso_search_ph' => 'Filter op roepnaam, datum, mode...', 'qsl_search_ph' => 'Zoek een QSL (titel, roepnaam, band...)', 'all_bands' => 'Alle banden', 'all_modes' => 'Alle modi'],
];
$qt = static function (string $key) use ($locale, $qslI18n): string {
    return (string) (($qslI18n[$locale] ?? $qslI18n['fr'])[$key] ?? $key);
};
$drawPresetPalettes = [
    'club_blue' => ['label' => 'Bleu club (dégradé)', 'primary' => '#0B1F3A', 'secondary' => '#1D4ED8'],
    'sunset' => ['label' => 'Sunset (dégradé)', 'primary' => '#7C2D12', 'secondary' => '#F97316'],
    'northern' => ['label' => 'Aurore (dégradé)', 'primary' => '#0F766E', 'secondary' => '#22D3EE'],
    'forest' => ['label' => 'Forêt (couleur unie)', 'primary' => '#166534', 'secondary' => '#166534'],
    'slate' => ['label' => 'Ardoise (couleur unie)', 'primary' => '#334155', 'secondary' => '#334155'],
];

db()->exec(
    'CREATE TABLE IF NOT EXISTS qsl_background_presets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        label VARCHAR(120) NOT NULL,
        type VARCHAR(16) NOT NULL,
        image_data_uri LONGTEXT DEFAULT NULL,
        color_primary VARCHAR(7) DEFAULT NULL,
        color_secondary VARCHAR(7) DEFAULT NULL,
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        $isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

        if ($action === 'save_background_image') {
            $label = trim((string) ($_POST['background_label'] ?? 'Fond image'));
            $label = mb_safe_substr($label !== '' ? $label : 'Fond image', 0, 120);
            $dataUri = qsl_background_upload_to_data_uri($_FILES['background_image'] ?? null);
            if ($dataUri === '') {
                throw new RuntimeException($qt('err_select_bg'));
            }
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, ?, NULL, NULL, ?)'
            )->execute([$memberId, $label, 'image', $dataUri, $setDefault ? 1 : 0]);
            set_flash('success', $qt('ok_bg_image'));
        } elseif ($action === 'save_background_gradient') {
            $label = trim((string) ($_POST['gradient_label'] ?? 'Fond dégradé'));
            $label = mb_safe_substr($label !== '' ? $label : 'Fond dégradé', 0, 120);
            $primary = trim((string) ($_POST['background_primary'] ?? '#0B1F3A'));
            $secondary = trim((string) ($_POST['background_secondary'] ?? '#1D4ED8'));
            if (preg_match('/^#[A-Fa-f0-9]{6}$/', $primary) !== 1 || preg_match('/^#[A-Fa-f0-9]{6}$/', $secondary) !== 1) {
                throw new RuntimeException($qt('err_gradient_invalid'));
            }
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, NULL, ?, ?, ?)'
            )->execute([$memberId, $label, 'gradient', strtoupper($primary), strtoupper($secondary), $setDefault ? 1 : 0]);
            set_flash('success', $qt('ok_bg_gradient'));
        } elseif ($action === 'save_background_solid') {
            $label = trim((string) ($_POST['solid_label'] ?? 'Fond couleur unie'));
            $label = mb_safe_substr($label !== '' ? $label : 'Fond couleur unie', 0, 120);
            $solidColor = trim((string) ($_POST['background_solid'] ?? '#1E293B'));
            if (preg_match('/^#[A-Fa-f0-9]{6}$/', $solidColor) !== 1) {
                throw new RuntimeException($qt('err_solid_invalid'));
            }
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            $normalizedColor = strtoupper($solidColor);
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, NULL, ?, ?, ?)'
            )->execute([$memberId, $label, 'gradient', $normalizedColor, $normalizedColor, $setDefault ? 1 : 0]);
            set_flash('success', $qt('ok_bg_solid'));
        } elseif ($action === 'save_background_palette') {
            $paletteKey = trim((string) ($_POST['preset_palette'] ?? ''));
            $palette = $drawPresetPalettes[$paletteKey] ?? null;
            if (!is_array($palette)) {
                throw new RuntimeException($qt('err_palette_invalid'));
            }
            $label = trim((string) ($_POST['palette_label'] ?? (string) ($palette['label'] ?? 'Palette prédéfinie')));
            $label = mb_safe_substr($label !== '' ? $label : (string) ($palette['label'] ?? 'Palette prédéfinie'), 0, 120);
            $primary = (string) ($palette['primary'] ?? '#0B1F3A');
            $secondary = (string) ($palette['secondary'] ?? '#1D4ED8');
            $setDefault = ((string) ($_POST['set_default'] ?? '') === '1');
            if ($setDefault) {
                db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            }
            db()->prepare(
                'INSERT INTO qsl_background_presets (member_id, label, type, image_data_uri, color_primary, color_secondary, is_default)
                 VALUES (?, ?, ?, NULL, ?, ?, ?)'
            )->execute([$memberId, $label, 'gradient', strtoupper($primary), strtoupper($secondary), $setDefault ? 1 : 0]);
            set_flash('success', $qt('ok_bg_palette'));
        } elseif ($action === 'set_default_background') {
            $presetId = (int) ($_POST['preset_id'] ?? 0);
            db()->prepare('UPDATE qsl_background_presets SET is_default = 0 WHERE member_id = ?')->execute([$memberId]);
            db()->prepare('UPDATE qsl_background_presets SET is_default = 1 WHERE id = ? AND member_id = ?')->execute([$presetId, $memberId]);
            set_flash('success', $qt('ok_bg_default'));
        } elseif ($action === 'delete_background') {
            $presetId = (int) ($_POST['preset_id'] ?? 0);
            db()->prepare('DELETE FROM qsl_background_presets WHERE id = ? AND member_id = ? LIMIT 1')->execute([$presetId, $memberId]);
            $hasDefault = db()->prepare('SELECT id FROM qsl_background_presets WHERE member_id = ? AND is_default = 1 LIMIT 1');
            $hasDefault->execute([$memberId]);
            if (!$hasDefault->fetch()) {
                $fallback = db()->prepare('SELECT id FROM qsl_background_presets WHERE member_id = ? ORDER BY id ASC LIMIT 1');
                $fallback->execute([$memberId]);
                $first = $fallback->fetch();
                if ($first) {
                    db()->prepare('UPDATE qsl_background_presets SET is_default = 1 WHERE id = ? AND member_id = ?')->execute([(int) $first['id'], $memberId]);
                }
            }
            set_flash('success', $qt('ok_bg_deleted'));
        } elseif ($action === 'import_adif') {
            $uploads = [];
            if (isset($_FILES['adif_files']) && is_array($_FILES['adif_files'])) {
                $batch = $_FILES['adif_files'];
                $names = (array) ($batch['name'] ?? []);
                foreach (array_keys($names) as $index) {
                    $uploads[] = [
                        'name' => (string) ($batch['name'][$index] ?? ''),
                        'type' => (string) ($batch['type'][$index] ?? ''),
                        'tmp_name' => (string) ($batch['tmp_name'][$index] ?? ''),
                        'error' => (int) ($batch['error'][$index] ?? UPLOAD_ERR_NO_FILE),
                        'size' => (int) ($batch['size'][$index] ?? 0),
                    ];
                }
            } elseif (isset($_FILES['adif_file']) && is_array($_FILES['adif_file'])) {
                $uploads[] = $_FILES['adif_file'];
            }

            if ($uploads === []) {
                throw new RuntimeException($qt('err_no_adif'));
            }
            $totalImported = 0;
            $processedFiles = 0;
            foreach ($uploads as $file) {
                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }
                $tmpName = (string) ($file['tmp_name'] ?? '');
                if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                    continue;
                }
                $content = file_get_contents($tmpName);
                if ($content === false) {
                    continue;
                }
                $records = parse_adif($content);
                $totalImported += import_adif_records((int) $user['id'], $records);
                $processedFiles++;
            }

            if ($processedFiles === 0) {
                throw new RuntimeException($qt('err_no_valid_adif'));
            }

            if ($isAjaxRequest) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => true,
                    'files' => $processedFiles,
                    'imported' => $totalImported,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            if ($totalImported > 0) {
                set_flash('success', $totalImported . ' ' . $qt('ok_qso_imported'));
            } else {
                set_flash('error', $qt('err_qso_none'));
            }
        } elseif ($action === 'generate_batch') {
            $ids = array_map('intval', $_POST['qso_ids'] ?? []);
            $templateName = ((string) ($_POST['qsl_template_name'] ?? 'classic')) === 'classic_duplex' ? 'classic_duplex' : 'classic';
            $count = create_qsl_cards_from_qsos((int) $user['id'], $ids, $templateName);
            if ($count > 0) {
                set_flash('success', $count . ' ' . $qt('ok_qsl_generated'));
            } else {
                set_flash('error', $qt('err_qsl_none'));
            }
        } elseif ($action === 'create_manual') {
            $presetId = (int) ($_POST['background_preset_id'] ?? 0);
            $templateName = ((string) ($_POST['template_name'] ?? 'classic')) === 'classic_duplex' ? 'classic_duplex' : 'classic';
            $presetStmt = db()->prepare('SELECT id, type, image_data_uri, color_primary, color_secondary FROM qsl_background_presets WHERE id = ? AND member_id = ? LIMIT 1');
            $presetStmt->execute([$presetId, $memberId]);
            $selectedPreset = $presetStmt->fetch();
            if (!$selectedPreset) {
                $defaultStmt = db()->prepare('SELECT id, type, image_data_uri, color_primary, color_secondary FROM qsl_background_presets WHERE member_id = ? AND is_default = 1 LIMIT 1');
                $defaultStmt->execute([$memberId]);
                $selectedPreset = $defaultStmt->fetch();
            }
            $data = [
                'own_call' => (string) ($user['callsign'] ?? ''),
                'own_name' => (string) ($user['full_name'] ?? ''),
                'own_qth' => (string) ($user['qth'] ?? ''),
                'qso_call' => trim((string) ($_POST['qso_call'] ?? '')),
                'qso_date' => trim((string) ($_POST['qso_date'] ?? '')),
                'time_on' => trim((string) ($_POST['time_on'] ?? '')),
                'band' => trim((string) ($_POST['band'] ?? '')),
                'mode' => trim((string) ($_POST['mode'] ?? '')),
                'rst_sent' => trim((string) ($_POST['rst_sent'] ?? '')),
                'rst_recv' => trim((string) ($_POST['rst_recv'] ?? '')),
                'comment' => trim((string) ($_POST['comment'] ?? 'TNX QSO 73')),
                'background_primary' => (string) ($selectedPreset['color_primary'] ?? '#0B1F3A'),
                'background_secondary' => (string) ($selectedPreset['color_secondary'] ?? '#1D4ED8'),
                'background_image_data_uri' => (string) ($selectedPreset['image_data_uri'] ?? ''),
                'template_name' => $templateName,
            ];

            $payload = build_qsl_svg_payload($user, $data, (string) $data['comment']);
            $svg = generate_qsl_svg($payload);
            $stmt = db()->prepare(
                'INSERT INTO qsl_cards (member_id, title, qso_call, qso_date, time_on, band, mode, rst_sent, rst_recv, template_name, svg_content)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $memberId,
                qsl_card_title($payload),
                $payload['qso_call'],
                $payload['qso_date'] !== '' ? $payload['qso_date'] : null,
                $payload['time_on'] !== '' ? $payload['time_on'] : null,
                $payload['band'] !== '' ? $payload['band'] : null,
                $payload['mode'] !== '' ? $payload['mode'] : null,
                $payload['rst_sent'] !== '' ? $payload['rst_sent'] : null,
                $payload['rst_recv'] !== '' ? $payload['rst_recv'] : null,
                $templateName,
                $svg,
            ]);
            set_flash('success', $qt('ok_qsl_created'));
        } elseif ($action === 'delete_qso' || isset($_POST['delete_qso_id'])) {
            $qsoId = (int) ($_POST['delete_qso_id'] ?? ($_POST['qso_id'] ?? 0));
            $stmt = db()->prepare('DELETE FROM qso_logs WHERE id = ? AND member_id = ? LIMIT 1');
            $stmt->execute([$qsoId, $memberId]);
            set_flash('success', $qt('ok_qso_deleted'));
        } elseif ($action === 'delete_qsl') {
            $stmt = db()->prepare('DELETE FROM qsl_cards WHERE id = ? AND member_id = ? LIMIT 1');
            $stmt->execute([(int) ($_POST['qsl_id'] ?? 0), $memberId]);
            set_flash('success', $qt('ok_qsl_deleted'));
        } else {
            throw new RuntimeException($qt('err_unknown_action'));
        }

        if ($isAjaxRequest) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        redirect('qsl');
    } catch (Throwable $throwable) {
        $isAjaxRequest = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if ($isAjaxRequest) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => $throwable->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        set_flash('error', $throwable->getMessage());
        redirect('qsl');
    }
}

$qsoLogs = db()->prepare('SELECT * FROM qso_logs WHERE member_id = ? ORDER BY id DESC LIMIT 100');
$qsoLogs->execute([$memberId]);
$qsoRows = $qsoLogs->fetchAll();

$qslCards = db()->prepare('SELECT * FROM qsl_cards WHERE member_id = ? ORDER BY id DESC LIMIT 50');
$qslCards->execute([$memberId]);
$backgroundPresetsStmt = db()->prepare('SELECT id, label, type, image_data_uri, color_primary, color_secondary, is_default FROM qsl_background_presets WHERE member_id = ? ORDER BY is_default DESC, id DESC');
$backgroundPresetsStmt->execute([$memberId]);
$backgroundPresets = $backgroundPresetsStmt->fetchAll();
$qslRows = $qslCards->fetchAll();
$defaultBackgroundPresetId = 0;
foreach ($backgroundPresets as $presetRow) {
    if ((int) ($presetRow['is_default'] ?? 0) === 1) {
        $defaultBackgroundPresetId = (int) ($presetRow['id'] ?? 0);
        break;
    }
}
$hasCreatedQsl = count($qslRows) > 0;

$qsoSearch = trim((string) ($_GET['qso_search'] ?? ''));
$qsoBandFilter = mb_safe_strtoupper(trim((string) ($_GET['qso_band'] ?? '')));
$qsoModeFilter = mb_safe_strtoupper(trim((string) ($_GET['qso_mode'] ?? '')));
$qslSearch = trim((string) ($_GET['qsl_search'] ?? ''));
$qsoPage = max(1, (int) ($_GET['qso_page'] ?? 1));
$qslPage = max(1, (int) ($_GET['qsl_page'] ?? 1));
$qsoPerPage = 25;
$qslPerPage = 25;

$qsoBandOptions = [];
$qsoModeOptions = [];
foreach ($qsoRows as $row) {
    $band = mb_safe_strtoupper(trim((string) ($row['band'] ?? '')));
    $mode = mb_safe_strtoupper(trim((string) ($row['mode'] ?? '')));
    if ($band !== '') {
        $qsoBandOptions[$band] = true;
    }
    if ($mode !== '') {
        $qsoModeOptions[$mode] = true;
    }
}

$matchesTextFilter = static function (string $needle, array $fields): bool {
    if ($needle === '') {
        return true;
    }
    foreach ($fields as $field) {
        if (stripos($field, $needle) !== false) {
            return true;
        }
    }

    return false;
};

$qsoEqslStatus = static function (array $row): string {
    $raw = (string) ($row['raw_payload'] ?? '');
    if ($raw === '') {
        return '—';
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        return '—';
    }

    $sent = qsl_normalize_qsl_status((string) ($payload['eqsl_qsl_sent'] ?? ''));
    $received = qsl_normalize_qsl_status((string) ($payload['eqsl_qsl_rcvd'] ?? ''));
    if ($sent === '' && $received === '') {
        return '—';
    }

    return 'S:' . ($sent !== '' ? $sent : '—') . ' / R:' . ($received !== '' ? $received : '—');
};

$filteredQsoRows = array_values(array_filter($qsoRows, static function (array $row) use ($matchesTextFilter, $qsoSearch, $qsoBandFilter, $qsoModeFilter): bool {
    $band = mb_safe_strtoupper(trim((string) ($row['band'] ?? '')));
    $mode = mb_safe_strtoupper(trim((string) ($row['mode'] ?? '')));
    if ($qsoBandFilter !== '' && $band !== $qsoBandFilter) {
        return false;
    }
    if ($qsoModeFilter !== '' && $mode !== $qsoModeFilter) {
        return false;
    }

    return $matchesTextFilter($qsoSearch, [
        (string) ($row['qso_call'] ?? ''),
        (string) ($row['qso_date'] ?? ''),
        (string) ($row['band'] ?? ''),
        (string) ($row['mode'] ?? ''),
        (string) ($row['comment'] ?? ''),
    ]);
}));

$filteredQslRows = array_values(array_filter($qslRows, static function (array $row) use ($matchesTextFilter, $qslSearch): bool {
    return $matchesTextFilter($qslSearch, [
        (string) ($row['title'] ?? ''),
        (string) ($row['qso_call'] ?? ''),
        (string) ($row['qso_date'] ?? ''),
        (string) ($row['band'] ?? ''),
        (string) ($row['mode'] ?? ''),
    ]);
}));

$qsoTotal = count($filteredQsoRows);
$qslTotal = count($filteredQslRows);
$qsoTotalPages = max(1, (int) ceil($qsoTotal / $qsoPerPage));
$qslTotalPages = max(1, (int) ceil($qslTotal / $qslPerPage));
$qsoPage = min($qsoPage, $qsoTotalPages);
$qslPage = min($qslPage, $qslTotalPages);
$qsoOffset = ($qsoPage - 1) * $qsoPerPage;
$qslOffset = ($qslPage - 1) * $qslPerPage;
$pagedQsoRows = array_slice($filteredQsoRows, $qsoOffset, $qsoPerPage);
$pagedQslRows = array_slice($filteredQslRows, $qslOffset, $qslPerPage);

$buildQslPageUrl = static function (int $targetQsoPage, int $targetQslPage) use ($qsoSearch, $qsoBandFilter, $qsoModeFilter, $qslSearch): string {
    $params = ['route' => 'qsl'];
    if ($qsoSearch !== '') { $params['qso_search'] = $qsoSearch; }
    if ($qsoBandFilter !== '') { $params['qso_band'] = $qsoBandFilter; }
    if ($qsoModeFilter !== '') { $params['qso_mode'] = $qsoModeFilter; }
    if ($qslSearch !== '') { $params['qsl_search'] = $qslSearch; }
    if ($targetQsoPage > 1) { $params['qso_page'] = (string) $targetQsoPage; }
    if ($targetQslPage > 1) { $params['qsl_page'] = (string) $targetQslPage; }

    return base_url('index.php?' . http_build_query($params));
};

$generatedByQsoId = [];
foreach ($qslRows as $card) {
    $key = qsl_normalize_callsign((string) ($card['qso_call'] ?? '')) . '|'
        . qsl_normalize_date((string) ($card['qso_date'] ?? '')) . '|'
        . qsl_normalize_time((string) ($card['time_on'] ?? ''));
    if ($key !== '||') {
        $generatedByQsoId[$key] = true;
    }
}

ksort($qsoBandOptions);
ksort($qsoModeOptions);

ob_start();
?>
<div class="qsl-page">
<section class="card qsl-studio-overview">
    <h2><?= e($qt('studio')) ?></h2>
    <p class="help"><?= e($qt('studio_help')) ?></p>
    <div class="grid-3">
        <a class="inner-card qsl-studio-link-card" href="#qsl-draw" data-qsl-nav-target="design">
            <span class="badge muted"><?= e($qt('nav_design')) ?></span>
            <p class="help"><?= e($qt('nav_design_help')) ?></p>
        </a>
        <a class="inner-card qsl-studio-link-card" href="#qsl-create" data-qsl-nav-target="create">
            <span class="badge muted"><?= e($qt('nav_create')) ?></span>
            <p class="help"><?= e($qt('nav_create_help')) ?></p>
        </a>
        <a class="inner-card qsl-studio-link-card" href="#qsl-view" data-qsl-nav-target="manage">
            <span class="badge muted"><?= e($qt('nav_manage')) ?></span>
            <p class="help"><?= e($qt('nav_manage_help')) ?></p>
        </a>
    </div>
</section>

<section class="card" id="qsl-draw" data-qsl-draw-assistant data-qsl-panel="design">
    <h2><?= e($qt('design')) ?></h2>
    <p class="help">Choisissez un type de fond. Le formulaire s’adapte automatiquement et l’aperçu se met à jour en direct.</p>
    <div class="actions">
        <label><input type="radio" name="qsl_draw_flow" value="image" data-qsl-draw-choice> <?= e($qt('label_bg_image')) ?></label>
        <label><input type="radio" name="qsl_draw_flow" value="solid" data-qsl-draw-choice> Couleur unique</label>
        <label><input type="radio" name="qsl_draw_flow" value="gradient" data-qsl-draw-choice checked> <?= e($qt('label_gradient')) ?></label>
        <label><input type="radio" name="qsl_draw_flow" value="palette" data-qsl-draw-choice> Couleurs prédéfinies</label>
    </div>
    <div class="split qsl-background-workbench">
        <div>
            <div class="stack">
                <form method="post" enctype="multipart/form-data" class="stack" data-preview-form="image" data-qsl-draw-panel="image">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_image">
                    <label>Nom du fond image<input type="text" name="background_label" maxlength="120" placeholder="Ex: Shack ON4CRD"></label>
                    <label>Image
                        <input type="file" name="background_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required data-preview-image-input>
                    </label>
                    <label><input type="checkbox" name="set_default" value="1"> Définir comme fond par défaut</label>
                    <button type="submit" class="button secondary">Ajouter le fond image</button>
                </form>
                <form method="post" class="stack" data-preview-form="gradient" data-qsl-draw-panel="gradient">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_gradient">
                    <label>Nom du fond dégradé<input type="text" name="gradient_label" maxlength="120" placeholder="Ex: Bleu club"></label>
                    <label><span>Couleur de fond 1</span><input class="qsl-color-input" type="color" name="background_primary" value="#0B1F3A" data-preview-color-primary></label>
                    <label><span>Couleur de fond 2</span><input class="qsl-color-input" type="color" name="background_secondary" value="#1D4ED8" data-preview-color-secondary></label>
                    <label><input type="checkbox" name="set_default" value="1"> Définir comme fond par défaut</label>
                    <button type="submit" class="button secondary">Ajouter le fond dégradé</button>
                </form>
                <form method="post" class="stack is-hidden" data-preview-form="solid" data-qsl-draw-panel="solid">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_solid">
                    <label>Nom de la couleur<input type="text" name="solid_label" maxlength="120" placeholder="Ex: Bleu nuit"></label>
                    <label>Couleur unie<input type="color" name="background_solid" value="#1E293B" data-preview-solid-color></label>
                    <label><input type="checkbox" name="set_default" value="1"> Définir comme fond par défaut</label>
                    <button type="submit" class="button secondary">Ajouter la couleur unie</button>
                </form>
                <form method="post" class="stack is-hidden" data-preview-form="palette" data-qsl-draw-panel="palette">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_palette">
                    <label>Palette prédéfinie
                        <select name="preset_palette" data-preview-palette-select>
                            <?php foreach ($drawPresetPalettes as $paletteKey => $palette): ?>
                                <option
                                    value="<?= e($paletteKey) ?>"
                                    data-primary="<?= e((string) ($palette['primary'] ?? '#0B1F3A')) ?>"
                                    data-secondary="<?= e((string) ($palette['secondary'] ?? '#1D4ED8')) ?>"
                                >
                                    <?= e((string) ($palette['label'] ?? 'Palette')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Nom personnalisé (optionnel)<input type="text" name="palette_label" maxlength="120" placeholder="Ex: Palette Aurora"></label>
                    <label><input type="checkbox" name="set_default" value="1"> Définir comme fond par défaut</label>
                    <button type="submit" class="button secondary">Ajouter la palette</button>
                </form>
            </div>
        </div>
        <div class="qsl-live-preview-wrap">
            <h3>Aperçu en direct</h3>
            <div class="qsl-live-preview" data-qsl-preview>
                <div class="qsl-live-preview-card" data-qsl-preview-card>
                    <p class="qsl-live-preview-title">QSL Preview</p>
                    <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> → TO: F4XYZ</p>
                </div>
            </div>
            <p class="help">Aperçu du fond en cours de création (image, couleur unique, dégradé ou palette prédéfinie).</p>
        </div>
    </div>
    <?php if ($backgroundPresets !== []): ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Fond</th><th>Type</th><th>Défaut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($backgroundPresets as $preset): ?>
                    <tr>
                        <td><?= e((string) ($preset['label'] ?? 'Fond')) ?></td>
                        <td><?= e(((string) ($preset['type'] ?? 'gradient')) === 'image' ? 'Image' : 'Dégradé') ?></td>
                        <td><?= ((int) ($preset['is_default'] ?? 0) === 1) ? '✅' : '—' ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="preset_id" value="<?= (int) ($preset['id'] ?? 0) ?>">
                                <button type="submit" name="action" value="set_default_background" class="button secondary small">Par défaut</button>
                                <button type="submit" name="action" value="delete_background" class="button secondary small"><?= e($qt('label_delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="card" id="qsl-create" data-qsl-assistant data-qsl-panel="create">
    <h1><?= e($qt('create')) ?></h1>
    <p class="help">Choisissez votre objectif : création manuelle détaillée ou import ADIF instantané.</p>

    <div class="stack">
        <div>
            <span class="badge muted">Étape A</span>
            <h2>Quel est votre besoin maintenant ?</h2>
            <div class="actions">
                <label><input type="radio" name="qsl_assistant_flow" value="manual" data-qsl-assistant-choice checked> Créer une QSL manuelle</label>
                <label><input type="radio" name="qsl_assistant_flow" value="adif" data-qsl-assistant-choice> Importer des QSO ADIF</label>
            </div>
        </div>

        <section class="stack" data-qsl-assistant-panel="manual">
            <div>
                <span class="badge muted">Étape B</span>
                <h2>Formulaire manuel assisté</h2>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create_manual">
                <div class="form-grid">
                    <label>Indicatif correspondant<input type="text" name="qso_call" maxlength="64" required data-manual-preview-source="qso_call"></label>
                    <label>Date QSO<input type="date" name="qso_date" data-manual-preview-source="qso_date"></label>
                    <label>UTC<input type="time" name="time_on" step="60" data-manual-preview-source="time_on"></label>
                    <label>Bande<input type="text" name="band" maxlength="32" placeholder="20M" data-manual-preview-source="band"></label>
                    <label>Mode<input type="text" name="mode" maxlength="32" placeholder="SSB" data-manual-preview-source="mode"></label>
                    <label>RST envoyé<input type="text" name="rst_sent" maxlength="16" placeholder="59" data-manual-preview-source="rst_sent"></label>
                    <label>RST reçu<input type="text" name="rst_recv" maxlength="16" placeholder="59" data-manual-preview-source="rst_recv"></label>
                    <label>Commentaire
                        <textarea name="comment" rows="3" maxlength="180" data-manual-preview-source="comment">TNX QSO 73</textarea>
                    </label>
                    <label>Fond QSL
                        <select name="background_preset_id" data-manual-preview-source="background_preset_id">
                            <option value="0" data-bg-type="gradient" data-bg-primary="#0B1F3A" data-bg-secondary="#1D4ED8" <?= $defaultBackgroundPresetId === 0 ? 'selected' : '' ?>>Fond par défaut système</option>
                            <?php foreach ($backgroundPresets as $preset): ?>
                                <?php
                                $presetId = (int) ($preset['id'] ?? 0);
                                $isDefaultPreset = (int) ($preset['is_default'] ?? 0) === 1;
                                $presetLabel = (string) ($preset['label'] ?? 'Fond');
                                $presetType = (string) ($preset['type'] ?? 'gradient');
                                $presetPrimary = (string) ($preset['color_primary'] ?? '#0B1F3A');
                                $presetSecondary = (string) ($preset['color_secondary'] ?? '#1D4ED8');
                                ?>
                                <option
                                    value="<?= $presetId ?>"
                                    data-bg-type="<?= e($presetType) ?>"
                                    data-bg-image="<?= e((string) ($preset['image_data_uri'] ?? '')) ?>"
                                    data-bg-primary="<?= e($presetPrimary) ?>"
                                    data-bg-secondary="<?= e($presetSecondary) ?>"
                                    <?= ($presetId === $defaultBackgroundPresetId) ? 'selected' : '' ?>
                                >
                                    <?= e($presetLabel) ?><?= $isDefaultPreset ? ' (défaut)' : '' ?> — <?= e($presetType === 'image' ? 'Image' : 'Dégradé') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Format d’impression
                        <select name="template_name">
                            <option value="classic">Recto uniquement</option>
                            <option value="classic_duplex">Recto-verso</option>
                        </select>
                    </label>
                </div>
                <p class="help">Choisissez un seul fond enregistré pour cette QSL.</p>
                <div class="qsl-live-preview-wrap" data-qsl-manual-preview>
                    <h3>Prévisualisation de la QSL</h3>
                    <div class="grid-2" data-manual-preview-layout>
                        <div class="qsl-live-preview">
                            <div class="qsl-live-preview-card" data-manual-preview-card>
                                <p class="qsl-live-preview-title">Aperçu recto</p>
                                <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> → TO: <span data-manual-preview-field="qso_call">F4XYZ</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail>DATE: <span data-manual-preview-field="qso_date">20260412</span> UTC: <span data-manual-preview-field="time_on">09:15</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail>BAND: <span data-manual-preview-field="band">20M</span> MODE: <span data-manual-preview-field="mode">SSB</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail>RST S/R: <span data-manual-preview-field="rst_sent">59</span>/<span data-manual-preview-field="rst_recv">59</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail><span data-manual-preview-field="comment">TNX QSO 73</span></p>
                                <p class="qsl-live-preview-meta is-hidden" data-manual-preview-front-message>QSL recto — détails au verso</p>
                            </div>
                        </div>
                        <div class="qsl-live-preview is-hidden" data-manual-preview-back-wrap>
                            <div class="qsl-live-preview-card" data-manual-preview-back-card>
                                <p class="qsl-live-preview-title">Aperçu verso</p>
                                <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> → TO: <span data-manual-preview-back-field="qso_call">F4XYZ</span></p>
                                <p class="qsl-live-preview-meta">DATE: <span data-manual-preview-back-field="qso_date">20260412</span> UTC: <span data-manual-preview-back-field="time_on">09:15</span></p>
                                <p class="qsl-live-preview-meta">BAND: <span data-manual-preview-back-field="band">20M</span> MODE: <span data-manual-preview-back-field="mode">SSB</span></p>
                                <p class="qsl-live-preview-meta">RST S/R: <span data-manual-preview-back-field="rst_sent">59</span>/<span data-manual-preview-back-field="rst_recv">59</span></p>
                                <p class="qsl-live-preview-meta"><span data-manual-preview-back-field="comment">TNX QSO 73</span></p>
                            </div>
                        </div>
                    </div>
                    <p class="help" data-manual-preview-note>Aperçu dynamique selon les champs du formulaire.</p>
                </div>
                <p><button class="button">Créer ma QSL</button></p>
            </form>
        </section>

        <section class="stack" data-qsl-assistant-panel="adif">
            <div>
                <span class="badge muted">Étape B</span>
                <h2>Import ADIF rapide</h2>
            </div>
            <form method="post" enctype="multipart/form-data" id="adif-dropzone-form" class="stack">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="import_adif">
                <div id="adif-dropzone" class="dropzone qsl-adif-dropzone">
                    <div class="dz-message">
                        Glissez-déposez vos fichiers ADIF ici
                        <small>ou cliquez pour sélectionner plusieurs fichiers (.adi, .adif)</small>
                    </div>
                </div>
                <input type="file" name="adif_files[]" id="adif-fallback-input" accept=".adi,.adif,text/plain" multiple hidden>
                <p class="help" id="adif-dropzone-status">Les fichiers seront traités automatiquement à l’ajout.</p>
            </form>
            <p class="help">Les doublons exacts sont ignorés automatiquement lors de l’import.</p>
        </section>
    </div>
</section>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css">
<script nonce="<?= e(csp_nonce()) ?>" src="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.js"></script>

<section class="card" id="qsl-view" data-qsl-panel="manage">
    <div class="row-between">
        <h2><?= e($qt('manage')) ?></h2>
        <span><?= count($qsoRows) ?> enregistrement(s)</span>
    </div>
    <?php if ($qsoRows === []): ?>
        <p><?= e($qt('empty_qso')) ?></p>
    <?php else: ?>
        <form method="get" class="inline-form qsl-filters">
            <input type="hidden" name="route" value="qsl">
            <input type="text" name="qso_search" value="<?= e($qsoSearch) ?>" placeholder="<?= e($qt('qso_search_ph')) ?>">
            <select name="qso_band">
                <option value=""><?= e($qt('all_bands')) ?></option>
                <?php foreach (array_keys($qsoBandOptions) as $option): ?>
                    <option value="<?= e($option) ?>" <?= $qsoBandFilter === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="qso_mode">
                <option value=""><?= e($qt('all_modes')) ?></option>
                <?php foreach (array_keys($qsoModeOptions) as $option): ?>
                    <option value="<?= e($option) ?>" <?= $qsoModeFilter === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button secondary small"><?= e($qt('filter')) ?></button>
            <a href="<?= e(base_url('index.php?route=qsl')) ?>" class="ghost"><?= e($qt('reset')) ?></a>
        </form>
        <form method="post">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="generate_batch">
            <div class="actions">
                <button type="button" class="button secondary small" data-qso-toggle="all"><?= e($qt('select_all')) ?></button>
                <button type="button" class="button secondary small" data-qso-toggle="none"><?= e($qt('select_none')) ?></button>
                <label>Format
                    <select name="qsl_template_name">
                        <option value="classic">Recto</option>
                        <option value="classic_duplex">Recto-verso</option>
                    </select>
                </label>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th></th><th>Call</th><th>Date</th><th>UTC</th><th>Bande</th><th>Mode</th><th>RST</th><th>eQSL</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pagedQsoRows as $row): ?>
                        <tr>
                            <td><input type="checkbox" name="qso_ids[]" value="<?= (int) $row['id'] ?>"></td>
                            <td><?= e((string) $row['qso_call']) ?></td>
                            <td><?= e(qsl_format_display_date((string) ($row['qso_date'] ?? ''))) ?></td>
                            <td><?= e(qsl_format_display_time((string) ($row['time_on'] ?? ''))) ?></td>
                            <td><?= e((string) $row['band']) ?></td>
                            <td><?= e((string) $row['mode']) ?></td>
                            <td><?= e((string) $row['rst_sent']) ?>/<?= e((string) $row['rst_recv']) ?></td>
                            <td><?= e($qsoEqslStatus($row)) ?></td>
                            <td><button class="button secondary small" type="submit" name="delete_qso_id" value="<?= (int) $row['id'] ?>"><?= e($qt('label_delete')) ?></button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($filteredQsoRows === []): ?>
                <p class="help"><?= e($qt('empty_qso_filtered')) ?></p>
            <?php endif; ?>
            <?php if ($qsoTotalPages > 1): ?>
                <div class="actions">
                    <span class="help"><?= e($qt('page')) ?> <?= $qsoPage ?> / <?= $qsoTotalPages ?></span>
                    <?php if ($qsoPage > 1): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage - 1, $qslPage)) ?>">← <?= e($qt('previous')) ?></a><?php endif; ?>
                    <?php if ($qsoPage < $qsoTotalPages): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage + 1, $qslPage)) ?>"><?= e($qt('next')) ?> →</a><?php endif; ?>
                </div>
            <?php endif; ?>
            <p><button class="button"><?= e($qt('bulk_generate')) ?></button></p>
        </form>
    <?php endif; ?>
</section>

<section class="card" data-qsl-panel="manage">
    <div class="row-between">
        <h2><?= e($qt('generated')) ?></h2>
        <span><?= count($qslRows) ?> carte(s)</span>
    </div>
    <?php if ($qslRows === []): ?>
        <p><?= e($qt('empty_qsl')) ?></p>
    <?php else: ?>
        <form method="get" class="inline-form qsl-filters">
            <input type="hidden" name="route" value="qsl">
            <input type="text" name="qsl_search" value="<?= e($qslSearch) ?>" placeholder="<?= e($qt('qsl_search_ph')) ?>">
            <button type="submit" class="button secondary small"><?= e($qt('filter')) ?></button>
            <a href="<?= e(base_url('index.php?route=qsl')) ?>" class="ghost"><?= e($qt('reset')) ?></a>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Titre</th><th>QSO</th><th>Date</th><th>Bande</th><th>Mode</th><th>Format</th><th>Aperçu</th><th>Export</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pagedQslRows as $row): ?>
                    <tr>
                        <td><?= e((string) $row['title']) ?></td>
                        <td><?= e((string) $row['qso_call']) ?></td>
                        <td><?= e(qsl_format_display_date((string) ($row['qso_date'] ?? ''))) ?></td>
                        <td><?= e((string) $row['band']) ?></td>
                        <td><?= e((string) $row['mode']) ?></td>
                        <td><?= qsl_template_supports_back((string) ($row['template_name'] ?? 'classic')) ? 'Recto-verso' : 'Recto' ?></td>
                        <td><a href="<?= e(base_url('index.php?route=qsl_preview&id=' . (int) $row['id'])) ?>">Voir</a></td>
                        <td>
                            <a href="<?= e(base_url('index.php?route=qsl_export&id=' . (int) $row['id'])) ?>">Recto SVG</a>
                            <?php if (qsl_template_supports_back((string) ($row['template_name'] ?? 'classic'))): ?>
                                · <a href="<?= e(base_url('index.php?route=qsl_export&id=' . (int) $row['id'] . '&side=back')) ?>">Verso SVG</a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete_qsl">
                                <input type="hidden" name="qsl_id" value="<?= (int) $row['id'] ?>">
                                <button class="button secondary small" type="submit"><?= e($qt('label_delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($filteredQslRows === []): ?>
            <p class="help"><?= e($qt('empty_qsl_filtered')) ?></p>
        <?php endif; ?>
        <?php if ($qslTotalPages > 1): ?>
            <div class="actions">
                <span class="help"><?= e($qt('page')) ?> <?= $qslPage ?> / <?= $qslTotalPages ?></span>
                <?php if ($qslPage > 1): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage, $qslPage - 1)) ?>">← <?= e($qt('previous')) ?></a><?php endif; ?>
                <?php if ($qslPage < $qslTotalPages): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage, $qslPage + 1)) ?>"><?= e($qt('next')) ?> →</a><?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
</div>
<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const navLinks = document.querySelectorAll('[data-qsl-nav-target]');
    const panels = document.querySelectorAll('[data-qsl-panel]');
    if (!navLinks.length || !panels.length) {
        return;
    }

    const activate = (target) => {
        const allowed = ['design', 'create', 'manage'];
        const current = allowed.includes(target) ? target : 'design';
        panels.forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.getAttribute('data-qsl-panel') !== current);
        });
        navLinks.forEach((link) => {
            const isActive = link.getAttribute('data-qsl-nav-target') === current;
            link.classList.toggle('active', isActive);
            link.setAttribute('aria-current', isActive ? 'page' : 'false');
        });
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            const target = link.getAttribute('data-qsl-nav-target') || 'design';
            activate(target);
        });
    });

    activate('design');
})();

(() => {
    const assistant = document.querySelector('[data-qsl-assistant]');
    if (!assistant) {
        return;
    }

    const choices = assistant.querySelectorAll('[data-qsl-assistant-choice]');
    const panels = assistant.querySelectorAll('[data-qsl-assistant-panel]');
    if (!choices.length || !panels.length) {
        return;
    }

    const syncPanels = () => {
        const selected = assistant.querySelector('[data-qsl-assistant-choice]:checked');
        const activeFlow = selected ? selected.value : 'manual';
        panels.forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.getAttribute('data-qsl-assistant-panel') !== activeFlow);
        });
    };

    choices.forEach((input) => input.addEventListener('change', syncPanels));
    syncPanels();
})();

(() => {
    const drawAssistant = document.querySelector('[data-qsl-draw-assistant]');
    if (!drawAssistant) {
        return;
    }

    const choices = drawAssistant.querySelectorAll('[data-qsl-draw-choice]');
    const panels = drawAssistant.querySelectorAll('[data-qsl-draw-panel]');
    if (!choices.length || !panels.length) {
        return;
    }

    const syncPanels = () => {
        const selected = drawAssistant.querySelector('[data-qsl-draw-choice]:checked');
        const activeFlow = selected ? selected.value : 'gradient';
        panels.forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.getAttribute('data-qsl-draw-panel') !== activeFlow);
        });
    };

    choices.forEach((input) => input.addEventListener('change', syncPanels));
    syncPanels();
})();

document.querySelectorAll('[data-qso-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const table = button.closest('form');
        if (!table) {
            return;
        }
        const checked = button.dataset.qsoToggle === 'all';
        table.querySelectorAll('input[name="qso_ids[]"]').forEach((checkbox) => {
            checkbox.checked = checked;
        });
    });
});

(() => {
    const previewRoot = document.querySelector('[data-qsl-manual-preview]');
    if (!previewRoot) {
        return;
    }

    const card = previewRoot.querySelector('[data-manual-preview-card]');
    if (!card) {
        return;
    }
    const backWrap = previewRoot.querySelector('[data-manual-preview-back-wrap]');
    const frontDetails = previewRoot.querySelectorAll('[data-manual-preview-front-detail]');
    const frontMessage = previewRoot.querySelector('[data-manual-preview-front-message]');
    const templateSource = document.querySelector('select[name="template_name"]');

    const fieldDefaults = {
        qso_call: 'F4XYZ',
        qso_date: '2026-04-12',
        time_on: '09:15',
        band: '20M',
        mode: 'SSB',
        rst_sent: '59',
        rst_recv: '59',
        comment: 'TNX QSO 73',
    };
    const formatPreviewDate = (value) => {
        if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
            const [year, month, day] = value.split('-');
            return `${day}/${month}/${year}`;
        }
        return value;
    };
    const formatPreviewTime = (value) => {
        if (/^\d{2}:\d{2}(:\d{2})?$/.test(value)) {
            return value.slice(0, 5);
        }
        return value;
    };

    const sync = () => {
        Object.keys(fieldDefaults).forEach((field) => {
            const source = document.querySelector(`[data-manual-preview-source="${field}"]`);
            const target = previewRoot.querySelector(`[data-manual-preview-field="${field}"]`);
            if (!target) {
                return;
            }
            const rawValue = source instanceof HTMLInputElement || source instanceof HTMLSelectElement || source instanceof HTMLTextAreaElement
                ? source.value
                : '';
            const value = (rawValue || '').trim();
            let displayValue = value !== '' ? value : fieldDefaults[field];
            if (field === 'qso_date') {
                displayValue = formatPreviewDate(displayValue);
            } else if (field === 'time_on') {
                displayValue = formatPreviewTime(displayValue);
            } else if (field !== 'comment') {
                displayValue = displayValue.toUpperCase();
            }
            target.textContent = displayValue;
            const backTarget = previewRoot.querySelector(`[data-manual-preview-back-field="${field}"]`);
            if (backTarget) {
                backTarget.textContent = displayValue;
            }
        });

        const presetSelect = document.querySelector('[data-manual-preview-source="background_preset_id"]');
        const note = previewRoot.querySelector('[data-manual-preview-note]');
        if (!(presetSelect instanceof HTMLSelectElement)) {
            return;
        }

        const selectedOption = presetSelect.selectedOptions[0];
        const type = selectedOption?.getAttribute('data-bg-type') || 'gradient';
        const imageData = selectedOption?.getAttribute('data-bg-image') || '';
        const primary = selectedOption?.getAttribute('data-bg-primary') || '#0B1F3A';
        const secondary = selectedOption?.getAttribute('data-bg-secondary') || '#1D4ED8';
        if (type === 'image' && imageData !== '') {
            card.style.backgroundImage = `linear-gradient(rgba(5, 10, 25, .35), rgba(5, 10, 25, .35)), url('${imageData}')`;
            card.style.backgroundSize = 'cover';
            card.style.backgroundPosition = 'center';
            if (note) {
                note.textContent = "<?= addslashes($qt('label_bg_image')) ?>";
            }
        } else if (type === 'gradient') {
            card.style.background = `linear-gradient(135deg, ${primary}, ${secondary})`;
            card.style.backgroundSize = '';
            card.style.backgroundPosition = '';
            if (note) {
                note.textContent = 'Aperçu dynamique selon les champs du formulaire.';
            }
        } else {
            card.style.background = 'linear-gradient(135deg, #0f172a, #1e293b)';
            card.style.backgroundSize = '';
            card.style.backgroundPosition = '';
            if (note) {
                note.textContent = "<?= addslashes($qt('label_bg_image')) ?>";
            }
        }

        const isDuplex = templateSource instanceof HTMLSelectElement && templateSource.value === 'classic_duplex';
        if (backWrap) {
            backWrap.classList.toggle('is-hidden', !isDuplex);
        }
        frontDetails.forEach((node) => node.classList.toggle('is-hidden', isDuplex));
        if (frontMessage) {
            frontMessage.classList.toggle('is-hidden', !isDuplex);
        }
    };

    document.querySelectorAll('[data-manual-preview-source]').forEach((source) => {
        source.addEventListener('input', sync);
        source.addEventListener('change', sync);
    });
    if (templateSource instanceof HTMLSelectElement) {
        templateSource.addEventListener('change', sync);
    }

    sync();
})();

(() => {
    const previewCard = document.querySelector('[data-qsl-preview-card]');
    if (!previewCard) {
        return;
    }

    const primaryInput = document.querySelector('[data-preview-color-primary]');
    const secondaryInput = document.querySelector('[data-preview-color-secondary]');
    const solidInput = document.querySelector('[data-preview-solid-color]');
    const paletteSelect = document.querySelector('[data-preview-palette-select]');
    const imageInput = document.querySelector('[data-preview-image-input]');
    const drawFlowChoices = document.querySelectorAll('[data-qsl-draw-choice]');
    const applyGradient = (primary = '#0B1F3A', secondary = '#1D4ED8') => {
        previewCard.style.backgroundImage = `linear-gradient(135deg, ${primary}, ${secondary})`;
    };
    const applyCurrentGradientInputs = () => {
        const primary = primaryInput?.value || '#0B1F3A';
        const secondary = secondaryInput?.value || '#1D4ED8';
        applyGradient(primary, secondary);
    };
    const applySolid = () => {
        const solid = solidInput?.value || '#1E293B';
        applyGradient(solid, solid);
    };
    const applyPalette = () => {
        if (!(paletteSelect instanceof HTMLSelectElement)) {
            return;
        }
        const option = paletteSelect.selectedOptions[0];
        const primary = option?.getAttribute('data-primary') || '#0B1F3A';
        const secondary = option?.getAttribute('data-secondary') || '#1D4ED8';
        applyGradient(primary, secondary);
    };
    const applyFromActiveFlow = () => {
        const activeFlow = document.querySelector('[data-qsl-draw-choice]:checked')?.getAttribute('value') || 'gradient';
        if (activeFlow === 'solid') {
            applySolid();
            return;
        }
        if (activeFlow === 'palette') {
            applyPalette();
            return;
        }
        applyCurrentGradientInputs();
    };

    primaryInput?.addEventListener('input', applyFromActiveFlow);
    secondaryInput?.addEventListener('input', applyFromActiveFlow);
    solidInput?.addEventListener('input', applyFromActiveFlow);
    paletteSelect?.addEventListener('change', applyFromActiveFlow);
    drawFlowChoices.forEach((choice) => {
        choice.addEventListener('change', applyFromActiveFlow);
    });
    applyFromActiveFlow();

    imageInput?.addEventListener('change', () => {
        const file = imageInput.files?.[0];
        if (!file) {
            applyFromActiveFlow();
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            if (typeof reader.result === 'string') {
                previewCard.style.backgroundImage = `linear-gradient(rgba(5, 10, 25, .35), rgba(5, 10, 25, .35)), url('${reader.result}')`;
                previewCard.style.backgroundSize = 'cover';
                previewCard.style.backgroundPosition = 'center';
            }
        };
        reader.readAsDataURL(file);
    });
})();

(() => {
    const form = document.getElementById('adif-dropzone-form');
    const status = document.getElementById('adif-dropzone-status');
    if (!form || typeof Dropzone === 'undefined') {
        return;
    }

    Dropzone.autoDiscover = false;
    const csrf = form.querySelector('input[name="_csrf"]')?.value || '';
    const action = form.querySelector('input[name="action"]')?.value || 'import_adif';
    const dropzone = new Dropzone('#adif-dropzone', {
        url: window.location.href,
        method: 'post',
        paramName: 'adif_files[]',
        acceptedFiles: '.adi,.adif,text/plain',
        uploadMultiple: false,
        parallelUploads: 6,
        maxFilesize: 8,
        addRemoveLinks: true,
        autoProcessQueue: true,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        params: {
            _csrf: csrf,
            action: action,
        },
        dictDefaultMessage: '',
    });

    dropzone.on('sending', () => {
        if (status) {
            status.textContent = 'Traitement des fichiers ADIF en cours...';
        }
    });
    dropzone.on('success', (file, response) => {
        const imported = Number(response?.imported || 0);
        const files = Number(response?.files || 1);
        if (status) {
            status.textContent = `${imported} QSO importé(s) depuis ${files} fichier(s).`;
        }
    });
    dropzone.on('error', (file, message) => {
        const text = typeof message === 'string' ? message : (message?.error || 'Échec de l’import ADIF.');
        if (status) {
            status.textContent = text;
        }
    });
    dropzone.on('queuecomplete', () => {
        window.setTimeout(() => window.location.reload(), 500);
    });
})();
</script>
<?php
echo render_layout((string) ob_get_clean(), 'QSL');
