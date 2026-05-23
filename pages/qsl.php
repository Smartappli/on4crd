<?php
declare(strict_types=1);

$user = require_login();
$memberId = (int) ($user['id'] ?? 0);
$locale = current_locale();
$qslEnglishMessages = ['studio' => 'QSL Studio Â· simple, guided, efficient', 'studio_help' => 'Everything is designed for speed: import your QSOs, create cards and export them seamlessly.', 'design' => '1) Design your QSL backgrounds', 'create' => '2) Create QSL cards easily', 'manage' => '3) Imported QSOs', 'generated' => 'Generated QSL cards', 'filter' => 'Filter', 'reset' => 'Reset', 'page' => 'Page', 'previous' => 'Previous', 'next' => 'Next', 'nav_design' => '1 Â· Customize design', 'nav_design_help' => 'Add an image background, a solid color, a gradient or a ready-to-use palette.', 'nav_create' => '2 Â· Create / import', 'nav_create_help' => 'Create a manual QSL or import your ADIF files via drag and drop.', 'err_select_bg' => 'Please select a background image.', 'ok_bg_image' => 'Image background saved.', 'err_gradient_invalid' => 'Invalid gradient colors.', 'ok_bg_gradient' => 'Gradient background saved.', 'err_solid_invalid' => 'Invalid solid color.', 'ok_bg_solid' => 'Solid color background saved.', 'err_palette_invalid' => 'Invalid preset palette.', 'ok_bg_palette' => 'Preset palette saved.', 'ok_bg_default' => 'Default background updated.', 'ok_bg_deleted' => 'Background deleted.', 'err_no_adif' => 'No ADIF file received.', 'err_no_valid_adif' => 'No valid ADIF file could be processed.', 'ok_qso_imported' => 'QSOs imported from ADIF files.', 'err_qso_none' => 'No new QSO imported.', 'ok_qsl_generated' => 'QSL cards generated.', 'err_qsl_none' => 'No QSL generated. Empty selection or cards already exist.', 'ok_qsl_created' => 'QSL created.', 'ok_qso_deleted' => 'QSO deleted.', 'ok_qsl_deleted' => 'QSL deleted.', 'err_unknown_action' => 'Unknown QSL action.', 'label_bg_image' => 'Image background', 'label_gradient' => '2-color gradient', 'label_delete' => 'Delete', 'empty_qso' => 'No imported QSO yet.', 'empty_qso_filtered' => 'No QSO matches active filters.', 'empty_qsl' => 'No generated QSL yet.', 'empty_qsl_filtered' => 'No QSL matches your search.', 'nav_manage' => '3 Â· Manage and export', 'nav_manage_help' => 'Filter your QSOs, generate in batch and export front/back cards.', 'bulk_generate' => 'Generate selected QSL cards', 'select_all' => 'Select all', 'select_none' => 'Select none', 'qso_search_ph' => 'Filter by callsign, date, mode...', 'qsl_search_ph' => 'Search a QSL (title, call, band...)', 'all_bands' => 'All bands', 'all_modes' => 'All modes', 'preview_dynamic' => 'Dynamic preview based on form fields.', 'adif_processing' => 'Processing ADIF files...', 'adif_import_error' => 'ADIF import failed.', 'adif_imported_status' => '{imported} QSO imported from {files} file(s).'];
$qslI18n = [
    'fr' => ['studio' => 'QSL Studio Â· simple, guidÃ©, efficace', 'studio_help' => 'Tout est pensÃ© pour aller vite : importez vos QSO, crÃ©ez vos cartes et exportez-les sans friction.', 'design' => '1) Designer vos fonds QSL', 'create' => '2) CrÃ©er des QSL facilement', 'manage' => '3) QSO importÃ©s', 'generated' => 'QSL gÃ©nÃ©rÃ©es', 'filter' => 'Filtrer', 'reset' => 'RÃ©initialiser', 'page' => 'Page', 'previous' => 'PrÃ©cÃ©dent', 'next' => 'Suivant', 'nav_design' => '1 Â· Personnaliser le design', 'nav_design_help' => 'Ajoutez un fond image, une couleur unie, un dÃ©gradÃ© ou une palette prÃªte Ã  lâ€™emploi.', 'nav_create' => '2 Â· CrÃ©er / importer', 'nav_create_help' => 'CrÃ©ez une QSL manuelle ou importez vos ADIF en glisserâ€‘dÃ©poser.', 'err_select_bg' => 'Veuillez sÃ©lectionner une image de fond.', 'ok_bg_image' => 'Fond image enregistrÃ©.', 'err_gradient_invalid' => 'Couleurs de dÃ©gradÃ© invalides.', 'ok_bg_gradient' => 'Fond dÃ©gradÃ© enregistrÃ©.', 'err_solid_invalid' => 'Couleur unie invalide.', 'ok_bg_solid' => 'Fond couleur unie enregistrÃ©.', 'err_palette_invalid' => 'Preset palette invalide.', 'ok_bg_palette' => 'Preset palette enregistrÃ©e.', 'ok_bg_default' => 'Fond par dÃ©faut mis Ã  jour.', 'ok_bg_deleted' => 'Fond supprimÃ©.', 'err_no_adif' => 'Aucun fichier ADIF reÃ§u.', 'err_no_valid_adif' => 'Aucun fichier ADIF valide nâ€™a pu Ãªtre traitÃ©.', 'ok_qso_imported' => 'QSO importÃ©s depuis les fichiers ADIF.', 'err_qso_none' => 'Aucun nouveau QSO importÃ©.', 'ok_qsl_generated' => 'QSL gÃ©nÃ©rÃ©es.', 'err_qsl_none' => 'Aucune QSL gÃ©nÃ©rÃ©e. SÃ©lection vide ou QSL dÃ©jÃ  existantes.', 'ok_qsl_created' => 'QSL crÃ©Ã©e.', 'ok_qso_deleted' => 'QSO supprimÃ©.', 'ok_qsl_deleted' => 'QSL supprimÃ©e.', 'err_unknown_action' => 'Action QSL inconnue.', 'label_bg_image' => 'Fond image', 'label_gradient' => 'DÃ©gradÃ© 2 couleurs', 'label_delete' => 'Supprimer', 'empty_qso' => 'Aucun QSO importÃ© pour le moment.', 'empty_qso_filtered' => 'Aucun QSO ne correspond aux filtres actifs.', 'empty_qsl' => 'Aucune QSL gÃ©nÃ©rÃ©e pour le moment.', 'empty_qsl_filtered' => 'Aucune QSL ne correspond Ã  la recherche.', 'nav_manage' => '3 Â· GÃ©rer et exporter', 'nav_manage_help' => 'Filtrez vos QSO, gÃ©nÃ©rez en lot et exportez vos cartes recto/verso.', 'bulk_generate' => 'GÃ©nÃ©rer les QSL sÃ©lectionnÃ©es', 'select_all' => 'Tout sÃ©lectionner', 'select_none' => 'Tout dÃ©sÃ©lectionner', 'qso_search_ph' => 'Filtrer par call, date, mode...', 'qsl_search_ph' => 'Rechercher une QSL (titre, call, bande...)', 'all_bands' => 'Toutes bandes', 'all_modes' => 'Tous modes', 'preview_dynamic' => 'AperÃ§u dynamique selon les champs du formulaire.', 'adif_processing' => 'Traitement des fichiers ADIF en cours...', 'adif_import_error' => 'Ã‰chec de lâ€™import ADIF.', 'adif_imported_status' => '{imported} QSO importÃ©(s) depuis {files} fichier(s).'],
    'en' => $qslEnglishMessages,
    'de' => ['studio' => 'QSL Studio Â· einfach, gefÃ¼hrt, effizient', 'studio_help' => 'Alles ist auf Tempo ausgelegt: QSOs importieren, Karten erstellen und ohne Reibung exportieren.', 'design' => '1) QSL-HintergrÃ¼nde gestalten', 'create' => '2) QSL-Karten einfach erstellen', 'manage' => '3) Importierte QSOs', 'generated' => 'Erstellte QSL-Karten', 'filter' => 'Filtern', 'reset' => 'ZurÃ¼cksetzen', 'page' => 'Seite', 'previous' => 'ZurÃ¼ck', 'next' => 'Weiter', 'nav_design' => '1 Â· Design anpassen', 'nav_design_help' => 'FÃ¼gen Sie ein Bild, eine Volltonfarbe, einen Verlauf oder eine fertige Palette hinzu.', 'nav_create' => '2 Â· Erstellen / importieren', 'nav_create_help' => 'Erstellen Sie eine manuelle QSL oder importieren Sie ADIF per Drag & Drop.', 'err_select_bg' => 'Bitte wÃ¤hlen Sie ein Hintergrundbild aus.', 'ok_bg_image' => 'Bildhintergrund gespeichert.', 'err_gradient_invalid' => 'UngÃ¼ltige Verlauf-Farben.', 'ok_bg_gradient' => 'Verlaufshintergrund gespeichert.', 'err_solid_invalid' => 'UngÃ¼ltige Volltonfarbe.', 'ok_bg_solid' => 'Einfarbiger Hintergrund gespeichert.', 'err_palette_invalid' => 'UngÃ¼ltige vordefinierte Palette.', 'ok_bg_palette' => 'Vordefinierte Palette gespeichert.', 'ok_bg_default' => 'Standardhintergrund aktualisiert.', 'ok_bg_deleted' => 'Hintergrund gelÃ¶scht.', 'err_no_adif' => 'Keine ADIF-Datei empfangen.', 'err_no_valid_adif' => 'Keine gÃ¼ltige ADIF-Datei konnte verarbeitet werden.', 'ok_qso_imported' => 'QSOs aus ADIF-Dateien importiert.', 'err_qso_none' => 'Kein neuer QSO importiert.', 'ok_qsl_generated' => 'QSL-Karten erstellt.', 'err_qsl_none' => 'Keine QSL erstellt. Leere Auswahl oder bereits vorhandene Karten.', 'ok_qsl_created' => 'QSL erstellt.', 'ok_qso_deleted' => 'QSO gelÃ¶scht.', 'ok_qsl_deleted' => 'QSL gelÃ¶scht.', 'err_unknown_action' => 'Unbekannte QSL-Aktion.', 'label_bg_image' => 'Bildhintergrund', 'label_gradient' => '2-Farben-Verlauf', 'label_delete' => 'LÃ¶schen', 'empty_qso' => 'Noch kein QSO importiert.', 'empty_qso_filtered' => 'Kein QSO entspricht den aktiven Filtern.', 'empty_qsl' => 'Noch keine QSL erstellt.', 'empty_qsl_filtered' => 'Keine QSL entspricht der Suche.', 'nav_manage' => '3 Â· Verwalten und exportieren', 'nav_manage_help' => 'Filtern Sie Ihre QSOs, erzeugen Sie Stapel und exportieren Sie Vorder-/RÃ¼ckseiten.', 'bulk_generate' => 'AusgewÃ¤hlte QSL-Karten erzeugen', 'select_all' => 'Alle auswÃ¤hlen', 'select_none' => 'Auswahl aufheben', 'qso_search_ph' => 'Nach Rufzeichen, Datum, Modus filtern...', 'qsl_search_ph' => 'QSL suchen (Titel, Rufzeichen, Band...)', 'all_bands' => 'Alle BÃ¤nder', 'all_modes' => 'Alle Modi', 'preview_dynamic' => 'Dynamische Vorschau basierend auf den Formularfeldern.', 'adif_processing' => 'ADIF-Dateien werden verarbeitet...', 'adif_import_error' => 'ADIF-Import fehlgeschlagen.', 'adif_imported_status' => '{imported} QSO aus {files} Datei(en) importiert.'],
    'nl' => ['studio' => 'QSL Studio Â· eenvoudig, begeleid, efficiÃ«nt', 'studio_help' => 'Alles is gericht op snelheid: importeer je QSOâ€™s, maak kaarten en exporteer zonder frictie.', 'design' => '1) Ontwerp je QSL-achtergronden', 'create' => '2) Maak eenvoudig QSL-kaarten', 'manage' => '3) GeÃ¯mporteerde QSOâ€™s', 'generated' => 'Gegenereerde QSL-kaarten', 'filter' => 'Filteren', 'reset' => 'Opnieuw instellen', 'page' => 'Pagina', 'previous' => 'Vorige', 'next' => 'Volgende', 'nav_design' => '1 Â· Ontwerp aanpassen', 'nav_design_help' => 'Voeg een afbeeldingsachtergrond, effen kleur, verloop of kant-en-klaar palet toe.', 'nav_create' => '2 Â· Maken / importeren', 'nav_create_help' => 'Maak een manuele QSL of importeer ADIF via drag-and-drop.', 'err_select_bg' => 'Selecteer een achtergrondafbeelding.', 'ok_bg_image' => 'Afbeeldingsachtergrond opgeslagen.', 'err_gradient_invalid' => 'Ongeldige verloopkleuren.', 'ok_bg_gradient' => 'Verloopachtergrond opgeslagen.', 'err_solid_invalid' => 'Ongeldige effen kleur.', 'ok_bg_solid' => 'Effen achtergrond opgeslagen.', 'err_palette_invalid' => 'Ongeldig vooraf ingesteld palet.', 'ok_bg_palette' => 'Vooraf ingesteld palet opgeslagen.', 'ok_bg_default' => 'Standaardachtergrond bijgewerkt.', 'ok_bg_deleted' => 'Achtergrond verwijderd.', 'err_no_adif' => 'Geen ADIF-bestand ontvangen.', 'err_no_valid_adif' => 'Geen geldig ADIF-bestand kon worden verwerkt.', 'ok_qso_imported' => 'QSOâ€™s geÃ¯mporteerd uit ADIF-bestanden.', 'err_qso_none' => 'Geen nieuwe QSO geÃ¯mporteerd.', 'ok_qsl_generated' => 'QSL-kaarten gegenereerd.', 'err_qsl_none' => 'Geen QSL gegenereerd. Lege selectie of kaarten bestaan al.', 'ok_qsl_created' => 'QSL aangemaakt.', 'ok_qso_deleted' => 'QSO verwijderd.', 'ok_qsl_deleted' => 'QSL verwijderd.', 'err_unknown_action' => 'Onbekende QSL-actie.', 'label_bg_image' => 'Afbeeldingsachtergrond', 'label_gradient' => 'Verloop met 2 kleuren', 'label_delete' => 'Verwijderen', 'empty_qso' => 'Nog geen QSO geÃ¯mporteerd.', 'empty_qso_filtered' => 'Geen QSO komt overeen met de actieve filters.', 'empty_qsl' => 'Nog geen QSL gegenereerd.', 'empty_qsl_filtered' => 'Geen QSL komt overeen met de zoekopdracht.', 'nav_manage' => '3 Â· Beheren en exporteren', 'nav_manage_help' => 'Filter je QSOâ€™s, genereer in bulk en exporteer voor-/achterkant kaarten.', 'bulk_generate' => 'Geselecteerde QSL-kaarten genereren', 'select_all' => 'Alles selecteren', 'select_none' => 'Selectie wissen', 'qso_search_ph' => 'Filter op roepnaam, datum, mode...', 'qsl_search_ph' => 'Zoek een QSL (titel, roepnaam, band...)', 'all_bands' => 'Alle banden', 'all_modes' => 'Alle modi', 'preview_dynamic' => 'Dynamische preview op basis van formuliervelden.', 'adif_processing' => 'ADIF-bestanden worden verwerkt...', 'adif_import_error' => 'ADIF-import mislukt.', 'adif_imported_status' => '{imported} QSO geÃ¯mporteerd uit {files} bestand(en).'],
    'pt' => ['studio' => 'QSL Studio Â· simples, guiado e eficiente', 'studio_help' => 'Tudo Ã© pensado para rapidez: importe os seus QSO, crie cartÃµes e exporte sem fricÃ§Ã£o.', 'design' => '1) Desenhe os seus fundos QSL', 'create' => '2) Crie cartÃµes QSL facilmente', 'manage' => '3) QSO importados', 'generated' => 'CartÃµes QSL gerados', 'filter' => 'Filtrar', 'reset' => 'Repor', 'page' => 'PÃ¡gina', 'previous' => 'Anterior', 'next' => 'Seguinte', 'nav_design' => '1 Â· Personalizar design', 'nav_design_help' => 'Adicione um fundo de imagem, uma cor sÃ³lida, um gradiente ou uma paleta predefinida.', 'nav_create' => '2 Â· Criar / importar', 'nav_create_help' => 'Crie um QSL manual ou importe os seus ficheiros ADIF com arrastar e largar.', 'err_select_bg' => 'Selecione uma imagem de fundo.', 'ok_bg_image' => 'Fundo de imagem guardado.', 'err_gradient_invalid' => 'Cores de gradiente invÃ¡lidas.', 'ok_bg_gradient' => 'Fundo em gradiente guardado.', 'err_solid_invalid' => 'Cor sÃ³lida invÃ¡lida.', 'ok_bg_solid' => 'Fundo de cor sÃ³lida guardado.', 'err_palette_invalid' => 'Paleta predefinida invÃ¡lida.', 'ok_bg_palette' => 'Paleta predefinida guardada.', 'ok_bg_default' => 'Fundo predefinido atualizado.', 'ok_bg_deleted' => 'Fundo eliminado.', 'err_no_adif' => 'Nenhum ficheiro ADIF recebido.', 'err_no_valid_adif' => 'NÃ£o foi possÃ­vel processar nenhum ficheiro ADIF vÃ¡lido.', 'ok_qso_imported' => 'QSO importados de ficheiros ADIF.', 'err_qso_none' => 'Nenhum novo QSO importado.', 'ok_qsl_generated' => 'CartÃµes QSL gerados.', 'err_qsl_none' => 'Nenhum QSL gerado. SeleÃ§Ã£o vazia ou cartÃµes jÃ¡ existentes.', 'ok_qsl_created' => 'QSL criado.', 'ok_qso_deleted' => 'QSO eliminado.', 'ok_qsl_deleted' => 'QSL eliminado.', 'err_unknown_action' => 'AÃ§Ã£o QSL desconhecida.', 'label_bg_image' => 'Fundo de imagem', 'label_gradient' => 'Gradiente de 2 cores', 'label_delete' => 'Eliminar', 'empty_qso' => 'Ainda nÃ£o hÃ¡ QSO importados.', 'empty_qso_filtered' => 'Nenhum QSO corresponde aos filtros ativos.', 'empty_qsl' => 'Ainda nÃ£o hÃ¡ QSL gerados.', 'empty_qsl_filtered' => 'Nenhum QSL corresponde Ã  sua pesquisa.', 'nav_manage' => '3 Â· Gerir e exportar', 'nav_manage_help' => 'Filtre os seus QSO, gere em lote e exporte cartÃµes frente/verso.', 'bulk_generate' => 'Gerar cartÃµes QSL selecionados', 'select_all' => 'Selecionar tudo', 'select_none' => 'Limpar seleÃ§Ã£o', 'qso_search_ph' => 'Filtrar por indicativo, data, modo...', 'qsl_search_ph' => 'Pesquisar um QSL (tÃ­tulo, indicativo, banda...)', 'all_bands' => 'Todas as bandas', 'all_modes' => 'Todos os modos', 'preview_dynamic' => 'PrÃ©-visualizaÃ§Ã£o dinÃ¢mica com base nos campos do formulÃ¡rio.', 'adif_processing' => 'A processar ficheiros ADIF...', 'adif_import_error' => 'Falha na importaÃ§Ã£o ADIF.', 'adif_imported_status' => '{imported} QSO importado(s) de {files} ficheiro(s).'],
    'it' => ['studio' => 'QSL Studio Â· semplice, guidato ed efficiente', 'studio_help' => 'Tutto Ã¨ pensato per la velocitÃ : importa i tuoi QSO, crea carte ed esportale senza attriti.', 'design' => '1) Progetta i tuoi sfondi QSL', 'create' => '2) Crea facilmente carte QSL', 'manage' => '3) QSO importati', 'generated' => 'Carte QSL generate', 'filter' => 'Filtra', 'reset' => 'Reimposta', 'page' => 'Pagina', 'previous' => 'Precedente', 'next' => 'Successiva', 'nav_design' => '1 Â· Personalizza design', 'nav_design_help' => 'Aggiungi uno sfondo immagine, un colore pieno, una sfumatura o una palette predefinita.', 'nav_create' => '2 Â· Crea / importa', 'nav_create_help' => 'Crea una QSL manuale o importa i tuoi file ADIF tramite drag and drop.', 'err_select_bg' => 'Seleziona unâ€™immagine di sfondo.', 'ok_bg_image' => 'Sfondo immagine salvato.', 'err_gradient_invalid' => 'Colori della sfumatura non validi.', 'ok_bg_gradient' => 'Sfondo sfumato salvato.', 'err_solid_invalid' => 'Colore pieno non valido.', 'ok_bg_solid' => 'Sfondo a colore pieno salvato.', 'err_palette_invalid' => 'Palette predefinita non valida.', 'ok_bg_palette' => 'Palette predefinita salvata.', 'ok_bg_default' => 'Sfondo predefinito aggiornato.', 'ok_bg_deleted' => 'Sfondo eliminato.', 'err_no_adif' => 'Nessun file ADIF ricevuto.', 'err_no_valid_adif' => 'Nessun file ADIF valido elaborabile.', 'ok_qso_imported' => 'QSO importati da file ADIF.', 'err_qso_none' => 'Nessun nuovo QSO importato.', 'ok_qsl_generated' => 'Carte QSL generate.', 'err_qsl_none' => 'Nessuna QSL generata. Selezione vuota o carte giÃ  esistenti.', 'ok_qsl_created' => 'QSL creata.', 'ok_qso_deleted' => 'QSO eliminato.', 'ok_qsl_deleted' => 'QSL eliminata.', 'err_unknown_action' => 'Azione QSL sconosciuta.', 'label_bg_image' => 'Sfondo immagine', 'label_gradient' => 'Sfumatura a 2 colori', 'label_delete' => 'Elimina', 'empty_qso' => 'Nessun QSO importato al momento.', 'empty_qso_filtered' => 'Nessun QSO corrisponde ai filtri attivi.', 'empty_qsl' => 'Nessuna QSL generata al momento.', 'empty_qsl_filtered' => 'Nessuna QSL corrisponde alla ricerca.', 'nav_manage' => '3 Â· Gestisci ed esporta', 'nav_manage_help' => 'Filtra i tuoi QSO, genera in batch ed esporta carte fronte/retro.', 'bulk_generate' => 'Genera le carte QSL selezionate', 'select_all' => 'Seleziona tutto', 'select_none' => 'Deseleziona tutto', 'qso_search_ph' => 'Filtra per nominativo, data, modo...', 'qsl_search_ph' => 'Cerca una QSL (titolo, nominativo, banda...)', 'all_bands' => 'Tutte le bande', 'all_modes' => 'Tutti i modi', 'preview_dynamic' => 'Anteprima dinamica in base ai campi del modulo.', 'adif_processing' => 'Elaborazione file ADIF...', 'adif_import_error' => 'Importazione ADIF non riuscita.', 'adif_imported_status' => '{imported} QSO importato/i da {files} file.'],
    'es' => ['studio' => 'QSL Studio Â· simple, guiado y eficiente', 'studio_help' => 'Todo estÃ¡ diseÃ±ado para la rapidez: importa tus QSO, crea tarjetas y expÃ³rtalas sin fricciÃ³n.', 'design' => '1) DiseÃ±a tus fondos QSL', 'create' => '2) Crea tarjetas QSL fÃ¡cilmente', 'manage' => '3) QSO importados', 'generated' => 'Tarjetas QSL generadas', 'filter' => 'Filtrar', 'reset' => 'Restablecer', 'page' => 'PÃ¡gina', 'previous' => 'Anterior', 'next' => 'Siguiente', 'nav_design' => '1 Â· Personalizar diseÃ±o', 'nav_design_help' => 'AÃ±ade un fondo de imagen, un color sÃ³lido, un degradado o una paleta predefinida.', 'nav_create' => '2 Â· Crear / importar', 'nav_create_help' => 'Crea una QSL manual o importa tus archivos ADIF mediante arrastrar y soltar.', 'err_select_bg' => 'Selecciona una imagen de fondo.', 'ok_bg_image' => 'Fondo de imagen guardado.', 'err_gradient_invalid' => 'Colores de degradado no vÃ¡lidos.', 'ok_bg_gradient' => 'Fondo degradado guardado.', 'err_solid_invalid' => 'Color sÃ³lido no vÃ¡lido.', 'ok_bg_solid' => 'Fondo de color sÃ³lido guardado.', 'err_palette_invalid' => 'Paleta predefinida no vÃ¡lida.', 'ok_bg_palette' => 'Paleta predefinida guardada.', 'ok_bg_default' => 'Fondo predeterminado actualizado.', 'ok_bg_deleted' => 'Fondo eliminado.', 'err_no_adif' => 'No se recibiÃ³ ningÃºn archivo ADIF.', 'err_no_valid_adif' => 'No se pudo procesar ningÃºn archivo ADIF vÃ¡lido.', 'ok_qso_imported' => 'QSO importados desde archivos ADIF.', 'err_qso_none' => 'No se importÃ³ ningÃºn QSO nuevo.', 'ok_qsl_generated' => 'Tarjetas QSL generadas.', 'err_qsl_none' => 'No se generÃ³ ninguna QSL. SelecciÃ³n vacÃ­a o tarjetas ya existentes.', 'ok_qsl_created' => 'QSL creada.', 'ok_qso_deleted' => 'QSO eliminado.', 'ok_qsl_deleted' => 'QSL eliminada.', 'err_unknown_action' => 'AcciÃ³n QSL desconocida.', 'label_bg_image' => 'Fondo de imagen', 'label_gradient' => 'Degradado de 2 colores', 'label_delete' => 'Eliminar', 'empty_qso' => 'AÃºn no hay QSO importados.', 'empty_qso_filtered' => 'NingÃºn QSO coincide con los filtros activos.', 'empty_qsl' => 'AÃºn no hay QSL generadas.', 'empty_qsl_filtered' => 'Ninguna QSL coincide con tu bÃºsqueda.', 'nav_manage' => '3 Â· Gestionar y exportar', 'nav_manage_help' => 'Filtra tus QSO, genera en lote y exporta tarjetas anverso/reverso.', 'bulk_generate' => 'Generar tarjetas QSL seleccionadas', 'select_all' => 'Seleccionar todo', 'select_none' => 'Deseleccionar todo', 'qso_search_ph' => 'Filtrar por indicativo, fecha, modo...', 'qsl_search_ph' => 'Buscar una QSL (tÃ­tulo, indicativo, banda...)', 'all_bands' => 'Todas las bandas', 'all_modes' => 'Todos los modos', 'preview_dynamic' => 'Vista previa dinÃ¡mica segÃºn los campos del formulario.', 'adif_processing' => 'Procesando archivos ADIF...', 'adif_import_error' => 'Error al importar ADIF.', 'adif_imported_status' => '{imported} QSO importado(s) desde {files} archivo(s).'],


    'ar' => array_replace($qslEnglishMessages, ['studio' => 'Ø§Ø³ØªÙˆØ¯ÙŠÙˆ QSL Â· Ø¨Ø³ÙŠØ·ØŒ Ù…ÙˆØ¬Ù‘Ù‡ ÙˆÙØ¹Ù‘Ø§Ù„', 'studio_help' => 'ÙƒÙ„ Ø´ÙŠØ¡ Ù…ØµÙ…Ù… Ù„Ù„Ø³Ø±Ø¹Ø©: Ø§Ø³ØªÙˆØ±Ø¯ Ø³Ø¬Ù„Ø§Øª QSOØŒ Ø£Ù†Ø´Ø¦ Ø§Ù„Ø¨Ø·Ø§Ù‚Ø§Øª ÙˆØµØ¯Ù‘Ø±Ù‡Ø§ Ø¨Ø³Ù„Ø§Ø³Ø©.', 'design' => '1) ØµÙ…Ù‘Ù… Ø®Ù„ÙÙŠØ§Øª QSL', 'create' => '2) Ø£Ù†Ø´Ø¦ Ø¨Ø·Ø§Ù‚Ø§Øª QSL Ø¨Ø³Ù‡ÙˆÙ„Ø©', 'manage' => '3) Ø³Ø¬Ù„Ø§Øª QSO Ø§Ù„Ù…Ø³ØªÙˆØ±Ø¯Ø©', 'generated' => 'Ø¨Ø·Ø§Ù‚Ø§Øª QSL Ø§Ù„Ù…ÙÙˆÙ„Ù‘Ø¯Ø©', 'filter' => 'ØªØµÙÙŠØ©', 'reset' => 'Ø¥Ø¹Ø§Ø¯Ø© ØªØ¹ÙŠÙŠÙ†', 'page' => 'Ø§Ù„ØµÙØ­Ø©', 'previous' => 'Ø§Ù„Ø³Ø§Ø¨Ù‚', 'next' => 'Ø§Ù„ØªØ§Ù„ÙŠ', 'nav_design' => '1 Â· ØªØ®ØµÙŠØµ Ø§Ù„ØªØµÙ…ÙŠÙ…', 'nav_create' => '2 Â· Ø¥Ù†Ø´Ø§Ø¡ / Ø§Ø³ØªÙŠØ±Ø§Ø¯', 'nav_manage' => '3 Â· Ø¥Ø¯Ø§Ø±Ø© ÙˆØªØµØ¯ÙŠØ±', 'bulk_generate' => 'Ø¥Ù†Ø´Ø§Ø¡ Ø¨Ø·Ø§Ù‚Ø§Øª QSL Ø§Ù„Ù…Ø­Ø¯Ø¯Ø©', 'select_all' => 'ØªØ­Ø¯ÙŠØ¯ Ø§Ù„ÙƒÙ„', 'select_none' => 'Ø¥Ù„ØºØ§Ø¡ Ø§Ù„ØªØ­Ø¯ÙŠØ¯', 'all_bands' => 'ÙƒÙ„ Ø§Ù„Ù†Ø·Ø§Ù‚Ø§Øª', 'all_modes' => 'ÙƒÙ„ Ø§Ù„Ø£Ù†Ù…Ø§Ø·', 'adif_processing' => 'Ø¬Ø§Ø±Ù Ù…Ø¹Ø§Ù„Ø¬Ø© Ù…Ù„ÙØ§Øª ADIF...', 'adif_import_error' => 'ÙØ´Ù„ Ø§Ø³ØªÙŠØ±Ø§Ø¯ ADIF.', 'nav_design_help' => 'Ø£Ø¶Ù Ø®Ù„ÙÙŠØ© ØµÙˆØ±Ø© Ø£Ùˆ Ù„ÙˆÙ†Ø§ Ø«Ø§Ø¨ØªØ§ Ø£Ùˆ ØªØ¯Ø±Ø¬Ø§ Ø£Ùˆ Ù„ÙˆØ­Ø© Ø¬Ø§Ù‡Ø²Ø© Ù„Ù„Ø§Ø³ØªØ®Ø¯Ø§Ù….', 'nav_create_help' => 'Ø£Ù†Ø´Ø¦ Ø¨Ø·Ø§Ù‚Ø© QSL ÙŠØ¯ÙˆÙŠØ§ Ø£Ùˆ Ø§Ø³ØªÙˆØ±Ø¯ Ù…Ù„ÙØ§Øª ADIF Ø¨Ø§Ù„Ø³Ø­Ø¨ ÙˆØ§Ù„Ø¥ÙÙ„Ø§Øª.', 'err_select_bg' => 'ÙŠØ±Ø¬Ù‰ Ø§Ø®ØªÙŠØ§Ø± ØµÙˆØ±Ø© Ø®Ù„ÙÙŠØ©.', 'ok_bg_image' => 'ØªÙ… Ø­ÙØ¸ Ø®Ù„ÙÙŠØ© Ø§Ù„ØµÙˆØ±Ø©.', 'err_gradient_invalid' => 'Ø£Ù„ÙˆØ§Ù† Ø§Ù„ØªØ¯Ø±Ø¬ ØºÙŠØ± ØµØ§Ù„Ø­Ø©.', 'ok_bg_gradient' => 'ØªÙ… Ø­ÙØ¸ Ø®Ù„ÙÙŠØ© Ø§Ù„ØªØ¯Ø±Ø¬.', 'err_solid_invalid' => 'Ø§Ù„Ù„ÙˆÙ† Ø§Ù„Ø«Ø§Ø¨Øª ØºÙŠØ± ØµØ§Ù„Ø­.', 'ok_bg_solid' => 'ØªÙ… Ø­ÙØ¸ Ø®Ù„ÙÙŠØ© Ø§Ù„Ù„ÙˆÙ† Ø§Ù„Ø«Ø§Ø¨Øª.', 'err_palette_invalid' => 'Ø§Ù„Ù„ÙˆØ­Ø© Ø§Ù„Ù…Ø­Ø¯Ø¯Ø© Ù…Ø³Ø¨Ù‚Ø§ ØºÙŠØ± ØµØ§Ù„Ø­Ø©.', 'ok_bg_palette' => 'ØªÙ… Ø­ÙØ¸ Ø§Ù„Ù„ÙˆØ­Ø© Ø§Ù„Ù…Ø­Ø¯Ø¯Ø© Ù…Ø³Ø¨Ù‚Ø§.', 'ok_bg_default' => 'ØªÙ… ØªØ­Ø¯ÙŠØ« Ø§Ù„Ø®Ù„ÙÙŠØ© Ø§Ù„Ø§ÙØªØ±Ø§Ø¶ÙŠØ©.', 'ok_bg_deleted' => 'ØªÙ… Ø­Ø°Ù Ø§Ù„Ø®Ù„ÙÙŠØ©.', 'err_no_adif' => 'Ù„Ù… ÙŠØªÙ… Ø§Ø³ØªÙ„Ø§Ù… Ø£ÙŠ Ù…Ù„Ù ADIF.', 'err_no_valid_adif' => 'ØªØ¹Ø°Ø±Øª Ù…Ø¹Ø§Ù„Ø¬Ø© Ø£ÙŠ Ù…Ù„Ù ADIF ØµØ§Ù„Ø­.', 'ok_qso_imported' => 'ØªÙ… Ø§Ø³ØªÙŠØ±Ø§Ø¯ QSO Ù…Ù† Ù…Ù„ÙØ§Øª ADIF.', 'err_qso_none' => 'Ù„Ù… ÙŠØªÙ… Ø§Ø³ØªÙŠØ±Ø§Ø¯ Ø£ÙŠ QSO Ø¬Ø¯ÙŠØ¯.', 'ok_qsl_generated' => 'ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ Ø¨Ø·Ø§Ù‚Ø§Øª QSL.', 'err_qsl_none' => 'Ù„Ù… ÙŠØªÙ… Ø¥Ù†Ø´Ø§Ø¡ Ø£ÙŠ QSL. Ø§Ù„ØªØ­Ø¯ÙŠØ¯ ÙØ§Ø±Øº Ø£Ùˆ Ø§Ù„Ø¨Ø·Ø§Ù‚Ø§Øª Ù…ÙˆØ¬ÙˆØ¯Ø© Ù…Ø³Ø¨Ù‚Ø§.', 'ok_qsl_created' => 'ØªÙ… Ø¥Ù†Ø´Ø§Ø¡ QSL.', 'ok_qso_deleted' => 'ØªÙ… Ø­Ø°Ù QSO.', 'ok_qsl_deleted' => 'ØªÙ… Ø­Ø°Ù QSL.', 'err_unknown_action' => 'Ø¥Ø¬Ø±Ø§Ø¡ QSL ØºÙŠØ± Ù…Ø¹Ø±ÙˆÙ.', 'label_bg_image' => 'Ø®Ù„ÙÙŠØ© ØµÙˆØ±Ø©', 'label_gradient' => 'ØªØ¯Ø±Ø¬ Ø¨Ù„ÙˆÙ†ÙŠÙ†', 'label_delete' => 'Ø­Ø°Ù', 'empty_qso' => 'Ù„Ø§ ØªÙˆØ¬Ø¯ QSO Ù…Ø³ØªÙˆØ±Ø¯Ø© Ø­ØªÙ‰ Ø§Ù„Ø¢Ù†.', 'empty_qso_filtered' => 'Ù„Ø§ ØªÙˆØ¬Ø¯ QSO ØªØ·Ø§Ø¨Ù‚ Ø§Ù„Ù…Ø±Ø´Ø­Ø§Øª Ø§Ù„Ù†Ø´Ø·Ø©.', 'empty_qsl' => 'Ù„Ø§ ØªÙˆØ¬Ø¯ QSL Ù…Ù†Ø´Ø£Ø© Ø­ØªÙ‰ Ø§Ù„Ø¢Ù†.', 'empty_qsl_filtered' => 'Ù„Ø§ ØªÙˆØ¬Ø¯ QSL ØªØ·Ø§Ø¨Ù‚ Ø§Ù„Ø¨Ø­Ø«.', 'nav_manage_help' => 'ØµÙ QSO Ø§Ù„Ø®Ø§ØµØ© Ø¨Ùƒ ÙˆØ£Ù†Ø´Ø¦Ù‡Ø§ Ø¯ÙØ¹Ø© ÙˆØ§Ø­Ø¯Ø© ÙˆØµØ¯Ù‘Ø± Ø§Ù„Ø¨Ø·Ø§Ù‚Ø§Øª Ø§Ù„Ø£Ù…Ø§Ù…ÙŠØ© ÙˆØ§Ù„Ø®Ù„ÙÙŠØ©.', 'qso_search_ph' => 'ØµÙ Ø­Ø³Ø¨ Ø§Ù„Ù†Ø¯Ø§Ø¡ Ø£Ùˆ Ø§Ù„ØªØ§Ø±ÙŠØ® Ø£Ùˆ Ø§Ù„Ù†Ù…Ø·...', 'qsl_search_ph' => 'Ø§Ø¨Ø­Ø« Ø¹Ù† QSL (Ø§Ù„Ø¹Ù†ÙˆØ§Ù†ØŒ Ø§Ù„Ù†Ø¯Ø§Ø¡ØŒ Ø§Ù„Ù†Ø·Ø§Ù‚...)', 'preview_dynamic' => 'Ù…Ø¹Ø§ÙŠÙ†Ø© Ø¯ÙŠÙ†Ø§Ù…ÙŠÙƒÙŠØ© Ø­Ø³Ø¨ Ø­Ù‚ÙˆÙ„ Ø§Ù„Ù†Ù…ÙˆØ°Ø¬.', 'adif_imported_status' => 'ØªÙ… Ø§Ø³ØªÙŠØ±Ø§Ø¯ {imported} QSO Ù…Ù† {files} Ù…Ù„Ù.']),
    'bn' => array_replace($qslEnglishMessages, ['studio' => 'QSL à¦¸à§à¦Ÿà§à¦¡à¦¿à¦“ Â· à¦¸à¦¹à¦œ, à¦¨à¦¿à¦°à§à¦¦à§‡à¦¶à¦¿à¦¤, à¦•à¦¾à¦°à§à¦¯à¦•à¦°', 'studio_help' => 'à¦¸à¦¬à¦•à¦¿à¦›à§ à¦¦à§à¦°à§à¦¤à¦¤à¦¾à¦° à¦œà¦¨à§à¦¯ à¦¤à§ˆà¦°à¦¿: à¦†à¦ªà¦¨à¦¾à¦° QSO à¦‡à¦®à¦ªà§‹à¦°à§à¦Ÿ à¦•à¦°à§à¦¨, à¦•à¦¾à¦°à§à¦¡ à¦¤à§ˆà¦°à¦¿ à¦•à¦°à§à¦¨ à¦à¦¬à¦‚ à¦¸à¦¹à¦œà§‡ à¦à¦•à§à¦¸à¦ªà§‹à¦°à§à¦Ÿ à¦•à¦°à§à¦¨à¥¤', 'design' => '1) à¦†à¦ªà¦¨à¦¾à¦° QSL à¦¬à§à¦¯à¦¾à¦•à¦—à§à¦°à¦¾à¦‰à¦¨à§à¦¡ à¦¡à¦¿à¦œà¦¾à¦‡à¦¨ à¦•à¦°à§à¦¨', 'create' => '2) à¦¸à¦¹à¦œà§‡ QSL à¦•à¦¾à¦°à§à¦¡ à¦¤à§ˆà¦°à¦¿ à¦•à¦°à§à¦¨', 'manage' => '3) à¦‡à¦®à¦ªà§‹à¦°à§à¦Ÿ à¦•à¦°à¦¾ QSO', 'generated' => 'à¦¤à§ˆà¦°à¦¿ à¦¹à¦“à¦¯à¦¼à¦¾ QSL à¦•à¦¾à¦°à§à¦¡', 'filter' => 'à¦«à¦¿à¦²à§à¦Ÿà¦¾à¦°', 'reset' => 'à¦°à¦¿à¦¸à§‡à¦Ÿ', 'page' => 'à¦ªà§ƒà¦·à§à¦ à¦¾', 'previous' => 'à¦ªà§‚à¦°à§à¦¬à¦¬à¦°à§à¦¤à§€', 'next' => 'à¦ªà¦°à¦¬à¦°à§à¦¤à§€', 'nav_design' => '1 Â· à¦¡à¦¿à¦œà¦¾à¦‡à¦¨ à¦•à¦¾à¦¸à§à¦Ÿà¦®à¦¾à¦‡à¦œ', 'nav_create' => '2 Â· à¦¤à§ˆà¦°à¦¿ / à¦‡à¦®à¦ªà§‹à¦°à§à¦Ÿ', 'nav_manage' => '3 Â· à¦ªà¦°à¦¿à¦šà¦¾à¦²à¦¨à¦¾ à¦“ à¦à¦•à§à¦¸à¦ªà§‹à¦°à§à¦Ÿ', 'bulk_generate' => 'à¦¨à¦¿à¦°à§à¦¬à¦¾à¦šà¦¿à¦¤ QSL à¦•à¦¾à¦°à§à¦¡ à¦¤à§ˆà¦°à¦¿ à¦•à¦°à§à¦¨', 'select_all' => 'à¦¸à¦¬ à¦¨à¦¿à¦°à§à¦¬à¦¾à¦šà¦¨', 'select_none' => 'à¦¨à¦¿à¦°à§à¦¬à¦¾à¦šà¦¨ à¦¬à¦¾à¦¤à¦¿à¦²', 'all_bands' => 'à¦¸à¦¬ à¦¬à§à¦¯à¦¾à¦¨à§à¦¡', 'all_modes' => 'à¦¸à¦¬ à¦®à§‹à¦¡', 'adif_processing' => 'ADIF à¦«à¦¾à¦‡à¦² à¦ªà§à¦°à¦•à§à¦°à¦¿à¦¯à¦¼à¦¾à¦•à¦°à¦£ à¦šà¦²à¦›à§‡...', 'adif_import_error' => 'ADIF à¦‡à¦®à¦ªà§‹à¦°à§à¦Ÿ à¦¬à§à¦¯à¦°à§à¦¥ à¦¹à¦¯à¦¼à§‡à¦›à§‡à¥¤', 'nav_design_help' => 'à¦›à¦¬à¦¿à¦° à¦¬à§à¦¯à¦¾à¦•à¦—à§à¦°à¦¾à¦‰à¦¨à§à¦¡, à¦à¦•à¦°à¦™à¦¾ à¦°à¦‚, à¦—à§à¦°à§‡à¦¡à¦¿à§Ÿà§‡à¦¨à§à¦Ÿ à¦¬à¦¾ à¦ªà§à¦°à¦¸à§à¦¤à§à¦¤ à¦ªà§à¦¯à¦¾à¦²à§‡à¦Ÿ à¦¯à§‹à¦— à¦•à¦°à§à¦¨à¥¤', 'nav_create_help' => 'à¦®à§à¦¯à¦¾à¦¨à§à§Ÿà¦¾à¦² QSL à¦¤à§ˆà¦°à¦¿ à¦•à¦°à§à¦¨ à¦…à¦¥à¦¬à¦¾ à¦¡à§à¦°à§à¦¯à¦¾à¦—-à¦…à§à¦¯à¦¾à¦¨à§à¦¡-à¦¡à§à¦°à¦ªà§‡ ADIF à¦«à¦¾à¦‡à¦² à¦‡à¦®à¦ªà§‹à¦°à§à¦Ÿ à¦•à¦°à§à¦¨à¥¤', 'err_select_bg' => 'à¦…à¦¨à§à¦—à§à¦°à¦¹ à¦•à¦°à§‡ à¦à¦•à¦Ÿà¦¿ à¦¬à§à¦¯à¦¾à¦•à¦—à§à¦°à¦¾à¦‰à¦¨à§à¦¡ à¦›à¦¬à¦¿ à¦¨à¦¿à¦°à§à¦¬à¦¾à¦šà¦¨ à¦•à¦°à§à¦¨à¥¤', 'ok_bg_image' => 'à¦›à¦¬à¦¿à¦° à¦¬à§à¦¯à¦¾à¦•à¦—à§à¦°à¦¾à¦‰à¦¨à§à¦¡ à¦¸à¦‚à¦°à¦•à§à¦·à¦¿à¦¤ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'err_gradient_invalid' => 'à¦—à§à¦°à§‡à¦¡à¦¿à§Ÿà§‡à¦¨à§à¦Ÿà§‡à¦° à¦°à¦‚ à¦¬à§ˆà¦§ à¦¨à§Ÿà¥¤', 'ok_bg_gradient' => 'à¦—à§à¦°à§‡à¦¡à¦¿à§Ÿà§‡à¦¨à§à¦Ÿ à¦¬à§à¦¯à¦¾à¦•à¦—à§à¦°à¦¾à¦‰à¦¨à§à¦¡ à¦¸à¦‚à¦°à¦•à§à¦·à¦¿à¦¤ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'err_solid_invalid' => 'à¦à¦•à¦°à¦™à¦¾ à¦°à¦‚ à¦¬à§ˆà¦§ à¦¨à§Ÿà¥¤', 'ok_bg_solid' => 'à¦à¦•à¦°à¦™à¦¾ à¦¬à§à¦¯à¦¾à¦•à¦—à§à¦°à¦¾à¦‰à¦¨à§à¦¡ à¦¸à¦‚à¦°à¦•à§à¦·à¦¿à¦¤ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'err_palette_invalid' => 'à¦ªà§‚à¦°à§à¦¬à¦¨à¦¿à¦°à§à¦§à¦¾à¦°à¦¿à¦¤ à¦ªà§à¦¯à¦¾à¦²à§‡à¦Ÿ à¦¬à§ˆà¦§ à¦¨à§Ÿà¥¤', 'ok_bg_palette' => 'à¦ªà§‚à¦°à§à¦¬à¦¨à¦¿à¦°à§à¦§à¦¾à¦°à¦¿à¦¤ à¦ªà§à¦¯à¦¾à¦²à§‡à¦Ÿ à¦¸à¦‚à¦°à¦•à§à¦·à¦¿à¦¤ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'ok_bg_default' => 'à¦¡à¦¿à¦«à¦²à§à¦Ÿ à¦¬à§à¦¯à¦¾à¦•à¦—à§à¦°à¦¾à¦‰à¦¨à§à¦¡ à¦†à¦ªà¦¡à§‡à¦Ÿ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'ok_bg_deleted' => 'à¦¬à§à¦¯à¦¾à¦•à¦—à§à¦°à¦¾à¦‰à¦¨à§à¦¡ à¦®à§à¦›à§‡ à¦«à§‡à¦²à¦¾ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'err_no_adif' => 'à¦•à§‹à¦¨à§‹ ADIF à¦«à¦¾à¦‡à¦² à¦ªà¦¾à¦“à§Ÿà¦¾ à¦¯à¦¾à§Ÿà¦¨à¦¿à¥¤', 'err_no_valid_adif' => 'à¦•à§‹à¦¨à§‹ à¦¬à§ˆà¦§ ADIF à¦«à¦¾à¦‡à¦² à¦ªà§à¦°à¦•à§à¦°à¦¿à§Ÿà¦¾ à¦•à¦°à¦¾ à¦¯à¦¾à§Ÿà¦¨à¦¿à¥¤', 'ok_qso_imported' => 'ADIF à¦«à¦¾à¦‡à¦² à¦¥à§‡à¦•à§‡ QSO à¦‡à¦®à¦ªà§‹à¦°à§à¦Ÿ à¦•à¦°à¦¾ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'err_qso_none' => 'à¦¨à¦¤à§à¦¨ à¦•à§‹à¦¨à§‹ QSO à¦‡à¦®à¦ªà§‹à¦°à§à¦Ÿ à¦¹à§Ÿà¦¨à¦¿à¥¤', 'ok_qsl_generated' => 'QSL à¦•à¦¾à¦°à§à¦¡ à¦¤à§ˆà¦°à¦¿ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'err_qsl_none' => 'à¦•à§‹à¦¨à§‹ QSL à¦¤à§ˆà¦°à¦¿ à¦¹à§Ÿà¦¨à¦¿à¥¤ à¦¨à¦¿à¦°à§à¦¬à¦¾à¦šà¦¨ à¦–à¦¾à¦²à¦¿ à¦¬à¦¾ à¦•à¦¾à¦°à§à¦¡ à¦†à¦—à§‡ à¦¥à§‡à¦•à§‡à¦‡ à¦†à¦›à§‡à¥¤', 'ok_qsl_created' => 'QSL à¦¤à§ˆà¦°à¦¿ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'ok_qso_deleted' => 'QSO à¦®à§à¦›à§‡ à¦«à§‡à¦²à¦¾ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'ok_qsl_deleted' => 'QSL à¦®à§à¦›à§‡ à¦«à§‡à¦²à¦¾ à¦¹à§Ÿà§‡à¦›à§‡à¥¤', 'err_unknown_action' => 'à¦…à¦œà¦¾à¦¨à¦¾ QSL à¦•à§à¦°à¦¿à§Ÿà¦¾à¥¤', 'label_bg_image' => 'à¦›à¦¬à¦¿à¦° à¦¬à§à¦¯à¦¾à¦•à¦—à§à¦°à¦¾à¦‰à¦¨à§à¦¡', 'label_gradient' => 'à§¨ à¦°à¦™à§‡à¦° à¦—à§à¦°à§‡à¦¡à¦¿à§Ÿà§‡à¦¨à§à¦Ÿ', 'label_delete' => 'à¦®à§à¦›à§à¦¨', 'empty_qso' => 'à¦à¦–à¦¨à¦“ à¦•à§‹à¦¨à§‹ QSO à¦‡à¦®à¦ªà§‹à¦°à§à¦Ÿ à¦•à¦°à¦¾ à¦¹à§Ÿà¦¨à¦¿à¥¤', 'empty_qso_filtered' => 'à¦¸à¦•à§à¦°à¦¿à§Ÿ à¦«à¦¿à¦²à§à¦Ÿà¦¾à¦°à§‡à¦° à¦¸à¦¾à¦¥à§‡ à¦•à§‹à¦¨à§‹ QSO à¦®à§‡à¦²à§‡à¦¨à¦¿à¥¤', 'empty_qsl' => 'à¦à¦–à¦¨à¦“ à¦•à§‹à¦¨à§‹ QSL à¦¤à§ˆà¦°à¦¿ à¦¹à§Ÿà¦¨à¦¿à¥¤', 'empty_qsl_filtered' => 'à¦†à¦ªà¦¨à¦¾à¦° à¦…à¦¨à§à¦¸à¦¨à§à¦§à¦¾à¦¨à§‡à¦° à¦¸à¦¾à¦¥à§‡ à¦•à§‹à¦¨à§‹ QSL à¦®à§‡à¦²à§‡à¦¨à¦¿à¥¤', 'nav_manage_help' => 'à¦†à¦ªà¦¨à¦¾à¦° QSO à¦«à¦¿à¦²à§à¦Ÿà¦¾à¦° à¦•à¦°à§à¦¨, à¦¬à§à¦¯à¦¾à¦šà§‡ à¦¤à§ˆà¦°à¦¿ à¦•à¦°à§à¦¨ à¦à¦¬à¦‚ à¦¸à¦¾à¦®à¦¨à§‡à¦°/à¦ªà§‡à¦›à¦¨à§‡à¦° à¦•à¦¾à¦°à§à¦¡ à¦à¦•à§à¦¸à¦ªà§‹à¦°à§à¦Ÿ à¦•à¦°à§à¦¨à¥¤', 'qso_search_ph' => 'à¦•à¦²à¦¸à¦¾à¦‡à¦¨, à¦¤à¦¾à¦°à¦¿à¦–, à¦®à§‹à¦¡ à¦¦à¦¿à§Ÿà§‡ à¦«à¦¿à¦²à§à¦Ÿà¦¾à¦° à¦•à¦°à§à¦¨...', 'qsl_search_ph' => 'QSL à¦–à§à¦à¦œà§à¦¨ (à¦¶à¦¿à¦°à§‹à¦¨à¦¾à¦®, à¦•à¦², à¦¬à§à¦¯à¦¾à¦¨à§à¦¡...)', 'preview_dynamic' => 'à¦«à¦°à§à¦®à§‡à¦° à¦•à§à¦·à§‡à¦¤à§à¦° à¦…à¦¨à§à¦¯à¦¾à§Ÿà§€ à¦—à¦¤à¦¿à¦¶à§€à¦² à¦ªà§à¦°à¦¿à¦­à¦¿à¦‰à¥¤', 'adif_imported_status' => '{files}à¦Ÿà¦¿ à¦«à¦¾à¦‡à¦² à¦¥à§‡à¦•à§‡ {imported}à¦Ÿà¦¿ QSO à¦‡à¦®à¦ªà§‹à¦°à§à¦Ÿ à¦¹à§Ÿà§‡à¦›à§‡à¥¤']),
    'hi' => array_replace($qslEnglishMessages, ['studio' => 'QSL à¤¸à¥à¤Ÿà¥‚à¤¡à¤¿à¤¯à¥‹ Â· à¤¸à¤°à¤², à¤¨à¤¿à¤°à¥à¤¦à¥‡à¤¶à¤¿à¤¤, à¤ªà¥à¤°à¤­à¤¾à¤µà¥€', 'studio_help' => 'à¤¸à¤¬ à¤•à¥à¤› à¤¤à¥‡à¤œà¤¼à¥€ à¤•à¥‡ à¤²à¤¿à¤ à¤¬à¤¨à¤¾à¤¯à¤¾ à¤—à¤¯à¤¾ à¤¹à¥ˆ: à¤…à¤ªà¤¨à¥‡ QSO à¤†à¤¯à¤¾à¤¤ à¤•à¤°à¥‡à¤‚, à¤•à¤¾à¤°à¥à¤¡ à¤¬à¤¨à¤¾à¤à¤‚ à¤”à¤° à¤†à¤¸à¤¾à¤¨à¥€ à¤¸à¥‡ à¤¨à¤¿à¤°à¥à¤¯à¤¾à¤¤ à¤•à¤°à¥‡à¤‚à¥¤', 'design' => '1) à¤…à¤ªà¤¨à¥‡ QSL à¤¬à¥ˆà¤•à¤—à¥à¤°à¤¾à¤‰à¤‚à¤¡ à¤¡à¤¿à¤œà¤¼à¤¾à¤‡à¤¨ à¤•à¤°à¥‡à¤‚', 'create' => '2) à¤†à¤¸à¤¾à¤¨à¥€ à¤¸à¥‡ QSL à¤•à¤¾à¤°à¥à¤¡ à¤¬à¤¨à¤¾à¤à¤‚', 'manage' => '3) à¤†à¤¯à¤¾à¤¤à¤¿à¤¤ QSO', 'generated' => 'à¤œà¤¨à¤°à¥‡à¤Ÿ à¤•à¤¿à¤ à¤—à¤ QSL à¤•à¤¾à¤°à¥à¤¡', 'filter' => 'à¤«à¤¼à¤¿à¤²à¥à¤Ÿà¤°', 'reset' => 'à¤°à¥€à¤¸à¥‡à¤Ÿ', 'page' => 'à¤ªà¥ƒà¤·à¥à¤ ', 'previous' => 'à¤ªà¤¿à¤›à¤²à¤¾', 'next' => 'à¤…à¤—à¤²à¤¾', 'nav_design' => '1 Â· à¤¡à¤¿à¤œà¤¼à¤¾à¤‡à¤¨ à¤…à¤¨à¥à¤•à¥‚à¤²à¤¿à¤¤ à¤•à¤°à¥‡à¤‚', 'nav_create' => '2 Â· à¤¬à¤¨à¤¾à¤à¤ / à¤†à¤¯à¤¾à¤¤ à¤•à¤°à¥‡à¤‚', 'nav_manage' => '3 Â· à¤ªà¥à¤°à¤¬à¤‚à¤§à¤¿à¤¤ à¤•à¤°à¥‡à¤‚ à¤”à¤° à¤¨à¤¿à¤°à¥à¤¯à¤¾à¤¤ à¤•à¤°à¥‡à¤‚', 'bulk_generate' => 'à¤šà¤¯à¤¨à¤¿à¤¤ QSL à¤•à¤¾à¤°à¥à¤¡ à¤œà¤¨à¤°à¥‡à¤Ÿ à¤•à¤°à¥‡à¤‚', 'select_all' => 'à¤¸à¤­à¥€ à¤šà¥à¤¨à¥‡à¤‚', 'select_none' => 'à¤šà¤¯à¤¨ à¤¹à¤Ÿà¤¾à¤à¤', 'all_bands' => 'à¤¸à¤­à¥€ à¤¬à¥ˆà¤‚à¤¡', 'all_modes' => 'à¤¸à¤­à¥€ à¤®à¥‹à¤¡', 'adif_processing' => 'ADIF à¤«à¤¼à¤¾à¤‡à¤²à¥‡à¤‚ à¤ªà¥à¤°à¥‹à¤¸à¥‡à¤¸ à¤•à¥€ à¤œà¤¾ à¤°à¤¹à¥€ à¤¹à¥ˆà¤‚...', 'adif_import_error' => 'ADIF à¤†à¤¯à¤¾à¤¤ à¤µà¤¿à¤«à¤² à¤¹à¥à¤†à¥¤', 'nav_design_help' => 'à¤šà¤¿à¤¤à¥à¤° à¤ªà¥ƒà¤·à¥à¤ à¤­à¥‚à¤®à¤¿, à¤ à¥‹à¤¸ à¤°à¤‚à¤—, à¤—à¥à¤°à¥‡à¤¡à¤¿à¤à¤‚à¤Ÿ à¤¯à¤¾ à¤¤à¥ˆà¤¯à¤¾à¤° à¤ªà¥ˆà¤²à¥‡à¤Ÿ à¤œà¥‹à¤¡à¤¼à¥‡à¤‚à¥¤', 'nav_create_help' => 'à¤®à¥ˆà¤¨à¥à¤…à¤² QSL à¤¬à¤¨à¤¾à¤à¤‚ à¤¯à¤¾ à¤…à¤ªà¤¨à¥€ ADIF à¤«à¤¾à¤‡à¤²à¥‡à¤‚ à¤¡à¥à¤°à¥ˆà¤—-à¤à¤‚à¤¡-à¤¡à¥à¤°à¥‰à¤ª à¤¸à¥‡ à¤†à¤¯à¤¾à¤¤ à¤•à¤°à¥‡à¤‚à¥¤', 'err_select_bg' => 'à¤•à¥ƒà¤ªà¤¯à¤¾ à¤à¤• à¤ªà¥ƒà¤·à¥à¤ à¤­à¥‚à¤®à¤¿ à¤šà¤¿à¤¤à¥à¤° à¤šà¥à¤¨à¥‡à¤‚à¥¤', 'ok_bg_image' => 'à¤šà¤¿à¤¤à¥à¤° à¤ªà¥ƒà¤·à¥à¤ à¤­à¥‚à¤®à¤¿ à¤¸à¤¹à¥‡à¤œà¥€ à¤—à¤ˆà¥¤', 'err_gradient_invalid' => 'à¤—à¥à¤°à¥‡à¤¡à¤¿à¤à¤‚à¤Ÿ à¤°à¤‚à¤— à¤…à¤®à¤¾à¤¨à¥à¤¯ à¤¹à¥ˆà¤‚à¥¤', 'ok_bg_gradient' => 'à¤—à¥à¤°à¥‡à¤¡à¤¿à¤à¤‚à¤Ÿ à¤ªà¥ƒà¤·à¥à¤ à¤­à¥‚à¤®à¤¿ à¤¸à¤¹à¥‡à¤œà¥€ à¤—à¤ˆà¥¤', 'err_solid_invalid' => 'à¤ à¥‹à¤¸ à¤°à¤‚à¤— à¤…à¤®à¤¾à¤¨à¥à¤¯ à¤¹à¥ˆà¥¤', 'ok_bg_solid' => 'à¤ à¥‹à¤¸ à¤°à¤‚à¤— à¤ªà¥ƒà¤·à¥à¤ à¤­à¥‚à¤®à¤¿ à¤¸à¤¹à¥‡à¤œà¥€ à¤—à¤ˆà¥¤', 'err_palette_invalid' => 'à¤ªà¥‚à¤°à¥à¤µà¤¨à¤¿à¤°à¥à¤§à¤¾à¤°à¤¿à¤¤ à¤ªà¥ˆà¤²à¥‡à¤Ÿ à¤…à¤®à¤¾à¤¨à¥à¤¯ à¤¹à¥ˆà¥¤', 'ok_bg_palette' => 'à¤ªà¥‚à¤°à¥à¤µà¤¨à¤¿à¤°à¥à¤§à¤¾à¤°à¤¿à¤¤ à¤ªà¥ˆà¤²à¥‡à¤Ÿ à¤¸à¤¹à¥‡à¤œà¤¾ à¤—à¤¯à¤¾à¥¤', 'ok_bg_default' => 'à¤¡à¤¿à¤«à¤¼à¥‰à¤²à¥à¤Ÿ à¤ªà¥ƒà¤·à¥à¤ à¤­à¥‚à¤®à¤¿ à¤…à¤ªà¤¡à¥‡à¤Ÿ à¤¹à¥à¤ˆà¥¤', 'ok_bg_deleted' => 'à¤ªà¥ƒà¤·à¥à¤ à¤­à¥‚à¤®à¤¿ à¤¹à¤Ÿà¤¾à¤ˆ à¤—à¤ˆà¥¤', 'err_no_adif' => 'à¤•à¥‹à¤ˆ ADIF à¤«à¤¾à¤‡à¤² à¤ªà¥à¤°à¤¾à¤ªà¥à¤¤ à¤¨à¤¹à¥€à¤‚ à¤¹à¥à¤ˆà¥¤', 'err_no_valid_adif' => 'à¤•à¥‹à¤ˆ à¤µà¥ˆà¤§ ADIF à¤«à¤¾à¤‡à¤² à¤¸à¤‚à¤¸à¤¾à¤§à¤¿à¤¤ à¤¨à¤¹à¥€à¤‚ à¤•à¥€ à¤œà¤¾ à¤¸à¤•à¥€à¥¤', 'ok_qso_imported' => 'ADIF à¤«à¤¾à¤‡à¤²à¥‹à¤‚ à¤¸à¥‡ QSO à¤†à¤¯à¤¾à¤¤ à¤•à¤¿à¤ à¤—à¤à¥¤', 'err_qso_none' => 'à¤•à¥‹à¤ˆ à¤¨à¤¯à¤¾ QSO à¤†à¤¯à¤¾à¤¤ à¤¨à¤¹à¥€à¤‚ à¤¹à¥à¤†à¥¤', 'ok_qsl_generated' => 'QSL à¤•à¤¾à¤°à¥à¤¡ à¤œà¤¨à¤°à¥‡à¤Ÿ à¤¹à¥à¤à¥¤', 'err_qsl_none' => 'à¤•à¥‹à¤ˆ QSL à¤œà¤¨à¤°à¥‡à¤Ÿ à¤¨à¤¹à¥€à¤‚ à¤¹à¥à¤ˆà¥¤ à¤šà¤¯à¤¨ à¤–à¤¾à¤²à¥€ à¤¹à¥ˆ à¤¯à¤¾ à¤•à¤¾à¤°à¥à¤¡ à¤ªà¤¹à¤²à¥‡ à¤¸à¥‡ à¤®à¥Œà¤œà¥‚à¤¦ à¤¹à¥ˆà¤‚à¥¤', 'ok_qsl_created' => 'QSL à¤¬à¤¨à¤¾à¤ˆ à¤—à¤ˆà¥¤', 'ok_qso_deleted' => 'QSO à¤¹à¤Ÿà¤¾à¤¯à¤¾ à¤—à¤¯à¤¾à¥¤', 'ok_qsl_deleted' => 'QSL à¤¹à¤Ÿà¤¾à¤ˆ à¤—à¤ˆà¥¤', 'err_unknown_action' => 'à¤…à¤œà¥à¤žà¤¾à¤¤ QSL à¤•à¥à¤°à¤¿à¤¯à¤¾à¥¤', 'label_bg_image' => 'à¤šà¤¿à¤¤à¥à¤° à¤ªà¥ƒà¤·à¥à¤ à¤­à¥‚à¤®à¤¿', 'label_gradient' => '2-à¤°à¤‚à¤— à¤—à¥à¤°à¥‡à¤¡à¤¿à¤à¤‚à¤Ÿ', 'label_delete' => 'à¤¹à¤Ÿà¤¾à¤à¤‚', 'empty_qso' => 'à¤…à¤­à¥€ à¤•à¥‹à¤ˆ QSO à¤†à¤¯à¤¾à¤¤à¤¿à¤¤ à¤¨à¤¹à¥€à¤‚ à¤¹à¥ˆà¥¤', 'empty_qso_filtered' => 'à¤¸à¤•à¥à¤°à¤¿à¤¯ à¤«à¤¼à¤¿à¤²à¥à¤Ÿà¤° à¤¸à¥‡ à¤•à¥‹à¤ˆ QSO à¤®à¥‡à¤² à¤¨à¤¹à¥€à¤‚ à¤–à¤¾à¤¤à¤¾à¥¤', 'empty_qsl' => 'à¤…à¤­à¥€ à¤•à¥‹à¤ˆ QSL à¤œà¤¨à¤°à¥‡à¤Ÿ à¤¨à¤¹à¥€à¤‚ à¤¹à¥à¤ˆà¥¤', 'empty_qsl_filtered' => 'à¤†à¤ªà¤•à¥€ à¤–à¥‹à¤œ à¤¸à¥‡ à¤•à¥‹à¤ˆ QSL à¤®à¥‡à¤² à¤¨à¤¹à¥€à¤‚ à¤–à¤¾à¤¤à¥€à¥¤', 'nav_manage_help' => 'à¤…à¤ªà¤¨à¥‡ QSO à¤«à¤¼à¤¿à¤²à¥à¤Ÿà¤° à¤•à¤°à¥‡à¤‚, à¤¬à¥ˆà¤š à¤®à¥‡à¤‚ à¤œà¤¨à¤°à¥‡à¤Ÿ à¤•à¤°à¥‡à¤‚ à¤”à¤° à¤¸à¤¾à¤®à¤¨à¥‡/à¤ªà¥€à¤›à¥‡ à¤•à¥‡ à¤•à¤¾à¤°à¥à¤¡ à¤¨à¤¿à¤°à¥à¤¯à¤¾à¤¤ à¤•à¤°à¥‡à¤‚à¥¤', 'qso_search_ph' => 'à¤•à¥‰à¤²à¤¸à¤¾à¤‡à¤¨, à¤¤à¤¾à¤°à¥€à¤–, à¤®à¥‹à¤¡ à¤¸à¥‡ à¤«à¤¼à¤¿à¤²à¥à¤Ÿà¤° à¤•à¤°à¥‡à¤‚...', 'qsl_search_ph' => 'QSL à¤–à¥‹à¤œà¥‡à¤‚ (à¤¶à¥€à¤°à¥à¤·à¤•, à¤•à¥‰à¤², à¤¬à¥ˆà¤‚à¤¡...)', 'preview_dynamic' => 'à¤«à¥‰à¤°à¥à¤® à¤«à¤¼à¥€à¤²à¥à¤¡ à¤•à¥‡ à¤…à¤¨à¥à¤¸à¤¾à¤° à¤—à¤¤à¤¿à¤¶à¥€à¤² à¤ªà¥‚à¤°à¥à¤µà¤¾à¤µà¤²à¥‹à¤•à¤¨à¥¤', 'adif_imported_status' => '{files} à¤«à¤¾à¤‡à¤²à¥‹à¤‚ à¤¸à¥‡ {imported} QSO à¤†à¤¯à¤¾à¤¤ à¤¹à¥à¤à¥¤']),
    'id' => array_replace($qslEnglishMessages, ['studio' => 'Studio QSL Â· sederhana, terpandu, efisien', 'studio_help' => 'Semuanya dirancang untuk kecepatan: impor QSO Anda, buat kartu, dan ekspor tanpa hambatan.', 'design' => '1) Rancang latar belakang QSL Anda', 'create' => '2) Buat kartu QSL dengan mudah', 'manage' => '3) QSO yang diimpor', 'generated' => 'Kartu QSL yang dihasilkan', 'filter' => 'Saring', 'reset' => 'Atur ulang', 'page' => 'Halaman', 'previous' => 'Sebelumnya', 'next' => 'Berikutnya', 'nav_design' => '1 Â· Sesuaikan desain', 'nav_create' => '2 Â· Buat / impor', 'nav_manage' => '3 Â· Kelola dan ekspor', 'bulk_generate' => 'Buat kartu QSL terpilih', 'select_all' => 'Pilih semua', 'select_none' => 'Hapus pilihan', 'all_bands' => 'Semua band', 'all_modes' => 'Semua mode', 'adif_processing' => 'Memproses file ADIF...', 'adif_import_error' => 'Impor ADIF gagal.', 'nav_design_help' => 'Tambahkan latar gambar, warna solid, gradasi, atau palet siap pakai.', 'nav_create_help' => 'Buat QSL manual atau impor file ADIF dengan seret dan lepas.', 'err_select_bg' => 'Silakan pilih gambar latar.', 'ok_bg_image' => 'Latar gambar disimpan.', 'err_gradient_invalid' => 'Warna gradasi tidak valid.', 'ok_bg_gradient' => 'Latar gradasi disimpan.', 'err_solid_invalid' => 'Warna solid tidak valid.', 'ok_bg_solid' => 'Latar warna solid disimpan.', 'err_palette_invalid' => 'Palet preset tidak valid.', 'ok_bg_palette' => 'Palet preset disimpan.', 'ok_bg_default' => 'Latar bawaan diperbarui.', 'ok_bg_deleted' => 'Latar dihapus.', 'err_no_adif' => 'Tidak ada file ADIF yang diterima.', 'err_no_valid_adif' => 'Tidak ada file ADIF valid yang dapat diproses.', 'ok_qso_imported' => 'QSO diimpor dari file ADIF.', 'err_qso_none' => 'Tidak ada QSO baru yang diimpor.', 'ok_qsl_generated' => 'Kartu QSL dibuat.', 'err_qsl_none' => 'Tidak ada QSL yang dibuat. Pilihan kosong atau kartu sudah ada.', 'ok_qsl_created' => 'QSL dibuat.', 'ok_qso_deleted' => 'QSO dihapus.', 'ok_qsl_deleted' => 'QSL dihapus.', 'err_unknown_action' => 'Tindakan QSL tidak dikenal.', 'label_bg_image' => 'Latar gambar', 'label_gradient' => 'Gradasi 2 warna', 'label_delete' => 'Hapus', 'empty_qso' => 'Belum ada QSO yang diimpor.', 'empty_qso_filtered' => 'Tidak ada QSO yang cocok dengan filter aktif.', 'empty_qsl' => 'Belum ada QSL yang dibuat.', 'empty_qsl_filtered' => 'Tidak ada QSL yang cocok dengan pencarian Anda.', 'nav_manage_help' => 'Saring QSO, buat secara massal, lalu ekspor kartu depan/belakang.', 'qso_search_ph' => 'Saring menurut callsign, tanggal, mode...', 'qsl_search_ph' => 'Cari QSL (judul, call, band...)', 'preview_dynamic' => 'Pratinjau dinamis berdasarkan isian formulir.', 'adif_imported_status' => '{imported} QSO diimpor dari {files} file.']),
    'ja' => array_replace($qslEnglishMessages, ['studio' => 'QSLã‚¹ã‚¿ã‚¸ã‚ª Â· ã‚·ãƒ³ãƒ—ãƒ«ã€ã‚¬ã‚¤ãƒ‰ä»˜ãã€åŠ¹çŽ‡çš„', 'studio_help' => 'ã™ã¹ã¦ãŒã‚¹ãƒ”ãƒ¼ãƒ‰é‡è¦–ã§ã™ã€‚QSOã‚’å–ã‚Šè¾¼ã¿ã€ã‚«ãƒ¼ãƒ‰ã‚’ä½œæˆã—ã€ã‚¹ãƒ ãƒ¼ã‚ºã«æ›¸ãå‡ºã›ã¾ã™ã€‚', 'design' => '1) QSLèƒŒæ™¯ã‚’ãƒ‡ã‚¶ã‚¤ãƒ³', 'create' => '2) QSLã‚«ãƒ¼ãƒ‰ã‚’ç°¡å˜ä½œæˆ', 'manage' => '3) å–ã‚Šè¾¼ã¿æ¸ˆã¿QSO', 'generated' => 'ç”Ÿæˆæ¸ˆã¿QSLã‚«ãƒ¼ãƒ‰', 'filter' => 'çµžã‚Šè¾¼ã¿', 'reset' => 'ãƒªã‚»ãƒƒãƒˆ', 'page' => 'ãƒšãƒ¼ã‚¸', 'previous' => 'å‰ã¸', 'next' => 'æ¬¡ã¸', 'nav_design' => '1 Â· ãƒ‡ã‚¶ã‚¤ãƒ³ã‚’ã‚«ã‚¹ã‚¿ãƒžã‚¤ã‚º', 'nav_create' => '2 Â· ä½œæˆ / å–ã‚Šè¾¼ã¿', 'nav_manage' => '3 Â· ç®¡ç†ã¨æ›¸ãå‡ºã—', 'bulk_generate' => 'é¸æŠžã—ãŸQSLã‚«ãƒ¼ãƒ‰ã‚’ç”Ÿæˆ', 'select_all' => 'ã™ã¹ã¦é¸æŠž', 'select_none' => 'é¸æŠžã‚’è§£é™¤', 'all_bands' => 'ã™ã¹ã¦ã®ãƒãƒ³ãƒ‰', 'all_modes' => 'ã™ã¹ã¦ã®ãƒ¢ãƒ¼ãƒ‰', 'adif_processing' => 'ADIFãƒ•ã‚¡ã‚¤ãƒ«ã‚’å‡¦ç†ä¸­...', 'adif_import_error' => 'ADIFã®å–ã‚Šè¾¼ã¿ã«å¤±æ•—ã—ã¾ã—ãŸã€‚', 'nav_design_help' => 'ç”»åƒèƒŒæ™¯ã€å˜è‰²ã€ã‚°ãƒ©ãƒ‡ãƒ¼ã‚·ãƒ§ãƒ³ã€ã¾ãŸã¯ã™ãä½¿ãˆã‚‹ãƒ‘ãƒ¬ãƒƒãƒˆã‚’è¿½åŠ ã§ãã¾ã™ã€‚', 'nav_create_help' => 'æ‰‹å…¥åŠ›ã§QSLã‚’ä½œæˆã™ã‚‹ã‹ã€ADIFãƒ•ã‚¡ã‚¤ãƒ«ã‚’ãƒ‰ãƒ©ãƒƒã‚°ï¼†ãƒ‰ãƒ­ãƒƒãƒ—ã§å–ã‚Šè¾¼ã¿ã¾ã™ã€‚', 'err_select_bg' => 'èƒŒæ™¯ç”»åƒã‚’é¸æŠžã—ã¦ãã ã•ã„ã€‚', 'ok_bg_image' => 'ç”»åƒèƒŒæ™¯ã‚’ä¿å­˜ã—ã¾ã—ãŸã€‚', 'err_gradient_invalid' => 'ã‚°ãƒ©ãƒ‡ãƒ¼ã‚·ãƒ§ãƒ³ã®è‰²ãŒç„¡åŠ¹ã§ã™ã€‚', 'ok_bg_gradient' => 'ã‚°ãƒ©ãƒ‡ãƒ¼ã‚·ãƒ§ãƒ³èƒŒæ™¯ã‚’ä¿å­˜ã—ã¾ã—ãŸã€‚', 'err_solid_invalid' => 'å˜è‰²ãŒç„¡åŠ¹ã§ã™ã€‚', 'ok_bg_solid' => 'å˜è‰²èƒŒæ™¯ã‚’ä¿å­˜ã—ã¾ã—ãŸã€‚', 'err_palette_invalid' => 'ãƒ—ãƒªã‚»ãƒƒãƒˆãƒ‘ãƒ¬ãƒƒãƒˆãŒç„¡åŠ¹ã§ã™ã€‚', 'ok_bg_palette' => 'ãƒ—ãƒªã‚»ãƒƒãƒˆãƒ‘ãƒ¬ãƒƒãƒˆã‚’ä¿å­˜ã—ã¾ã—ãŸã€‚', 'ok_bg_default' => 'æ—¢å®šã®èƒŒæ™¯ã‚’æ›´æ–°ã—ã¾ã—ãŸã€‚', 'ok_bg_deleted' => 'èƒŒæ™¯ã‚’å‰Šé™¤ã—ã¾ã—ãŸã€‚', 'err_no_adif' => 'ADIFãƒ•ã‚¡ã‚¤ãƒ«ã‚’å—ä¿¡ã—ã¦ã„ã¾ã›ã‚“ã€‚', 'err_no_valid_adif' => 'æœ‰åŠ¹ãªADIFãƒ•ã‚¡ã‚¤ãƒ«ã‚’å‡¦ç†ã§ãã¾ã›ã‚“ã§ã—ãŸã€‚', 'ok_qso_imported' => 'ADIFãƒ•ã‚¡ã‚¤ãƒ«ã‹ã‚‰QSOã‚’å–ã‚Šè¾¼ã¿ã¾ã—ãŸã€‚', 'err_qso_none' => 'æ–°ã—ã„QSOã¯å–ã‚Šè¾¼ã¾ã‚Œã¾ã›ã‚“ã§ã—ãŸã€‚', 'ok_qsl_generated' => 'QSLã‚«ãƒ¼ãƒ‰ã‚’ç”Ÿæˆã—ã¾ã—ãŸã€‚', 'err_qsl_none' => 'QSLã¯ç”Ÿæˆã•ã‚Œã¾ã›ã‚“ã§ã—ãŸã€‚é¸æŠžãŒç©ºã€ã¾ãŸã¯ã‚«ãƒ¼ãƒ‰ãŒæ—¢ã«å­˜åœ¨ã—ã¾ã™ã€‚', 'ok_qsl_created' => 'QSLã‚’ä½œæˆã—ã¾ã—ãŸã€‚', 'ok_qso_deleted' => 'QSOã‚’å‰Šé™¤ã—ã¾ã—ãŸã€‚', 'ok_qsl_deleted' => 'QSLã‚’å‰Šé™¤ã—ã¾ã—ãŸã€‚', 'err_unknown_action' => 'ä¸æ˜ŽãªQSLæ“ä½œã§ã™ã€‚', 'label_bg_image' => 'ç”»åƒèƒŒæ™¯', 'label_gradient' => '2è‰²ã‚°ãƒ©ãƒ‡ãƒ¼ã‚·ãƒ§ãƒ³', 'label_delete' => 'å‰Šé™¤', 'empty_qso' => 'å–ã‚Šè¾¼ã¿æ¸ˆã¿QSOã¯ã¾ã ã‚ã‚Šã¾ã›ã‚“ã€‚', 'empty_qso_filtered' => 'æœ‰åŠ¹ãªãƒ•ã‚£ãƒ«ã‚¿ãƒ¼ã«ä¸€è‡´ã™ã‚‹QSOã¯ã‚ã‚Šã¾ã›ã‚“ã€‚', 'empty_qsl' => 'ç”Ÿæˆæ¸ˆã¿QSLã¯ã¾ã ã‚ã‚Šã¾ã›ã‚“ã€‚', 'empty_qsl_filtered' => 'æ¤œç´¢ã«ä¸€è‡´ã™ã‚‹QSLã¯ã‚ã‚Šã¾ã›ã‚“ã€‚', 'nav_manage_help' => 'QSOã‚’çµžã‚Šè¾¼ã¿ã€ä¸€æ‹¬ç”Ÿæˆã—ã€è¡¨é¢/è£é¢ã‚«ãƒ¼ãƒ‰ã‚’æ›¸ãå‡ºã—ã¾ã™ã€‚', 'qso_search_ph' => 'ã‚³ãƒ¼ãƒ«ã‚µã‚¤ãƒ³ã€æ—¥ä»˜ã€ãƒ¢ãƒ¼ãƒ‰ã§çµžã‚Šè¾¼ã¿...', 'qsl_search_ph' => 'QSLã‚’æ¤œç´¢ï¼ˆã‚¿ã‚¤ãƒˆãƒ«ã€ã‚³ãƒ¼ãƒ«ã€ãƒãƒ³ãƒ‰...ï¼‰', 'preview_dynamic' => 'ãƒ•ã‚©ãƒ¼ãƒ é …ç›®ã«åŸºã¥ãå‹•çš„ãƒ—ãƒ¬ãƒ“ãƒ¥ãƒ¼ã€‚', 'adif_imported_status' => '{files}å€‹ã®ãƒ•ã‚¡ã‚¤ãƒ«ã‹ã‚‰{imported}ä»¶ã®QSOã‚’å–ã‚Šè¾¼ã¿ã¾ã—ãŸã€‚']),
    'ru' => array_replace($qslEnglishMessages, ['studio' => 'QSL Studio Â· Ð¿Ñ€Ð¾ÑÑ‚Ð¾, Ñ Ð¿Ð¾Ð´ÑÐºÐ°Ð·ÐºÐ°Ð¼Ð¸, ÑÑ„Ñ„ÐµÐºÑ‚Ð¸Ð²Ð½Ð¾', 'studio_help' => 'Ð’ÑÑ‘ Ñ€Ð°ÑÑÑ‡Ð¸Ñ‚Ð°Ð½Ð¾ Ð½Ð° ÑÐºÐ¾Ñ€Ð¾ÑÑ‚ÑŒ: Ð¸Ð¼Ð¿Ð¾Ñ€Ñ‚Ð¸Ñ€ÑƒÐ¹Ñ‚Ðµ QSO, ÑÐ¾Ð·Ð´Ð°Ð²Ð°Ð¹Ñ‚Ðµ ÐºÐ°Ñ€Ñ‚Ð¾Ñ‡ÐºÐ¸ Ð¸ ÑÐºÑÐ¿Ð¾Ñ€Ñ‚Ð¸Ñ€ÑƒÐ¹Ñ‚Ðµ Ð±ÐµÐ· Ð»Ð¸ÑˆÐ½Ð¸Ñ… ÑˆÐ°Ð³Ð¾Ð².', 'design' => '1) ÐÐ°ÑÑ‚Ñ€Ð¾Ð¹Ñ‚Ðµ Ñ„Ð¾Ð½ QSL', 'create' => '2) Ð›ÐµÐ³ÐºÐ¾ ÑÐ¾Ð·Ð´Ð°Ð²Ð°Ð¹Ñ‚Ðµ QSL-ÐºÐ°Ñ€Ñ‚Ð¾Ñ‡ÐºÐ¸', 'manage' => '3) Ð˜Ð¼Ð¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ðµ QSO', 'generated' => 'Ð¡Ð³ÐµÐ½ÐµÑ€Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ðµ QSL-ÐºÐ°Ñ€Ñ‚Ð¾Ñ‡ÐºÐ¸', 'filter' => 'Ð¤Ð¸Ð»ÑŒÑ‚Ñ€', 'reset' => 'Ð¡Ð±Ñ€Ð¾Ñ', 'page' => 'Ð¡Ñ‚Ñ€Ð°Ð½Ð¸Ñ†Ð°', 'previous' => 'ÐÐ°Ð·Ð°Ð´', 'next' => 'Ð’Ð¿ÐµÑ€Ñ‘Ð´', 'nav_design' => '1 Â· ÐÐ°ÑÑ‚Ñ€Ð¾Ð¸Ñ‚ÑŒ Ð´Ð¸Ð·Ð°Ð¹Ð½', 'nav_create' => '2 Â· Ð¡Ð¾Ð·Ð´Ð°Ñ‚ÑŒ / Ð¸Ð¼Ð¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²Ð°Ñ‚ÑŒ', 'nav_manage' => '3 Â· Ð£Ð¿Ñ€Ð°Ð²Ð»ÑÑ‚ÑŒ Ð¸ ÑÐºÑÐ¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²Ð°Ñ‚ÑŒ', 'bulk_generate' => 'Ð¡Ð³ÐµÐ½ÐµÑ€Ð¸Ñ€Ð¾Ð²Ð°Ñ‚ÑŒ Ð²Ñ‹Ð±Ñ€Ð°Ð½Ð½Ñ‹Ðµ QSL-ÐºÐ°Ñ€Ñ‚Ð¾Ñ‡ÐºÐ¸', 'select_all' => 'Ð’Ñ‹Ð±Ñ€Ð°Ñ‚ÑŒ Ð²ÑÑ‘', 'select_none' => 'Ð¡Ð½ÑÑ‚ÑŒ Ð²Ñ‹Ð±Ð¾Ñ€', 'all_bands' => 'Ð’ÑÐµ Ð´Ð¸Ð°Ð¿Ð°Ð·Ð¾Ð½Ñ‹', 'all_modes' => 'Ð’ÑÐµ Ñ€ÐµÐ¶Ð¸Ð¼Ñ‹', 'adif_processing' => 'ÐžÐ±Ñ€Ð°Ð±Ð¾Ñ‚ÐºÐ° Ñ„Ð°Ð¹Ð»Ð¾Ð² ADIF...', 'adif_import_error' => 'ÐÐµ ÑƒÐ´Ð°Ð»Ð¾ÑÑŒ Ð¸Ð¼Ð¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²Ð°Ñ‚ÑŒ ADIF.', 'nav_design_help' => 'Ð”Ð¾Ð±Ð°Ð²ÑŒÑ‚Ðµ Ñ„Ð¾Ð½Ð¾Ð²Ð¾Ðµ Ð¸Ð·Ð¾Ð±Ñ€Ð°Ð¶ÐµÐ½Ð¸Ðµ, ÑÐ¿Ð»Ð¾ÑˆÐ½Ð¾Ð¹ Ñ†Ð²ÐµÑ‚, Ð³Ñ€Ð°Ð´Ð¸ÐµÐ½Ñ‚ Ð¸Ð»Ð¸ Ð³Ð¾Ñ‚Ð¾Ð²ÑƒÑŽ Ð¿Ð°Ð»Ð¸Ñ‚Ñ€Ñƒ.', 'nav_create_help' => 'Ð¡Ð¾Ð·Ð´Ð°Ð¹Ñ‚Ðµ QSL Ð²Ñ€ÑƒÑ‡Ð½ÑƒÑŽ Ð¸Ð»Ð¸ Ð¸Ð¼Ð¿Ð¾Ñ€Ñ‚Ð¸Ñ€ÑƒÐ¹Ñ‚Ðµ Ñ„Ð°Ð¹Ð»Ñ‹ ADIF Ð¿ÐµÑ€ÐµÑ‚Ð°ÑÐºÐ¸Ð²Ð°Ð½Ð¸ÐµÐ¼.', 'err_select_bg' => 'Ð’Ñ‹Ð±ÐµÑ€Ð¸Ñ‚Ðµ Ñ„Ð¾Ð½Ð¾Ð²Ð¾Ðµ Ð¸Ð·Ð¾Ð±Ñ€Ð°Ð¶ÐµÐ½Ð¸Ðµ.', 'ok_bg_image' => 'Ð¤Ð¾Ð½Ð¾Ð²Ð¾Ðµ Ð¸Ð·Ð¾Ð±Ñ€Ð°Ð¶ÐµÐ½Ð¸Ðµ ÑÐ¾Ñ…Ñ€Ð°Ð½ÐµÐ½Ð¾.', 'err_gradient_invalid' => 'ÐÐµÐ´Ð¾Ð¿ÑƒÑÑ‚Ð¸Ð¼Ñ‹Ðµ Ñ†Ð²ÐµÑ‚Ð° Ð³Ñ€Ð°Ð´Ð¸ÐµÐ½Ñ‚Ð°.', 'ok_bg_gradient' => 'Ð“Ñ€Ð°Ð´Ð¸ÐµÐ½Ñ‚Ð½Ñ‹Ð¹ Ñ„Ð¾Ð½ ÑÐ¾Ñ…Ñ€Ð°Ð½Ñ‘Ð½.', 'err_solid_invalid' => 'ÐÐµÐ´Ð¾Ð¿ÑƒÑÑ‚Ð¸Ð¼Ñ‹Ð¹ ÑÐ¿Ð»Ð¾ÑˆÐ½Ð¾Ð¹ Ñ†Ð²ÐµÑ‚.', 'ok_bg_solid' => 'Ð¡Ð¿Ð»Ð¾ÑˆÐ½Ð¾Ð¹ Ñ„Ð¾Ð½ ÑÐ¾Ñ…Ñ€Ð°Ð½Ñ‘Ð½.', 'err_palette_invalid' => 'ÐÐµÐ´Ð¾Ð¿ÑƒÑÑ‚Ð¸Ð¼Ð°Ñ Ð³Ð¾Ñ‚Ð¾Ð²Ð°Ñ Ð¿Ð°Ð»Ð¸Ñ‚Ñ€Ð°.', 'ok_bg_palette' => 'Ð“Ð¾Ñ‚Ð¾Ð²Ð°Ñ Ð¿Ð°Ð»Ð¸Ñ‚Ñ€Ð° ÑÐ¾Ñ…Ñ€Ð°Ð½ÐµÐ½Ð°.', 'ok_bg_default' => 'Ð¤Ð¾Ð½ Ð¿Ð¾ ÑƒÐ¼Ð¾Ð»Ñ‡Ð°Ð½Ð¸ÑŽ Ð¾Ð±Ð½Ð¾Ð²Ð»Ñ‘Ð½.', 'ok_bg_deleted' => 'Ð¤Ð¾Ð½ ÑƒÐ´Ð°Ð»Ñ‘Ð½.', 'err_no_adif' => 'Ð¤Ð°Ð¹Ð» ADIF Ð½Ðµ Ð¿Ð¾Ð»ÑƒÑ‡ÐµÐ½.', 'err_no_valid_adif' => 'ÐÐµ ÑƒÐ´Ð°Ð»Ð¾ÑÑŒ Ð¾Ð±Ñ€Ð°Ð±Ð¾Ñ‚Ð°Ñ‚ÑŒ Ð½Ð¸ Ð¾Ð´Ð¸Ð½ ÐºÐ¾Ñ€Ñ€ÐµÐºÑ‚Ð½Ñ‹Ð¹ Ñ„Ð°Ð¹Ð» ADIF.', 'ok_qso_imported' => 'QSO Ð¸Ð¼Ð¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²Ð°Ð½Ñ‹ Ð¸Ð· Ñ„Ð°Ð¹Ð»Ð¾Ð² ADIF.', 'err_qso_none' => 'ÐÐ¾Ð²Ñ‹Ðµ QSO Ð½Ðµ Ð¸Ð¼Ð¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²Ð°Ð½Ñ‹.', 'ok_qsl_generated' => 'QSL-ÐºÐ°Ñ€Ñ‚Ð¾Ñ‡ÐºÐ¸ ÑÐ³ÐµÐ½ÐµÑ€Ð¸Ñ€Ð¾Ð²Ð°Ð½Ñ‹.', 'err_qsl_none' => 'QSL Ð½Ðµ ÑÐ³ÐµÐ½ÐµÑ€Ð¸Ñ€Ð¾Ð²Ð°Ð½Ñ‹. Ð’Ñ‹Ð±Ð¾Ñ€ Ð¿ÑƒÑÑ‚ Ð¸Ð»Ð¸ ÐºÐ°Ñ€Ñ‚Ð¾Ñ‡ÐºÐ¸ ÑƒÐ¶Ðµ ÑÑƒÑ‰ÐµÑÑ‚Ð²ÑƒÑŽÑ‚.', 'ok_qsl_created' => 'QSL ÑÐ¾Ð·Ð´Ð°Ð½Ð°.', 'ok_qso_deleted' => 'QSO ÑƒÐ´Ð°Ð»ÐµÐ½Ð¾.', 'ok_qsl_deleted' => 'QSL ÑƒÐ´Ð°Ð»ÐµÐ½Ð°.', 'err_unknown_action' => 'ÐÐµÐ¸Ð·Ð²ÐµÑÑ‚Ð½Ð¾Ðµ Ð´ÐµÐ¹ÑÑ‚Ð²Ð¸Ðµ QSL.', 'label_bg_image' => 'Ð¤Ð¾Ð½Ð¾Ð²Ð¾Ðµ Ð¸Ð·Ð¾Ð±Ñ€Ð°Ð¶ÐµÐ½Ð¸Ðµ', 'label_gradient' => 'Ð“Ñ€Ð°Ð´Ð¸ÐµÐ½Ñ‚ Ð¸Ð· 2 Ñ†Ð²ÐµÑ‚Ð¾Ð²', 'label_delete' => 'Ð£Ð´Ð°Ð»Ð¸Ñ‚ÑŒ', 'empty_qso' => 'Ð˜Ð¼Ð¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ñ… QSO Ð¿Ð¾ÐºÐ° Ð½ÐµÑ‚.', 'empty_qso_filtered' => 'ÐÐµÑ‚ QSO, ÑÐ¾Ð¾Ñ‚Ð²ÐµÑ‚ÑÑ‚Ð²ÑƒÑŽÑ‰Ð¸Ñ… Ð°ÐºÑ‚Ð¸Ð²Ð½Ñ‹Ð¼ Ñ„Ð¸Ð»ÑŒÑ‚Ñ€Ð°Ð¼.', 'empty_qsl' => 'Ð¡Ð³ÐµÐ½ÐµÑ€Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð½Ñ‹Ñ… QSL Ð¿Ð¾ÐºÐ° Ð½ÐµÑ‚.', 'empty_qsl_filtered' => 'ÐÐµÑ‚ QSL, ÑÐ¾Ð¾Ñ‚Ð²ÐµÑ‚ÑÑ‚Ð²ÑƒÑŽÑ‰Ð¸Ñ… Ð¿Ð¾Ð¸ÑÐºÑƒ.', 'nav_manage_help' => 'Ð¤Ð¸Ð»ÑŒÑ‚Ñ€ÑƒÐ¹Ñ‚Ðµ QSO, Ð³ÐµÐ½ÐµÑ€Ð¸Ñ€ÑƒÐ¹Ñ‚Ðµ Ð¿Ð°ÐºÐµÑ‚Ð½Ð¾ Ð¸ ÑÐºÑÐ¿Ð¾Ñ€Ñ‚Ð¸Ñ€ÑƒÐ¹Ñ‚Ðµ Ð»Ð¸Ñ†ÐµÐ²ÑƒÑŽ/Ð¾Ð±Ð¾Ñ€Ð¾Ñ‚Ð½ÑƒÑŽ ÑÑ‚Ð¾Ñ€Ð¾Ð½Ñ‹ ÐºÐ°Ñ€Ñ‚Ð¾Ñ‡ÐµÐº.', 'qso_search_ph' => 'Ð¤Ð¸Ð»ÑŒÑ‚Ñ€ Ð¿Ð¾ Ð¿Ð¾Ð·Ñ‹Ð²Ð½Ð¾Ð¼Ñƒ, Ð´Ð°Ñ‚Ðµ, Ñ€ÐµÐ¶Ð¸Ð¼Ñƒ...', 'qsl_search_ph' => 'ÐŸÐ¾Ð¸ÑÐº QSL (Ð·Ð°Ð³Ð¾Ð»Ð¾Ð²Ð¾Ðº, Ð¿Ð¾Ð·Ñ‹Ð²Ð½Ð¾Ð¹, Ð´Ð¸Ð°Ð¿Ð°Ð·Ð¾Ð½...)', 'preview_dynamic' => 'Ð”Ð¸Ð½Ð°Ð¼Ð¸Ñ‡ÐµÑÐºÐ¸Ð¹ Ð¿Ñ€ÐµÐ´Ð¿Ñ€Ð¾ÑÐ¼Ð¾Ñ‚Ñ€ Ð¿Ð¾ Ð¿Ð¾Ð»ÑÐ¼ Ñ„Ð¾Ñ€Ð¼Ñ‹.', 'adif_imported_status' => '{imported} QSO Ð¸Ð¼Ð¿Ð¾Ñ€Ñ‚Ð¸Ñ€Ð¾Ð²Ð°Ð½Ð¾ Ð¸Ð· {files} Ñ„Ð°Ð¹Ð»(Ð¾Ð²).']),
    'zh' => array_replace($qslEnglishMessages, ['studio' => 'QSL å·¥ä½œå®¤ Â· ç®€å•ã€å¼•å¯¼å¼ã€é«˜æ•ˆ', 'studio_help' => 'ä¸€åˆ‡éƒ½ä¸ºæ•ˆçŽ‡è€Œè®¾è®¡ï¼šå¯¼å…¥ä½ çš„ QSOï¼Œåˆ›å»ºå¡ç‰‡å¹¶é¡ºç•…å¯¼å‡ºã€‚', 'design' => '1) è®¾è®¡ä½ çš„ QSL èƒŒæ™¯', 'create' => '2) è½»æ¾åˆ›å»º QSL å¡ç‰‡', 'manage' => '3) å·²å¯¼å…¥çš„ QSO', 'generated' => 'å·²ç”Ÿæˆçš„ QSL å¡ç‰‡', 'filter' => 'ç­›é€‰', 'reset' => 'é‡ç½®', 'page' => 'é¡µ', 'previous' => 'ä¸Šä¸€é¡µ', 'next' => 'ä¸‹ä¸€é¡µ', 'nav_design' => '1 Â· è‡ªå®šä¹‰è®¾è®¡', 'nav_create' => '2 Â· åˆ›å»º / å¯¼å…¥', 'nav_manage' => '3 Â· ç®¡ç†ä¸Žå¯¼å‡º', 'bulk_generate' => 'ç”Ÿæˆæ‰€é€‰ QSL å¡ç‰‡', 'select_all' => 'å…¨é€‰', 'select_none' => 'å–æ¶ˆå…¨é€‰', 'all_bands' => 'æ‰€æœ‰æ³¢æ®µ', 'all_modes' => 'æ‰€æœ‰æ¨¡å¼', 'adif_processing' => 'æ­£åœ¨å¤„ç† ADIF æ–‡ä»¶...', 'adif_import_error' => 'ADIF å¯¼å…¥å¤±è´¥ã€‚', 'nav_design_help' => 'æ·»åŠ å›¾ç‰‡èƒŒæ™¯ã€çº¯è‰²ã€æ¸å˜æˆ–å¯ç›´æŽ¥ä½¿ç”¨çš„è°ƒè‰²æ¿ã€‚', 'nav_create_help' => 'æ‰‹åŠ¨åˆ›å»º QSLï¼Œæˆ–é€šè¿‡æ‹–æ”¾å¯¼å…¥ ADIF æ–‡ä»¶ã€‚', 'err_select_bg' => 'è¯·é€‰æ‹©ä¸€å¼ èƒŒæ™¯å›¾ç‰‡ã€‚', 'ok_bg_image' => 'å›¾ç‰‡èƒŒæ™¯å·²ä¿å­˜ã€‚', 'err_gradient_invalid' => 'æ¸å˜é¢œè‰²æ— æ•ˆã€‚', 'ok_bg_gradient' => 'æ¸å˜èƒŒæ™¯å·²ä¿å­˜ã€‚', 'err_solid_invalid' => 'çº¯è‰²æ— æ•ˆã€‚', 'ok_bg_solid' => 'çº¯è‰²èƒŒæ™¯å·²ä¿å­˜ã€‚', 'err_palette_invalid' => 'é¢„è®¾è°ƒè‰²æ¿æ— æ•ˆã€‚', 'ok_bg_palette' => 'é¢„è®¾è°ƒè‰²æ¿å·²ä¿å­˜ã€‚', 'ok_bg_default' => 'é»˜è®¤èƒŒæ™¯å·²æ›´æ–°ã€‚', 'ok_bg_deleted' => 'èƒŒæ™¯å·²åˆ é™¤ã€‚', 'err_no_adif' => 'æœªæ”¶åˆ° ADIF æ–‡ä»¶ã€‚', 'err_no_valid_adif' => 'æ— æ³•å¤„ç†ä»»ä½•æœ‰æ•ˆçš„ ADIF æ–‡ä»¶ã€‚', 'ok_qso_imported' => 'å·²ä»Ž ADIF æ–‡ä»¶å¯¼å…¥ QSOã€‚', 'err_qso_none' => 'æ²¡æœ‰å¯¼å…¥æ–°çš„ QSOã€‚', 'ok_qsl_generated' => 'QSL å¡ç‰‡å·²ç”Ÿæˆã€‚', 'err_qsl_none' => 'æœªç”Ÿæˆ QSLã€‚é€‰æ‹©ä¸ºç©ºæˆ–å¡ç‰‡å·²å­˜åœ¨ã€‚', 'ok_qsl_created' => 'QSL å·²åˆ›å»ºã€‚', 'ok_qso_deleted' => 'QSO å·²åˆ é™¤ã€‚', 'ok_qsl_deleted' => 'QSL å·²åˆ é™¤ã€‚', 'err_unknown_action' => 'æœªçŸ¥çš„ QSL æ“ä½œã€‚', 'label_bg_image' => 'å›¾ç‰‡èƒŒæ™¯', 'label_gradient' => 'åŒè‰²æ¸å˜', 'label_delete' => 'åˆ é™¤', 'empty_qso' => 'å°šæœªå¯¼å…¥ QSOã€‚', 'empty_qso_filtered' => 'æ²¡æœ‰ QSO ç¬¦åˆå½“å‰ç­›é€‰æ¡ä»¶ã€‚', 'empty_qsl' => 'å°šæœªç”Ÿæˆ QSLã€‚', 'empty_qsl_filtered' => 'æ²¡æœ‰ QSL ç¬¦åˆä½ çš„æœç´¢ã€‚', 'nav_manage_help' => 'ç­›é€‰ QSOï¼Œæ‰¹é‡ç”Ÿæˆï¼Œå¹¶å¯¼å‡ºæ­£åé¢å¡ç‰‡ã€‚', 'qso_search_ph' => 'æŒ‰å‘¼å·ã€æ—¥æœŸã€æ¨¡å¼ç­›é€‰...', 'qsl_search_ph' => 'æœç´¢ QSLï¼ˆæ ‡é¢˜ã€å‘¼å·ã€æ³¢æ®µ...ï¼‰', 'preview_dynamic' => 'æ ¹æ®è¡¨å•å­—æ®µåŠ¨æ€é¢„è§ˆã€‚', 'adif_imported_status' => 'å·²ä»Ž {files} ä¸ªæ–‡ä»¶å¯¼å…¥ {imported} æ¡ QSOã€‚']),
];
$qt = static function (string $key) use ($locale, $qslI18n): string {
    return (string) (($qslI18n[$locale] ?? $qslI18n['fr'])[$key] ?? $key);
};
$drawPresetPalettes = [
    'club_blue' => ['label' => 'Club blue (gradient)', 'primary' => '#0B1F3A', 'secondary' => '#1D4ED8'],
    'sunset' => ['label' => 'Sunset (gradient)', 'primary' => '#7C2D12', 'secondary' => '#F97316'],
    'northern' => ['label' => 'Aurora (gradient)', 'primary' => '#0F766E', 'secondary' => '#22D3EE'],
    'forest' => ['label' => 'Forest (solid color)', 'primary' => '#166534', 'secondary' => '#166534'],
    'slate' => ['label' => 'Slate (solid color)', 'primary' => '#334155', 'secondary' => '#334155'],
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
            $label = trim((string) ($_POST['background_label'] ?? 'Image background'));
            $label = mb_safe_substr($label !== '' ? $label : 'Image background', 0, 120);
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
            $label = trim((string) ($_POST['gradient_label'] ?? 'Gradient background'));
            $label = mb_safe_substr($label !== '' ? $label : 'Gradient background', 0, 120);
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
            $label = trim((string) ($_POST['solid_label'] ?? 'Solid color background'));
            $label = mb_safe_substr($label !== '' ? $label : 'Solid color background', 0, 120);
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
            $label = trim((string) ($_POST['palette_label'] ?? (string) ($palette['label'] ?? 'Preset palette')));
            $label = mb_safe_substr($label !== '' ? $label : (string) ($palette['label'] ?? 'Preset palette'), 0, 120);
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
        return 'â€”';
    }

    $payload = json_decode($raw, true);
    if (!is_array($payload)) {
        return 'â€”';
    }

    $sent = qsl_normalize_qsl_status((string) ($payload['eqsl_qsl_sent'] ?? ''));
    $received = qsl_normalize_qsl_status((string) ($payload['eqsl_qsl_rcvd'] ?? ''));
    if ($sent === '' && $received === '') {
        return 'â€”';
    }

    return 'S:' . ($sent !== '' ? $sent : 'â€”') . ' / R:' . ($received !== '' ? $received : 'â€”');
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
$qsoPagination = pagination_state($qsoTotal, $qsoPage, $qsoPerPage);
$qslPagination = pagination_state($qslTotal, $qslPage, $qslPerPage);
$qsoPage = $qsoPagination['page'];
$qslPage = $qslPagination['page'];
$qsoTotalPages = $qsoPagination['total_pages'];
$qslTotalPages = $qslPagination['total_pages'];
$pagedQsoRows = array_slice($filteredQsoRows, $qsoPagination['offset'], $qsoPerPage);
$pagedQslRows = array_slice($filteredQslRows, $qslPagination['offset'], $qslPerPage);

$buildQslPageUrl = static function (int $targetQsoPage, int $targetQslPage) use ($qsoSearch, $qsoBandFilter, $qsoModeFilter, $qslSearch): string {
    return route_url_clean('qsl', [
        'qso_search' => $qsoSearch,
        'qso_band' => $qsoBandFilter,
        'qso_mode' => $qsoModeFilter,
        'qsl_search' => $qslSearch,
        'qso_page' => $targetQsoPage > 1 ? $targetQsoPage : null,
        'qsl_page' => $targetQslPage > 1 ? $targetQslPage : null,
    ]);
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
    <p class="help">Choose a background type. The form updates automatically and the preview refreshes live.</p>
    <div class="actions">
        <label><input type="radio" name="qsl_draw_flow" value="image" data-qsl-draw-choice> <?= e($qt('label_bg_image')) ?></label>
        <label><input type="radio" name="qsl_draw_flow" value="solid" data-qsl-draw-choice> Solid color</label>
        <label><input type="radio" name="qsl_draw_flow" value="gradient" data-qsl-draw-choice checked> <?= e($qt('label_gradient')) ?></label>
        <label><input type="radio" name="qsl_draw_flow" value="palette" data-qsl-draw-choice> Preset colors</label>
    </div>
    <div class="split qsl-background-workbench">
        <div>
            <div class="stack">
                <form method="post" enctype="multipart/form-data" class="stack" data-preview-form="image" data-qsl-draw-panel="image">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_image">
                    <label>Image background name<input type="text" name="background_label" maxlength="120" placeholder="Ex: Shack ON4CRD"></label>
                    <label>Image
                        <input type="file" name="background_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required data-preview-image-input>
                    </label>
                    <label><input type="checkbox" name="set_default" value="1"> Set as default background</label>
                    <button type="submit" class="button secondary">Add image background</button>
                </form>
                <form method="post" class="stack" data-preview-form="gradient" data-qsl-draw-panel="gradient">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_gradient">
                    <label>Gradient background name<input type="text" name="gradient_label" maxlength="120" placeholder="Ex: Club blue"></label>
                    <label><span>Background color 1</span><input class="qsl-color-input" type="color" name="background_primary" value="#0B1F3A" data-preview-color-primary></label>
                    <label><span>Background color 2</span><input class="qsl-color-input" type="color" name="background_secondary" value="#1D4ED8" data-preview-color-secondary></label>
                    <label><input type="checkbox" name="set_default" value="1"> Set as default background</label>
                    <button type="submit" class="button secondary">Add gradient background</button>
                </form>
                <form method="post" class="stack is-hidden" data-preview-form="solid" data-qsl-draw-panel="solid">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_solid">
                    <label>Color name<input type="text" name="solid_label" maxlength="120" placeholder="Ex: Night blue"></label>
                    <label>Solid color<input type="color" name="background_solid" value="#1E293B" data-preview-solid-color></label>
                    <label><input type="checkbox" name="set_default" value="1"> Set as default background</label>
                    <button type="submit" class="button secondary">Add solid color</button>
                </form>
                <form method="post" class="stack is-hidden" data-preview-form="palette" data-qsl-draw-panel="palette">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="save_background_palette">
                    <label>Preset palette
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
                    <label>Custom name (optional)<input type="text" name="palette_label" maxlength="120" placeholder="Ex: Aurora palette"></label>
                    <label><input type="checkbox" name="set_default" value="1"> Set as default background</label>
                    <button type="submit" class="button secondary">Add palette</button>
                </form>
            </div>
        </div>
        <div class="qsl-live-preview-wrap">
            <h3>Live preview</h3>
            <div class="qsl-live-preview" data-qsl-preview>
                <div class="qsl-live-preview-card" data-qsl-preview-card>
                    <p class="qsl-live-preview-title">QSL Preview</p>
                    <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> â†’ TO: F4XYZ</p>
                </div>
            </div>
            <p class="help">Preview of the background being created (image, solid color, gradient or preset palette).</p>
        </div>
    </div>
    <?php if ($backgroundPresets !== []): ?>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Background</th><th>Type</th><th>Default</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($backgroundPresets as $preset): ?>
                    <tr>
                        <td><?= e((string) ($preset['label'] ?? 'Background')) ?></td>
                        <td><?= e(((string) ($preset['type'] ?? 'gradient')) === 'image' ? 'Image' : 'Gradient') ?></td>
                        <td><?= ((int) ($preset['is_default'] ?? 0) === 1) ? 'âœ…' : 'â€”' ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="preset_id" value="<?= (int) ($preset['id'] ?? 0) ?>">
                                <button type="submit" name="action" value="set_default_background" class="button secondary small">Set default</button>
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
    <p class="help">Choose your goal: detailed manual creation or instant ADIF import.</p>

    <div class="stack">
        <div>
            <span class="badge muted">Step A</span>
            <h2>What do you need now?</h2>
            <div class="actions">
                <label><input type="radio" name="qsl_assistant_flow" value="manual" data-qsl-assistant-choice checked> Create a manual QSL</label>
                <label><input type="radio" name="qsl_assistant_flow" value="adif" data-qsl-assistant-choice> Import ADIF QSOs</label>
            </div>
        </div>

        <section class="stack" data-qsl-assistant-panel="manual">
            <div>
                <span class="badge muted">Step B</span>
                <h2>Guided manual form</h2>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="create_manual">
                <div class="form-grid">
                    <label>Contact callsign<input type="text" name="qso_call" maxlength="64" required data-manual-preview-source="qso_call"></label>
                    <label>QSO date<input type="date" name="qso_date" data-manual-preview-source="qso_date"></label>
                    <label>UTC<input type="time" name="time_on" step="60" data-manual-preview-source="time_on"></label>
                    <label>Band<input type="text" name="band" maxlength="32" placeholder="20M" data-manual-preview-source="band"></label>
                    <label>Mode<input type="text" name="mode" maxlength="32" placeholder="SSB" data-manual-preview-source="mode"></label>
                    <label>RST sent<input type="text" name="rst_sent" maxlength="16" placeholder="59" data-manual-preview-source="rst_sent"></label>
                    <label>RST received<input type="text" name="rst_recv" maxlength="16" placeholder="59" data-manual-preview-source="rst_recv"></label>
                    <label>Comment
                        <textarea name="comment" rows="3" maxlength="180" data-manual-preview-source="comment">TNX QSO 73</textarea>
                    </label>
                    <label>QSL background
                        <select name="background_preset_id" data-manual-preview-source="background_preset_id">
                            <option value="0" data-bg-type="gradient" data-bg-primary="#0B1F3A" data-bg-secondary="#1D4ED8" <?= $defaultBackgroundPresetId === 0 ? 'selected' : '' ?>>System default background</option>
                            <?php foreach ($backgroundPresets as $preset): ?>
                                <?php
                                $presetId = (int) ($preset['id'] ?? 0);
                                $isDefaultPreset = (int) ($preset['is_default'] ?? 0) === 1;
                                $presetLabel = (string) ($preset['label'] ?? 'Background');
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
                                    <?= e($presetLabel) ?><?= $isDefaultPreset ? ' (default)' : '' ?> â€” <?= e($presetType === 'image' ? 'Image' : 'Gradient') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Print format
                        <select name="template_name">
                            <option value="classic">Front only</option>
                            <option value="classic_duplex">Front and back</option>
                        </select>
                    </label>
                </div>
                <p class="help">Choose one saved background for this QSL.</p>
                <div class="qsl-live-preview-wrap" data-qsl-manual-preview data-preview-note="<?= e($qt('preview_dynamic')) ?>">
                    <h3>QSL preview</h3>
                    <div class="grid-2" data-manual-preview-layout>
                        <div class="qsl-live-preview">
                            <div class="qsl-live-preview-card" data-manual-preview-card>
                                <p class="qsl-live-preview-title">Front preview</p>
                                <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> â†’ TO: <span data-manual-preview-field="qso_call">F4XYZ</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail>DATE: <span data-manual-preview-field="qso_date">20260412</span> UTC: <span data-manual-preview-field="time_on">09:15</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail>BAND: <span data-manual-preview-field="band">20M</span> MODE: <span data-manual-preview-field="mode">SSB</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail>RST S/R: <span data-manual-preview-field="rst_sent">59</span>/<span data-manual-preview-field="rst_recv">59</span></p>
                                <p class="qsl-live-preview-meta" data-manual-preview-front-detail><span data-manual-preview-field="comment">TNX QSO 73</span></p>
                                <p class="qsl-live-preview-meta is-hidden" data-manual-preview-front-message>Front side - details on back side</p>
                            </div>
                        </div>
                        <div class="qsl-live-preview is-hidden" data-manual-preview-back-wrap>
                            <div class="qsl-live-preview-card" data-manual-preview-back-card>
                                <p class="qsl-live-preview-title">Back preview</p>
                                <p class="qsl-live-preview-meta">DE: <?= e((string) ($user['callsign'] ?? 'ON4CRD')) ?> â†’ TO: <span data-manual-preview-back-field="qso_call">F4XYZ</span></p>
                                <p class="qsl-live-preview-meta">DATE: <span data-manual-preview-back-field="qso_date">20260412</span> UTC: <span data-manual-preview-back-field="time_on">09:15</span></p>
                                <p class="qsl-live-preview-meta">BAND: <span data-manual-preview-back-field="band">20M</span> MODE: <span data-manual-preview-back-field="mode">SSB</span></p>
                                <p class="qsl-live-preview-meta">RST S/R: <span data-manual-preview-back-field="rst_sent">59</span>/<span data-manual-preview-back-field="rst_recv">59</span></p>
                                <p class="qsl-live-preview-meta"><span data-manual-preview-back-field="comment">TNX QSO 73</span></p>
                            </div>
                        </div>
                    </div>
                    <p class="help" data-manual-preview-note><?= e($qt('preview_dynamic')) ?></p>
                </div>
                <p><button class="button">Create my QSL</button></p>
            </form>
        </section>

        <section class="stack" data-qsl-assistant-panel="adif">
            <div>
                <span class="badge muted">Step B</span>
                <h2>Fast ADIF import</h2>
            </div>
            <form method="post" enctype="multipart/form-data" id="adif-dropzone-form" class="stack" data-adif-processing="<?= e($qt('adif_processing')) ?>" data-adif-import-error="<?= e($qt('adif_import_error')) ?>" data-adif-imported-status="<?= e($qt('adif_imported_status')) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="import_adif">
                <div id="adif-dropzone" class="dropzone qsl-adif-dropzone">
                    <div class="dz-message">
                        Drag and drop your ADIF files here
                        <small>or click to select multiple files (.adi, .adif)</small>
                    </div>
                </div>
                <input type="file" name="adif_files[]" id="adif-fallback-input" accept=".adi,.adif,text/plain" multiple hidden>
                <p class="help" id="adif-dropzone-status">Files are processed automatically when added.</p>
            </form>
            <p class="help">Exact duplicates are ignored automatically during import.</p>
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
            <a href="<?= e(route_url('qsl')) ?>" class="ghost"><?= e($qt('reset')) ?></a>
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
                    <tr><th></th><th>Call</th><th>Date</th><th>UTC</th><th>Band</th><th>Mode</th><th>RST</th><th>eQSL</th><th>Action</th></tr>
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
                    <?php if ($qsoPage > 1): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage - 1, $qslPage)) ?>">â† <?= e($qt('previous')) ?></a><?php endif; ?>
                    <?php if ($qsoPage < $qsoTotalPages): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage + 1, $qslPage)) ?>"><?= e($qt('next')) ?> â†’</a><?php endif; ?>
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
            <a href="<?= e(route_url('qsl')) ?>" class="ghost"><?= e($qt('reset')) ?></a>
        </form>
        <div class="table-wrap">
            <table>
                <thead>
                <tr><th>Title</th><th>QSO</th><th>Date</th><th>Band</th><th>Mode</th><th>Format</th><th>Preview</th><th>Export</th><th>Action</th></tr>
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
                        <td><a href="<?= e(route_url('qsl_preview', ['id' => (int) $row['id']])) ?>">Voir</a></td>
                        <td>
                            <a href="<?= e(route_url('qsl_export', ['id' => (int) $row['id']])) ?>">Recto SVG</a>
                            <?php if (qsl_template_supports_back((string) ($row['template_name'] ?? 'classic'))): ?>
                                Â· <a href="<?= e(route_url('qsl_export', ['id' => (int) $row['id'], 'side' => 'back'])) ?>">Verso SVG</a>
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
                <?php if ($qslPage > 1): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage, $qslPage - 1)) ?>">â† <?= e($qt('previous')) ?></a><?php endif; ?>
                <?php if ($qslPage < $qslTotalPages): ?><a class="button secondary small" href="<?= e($buildQslPageUrl($qsoPage, $qslPage + 1)) ?>"><?= e($qt('next')) ?> â†’</a><?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
</div>
<?php include __DIR__ . '/qsl_script.js.php'; ?>

<?php
echo render_layout((string) ob_get_clean(), 'QSL');


