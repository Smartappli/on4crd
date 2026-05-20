<?php
declare(strict_types=1);

$user = require_login();
$memberId = (int) ($user['id'] ?? 0);
$locale = current_locale();
$qslEnglishMessages = ['studio' => 'QSL Studio · simple, guided, efficient', 'studio_help' => 'Everything is designed for speed: import your QSOs, create cards and export them seamlessly.', 'design' => '1) Design your QSL backgrounds', 'create' => '2) Create QSL cards easily', 'manage' => '3) Imported QSOs', 'generated' => 'Generated QSL cards', 'filter' => 'Filter', 'reset' => 'Reset', 'page' => 'Page', 'previous' => 'Previous', 'next' => 'Next', 'nav_design' => '1 · Customize design', 'nav_design_help' => 'Add an image background, a solid color, a gradient or a ready-to-use palette.', 'nav_create' => '2 · Create / import', 'nav_create_help' => 'Create a manual QSL or import your ADIF files via drag and drop.', 'err_select_bg' => 'Please select a background image.', 'ok_bg_image' => 'Image background saved.', 'err_gradient_invalid' => 'Invalid gradient colors.', 'ok_bg_gradient' => 'Gradient background saved.', 'err_solid_invalid' => 'Invalid solid color.', 'ok_bg_solid' => 'Solid color background saved.', 'err_palette_invalid' => 'Invalid preset palette.', 'ok_bg_palette' => 'Preset palette saved.', 'ok_bg_default' => 'Default background updated.', 'ok_bg_deleted' => 'Background deleted.', 'err_no_adif' => 'No ADIF file received.', 'err_no_valid_adif' => 'No valid ADIF file could be processed.', 'ok_qso_imported' => 'QSOs imported from ADIF files.', 'err_qso_none' => 'No new QSO imported.', 'ok_qsl_generated' => 'QSL cards generated.', 'err_qsl_none' => 'No QSL generated. Empty selection or cards already exist.', 'ok_qsl_created' => 'QSL created.', 'ok_qso_deleted' => 'QSO deleted.', 'ok_qsl_deleted' => 'QSL deleted.', 'err_unknown_action' => 'Unknown QSL action.', 'label_bg_image' => 'Image background', 'label_gradient' => '2-color gradient', 'label_delete' => 'Delete', 'empty_qso' => 'No imported QSO yet.', 'empty_qso_filtered' => 'No QSO matches active filters.', 'empty_qsl' => 'No generated QSL yet.', 'empty_qsl_filtered' => 'No QSL matches your search.', 'nav_manage' => '3 · Manage and export', 'nav_manage_help' => 'Filter your QSOs, generate in batch and export front/back cards.', 'bulk_generate' => 'Generate selected QSL cards', 'select_all' => 'Select all', 'select_none' => 'Select none', 'qso_search_ph' => 'Filter by callsign, date, mode...', 'qsl_search_ph' => 'Search a QSL (title, call, band...)', 'all_bands' => 'All bands', 'all_modes' => 'All modes', 'preview_dynamic' => 'Dynamic preview based on form fields.', 'adif_processing' => 'Processing ADIF files...', 'adif_import_error' => 'ADIF import failed.', 'adif_imported_status' => '{imported} QSO imported from {files} file(s).'];
$qslI18n = [
    'fr' => ['studio' => 'QSL Studio · simple, guidé, efficace', 'studio_help' => 'Tout est pensé pour aller vite : importez vos QSO, créez vos cartes et exportez-les sans friction.', 'design' => '1) Designer vos fonds QSL', 'create' => '2) Créer des QSL facilement', 'manage' => '3) QSO importés', 'generated' => 'QSL générées', 'filter' => 'Filtrer', 'reset' => 'Réinitialiser', 'page' => 'Page', 'previous' => 'Précédent', 'next' => 'Suivant', 'nav_design' => '1 · Personnaliser le design', 'nav_design_help' => 'Ajoutez un fond image, une couleur unie, un dégradé ou une palette prête à l’emploi.', 'nav_create' => '2 · Créer / importer', 'nav_create_help' => 'Créez une QSL manuelle ou importez vos ADIF en glisser‑déposer.', 'err_select_bg' => 'Veuillez sélectionner une image de fond.', 'ok_bg_image' => 'Fond image enregistré.', 'err_gradient_invalid' => 'Couleurs de dégradé invalides.', 'ok_bg_gradient' => 'Fond dégradé enregistré.', 'err_solid_invalid' => 'Couleur unie invalide.', 'ok_bg_solid' => 'Fond couleur unie enregistré.', 'err_palette_invalid' => 'Palette prédéfinie invalide.', 'ok_bg_palette' => 'Palette prédéfinie enregistrée.', 'ok_bg_default' => 'Fond par défaut mis à jour.', 'ok_bg_deleted' => 'Fond supprimé.', 'err_no_adif' => 'Aucun fichier ADIF reçu.', 'err_no_valid_adif' => 'Aucun fichier ADIF valide n’a pu être traité.', 'ok_qso_imported' => 'QSO importés depuis les fichiers ADIF.', 'err_qso_none' => 'Aucun nouveau QSO importé.', 'ok_qsl_generated' => 'QSL générées.', 'err_qsl_none' => 'Aucune QSL générée. Sélection vide ou QSL déjà existantes.', 'ok_qsl_created' => 'QSL créée.', 'ok_qso_deleted' => 'QSO supprimé.', 'ok_qsl_deleted' => 'QSL supprimée.', 'err_unknown_action' => 'Action QSL inconnue.', 'label_bg_image' => 'Fond image', 'label_gradient' => 'Dégradé 2 couleurs', 'label_delete' => 'Supprimer', 'empty_qso' => 'Aucun QSO importé pour le moment.', 'empty_qso_filtered' => 'Aucun QSO ne correspond aux filtres actifs.', 'empty_qsl' => 'Aucune QSL générée pour le moment.', 'empty_qsl_filtered' => 'Aucune QSL ne correspond à la recherche.', 'nav_manage' => '3 · Gérer et exporter', 'nav_manage_help' => 'Filtrez vos QSO, générez en lot et exportez vos cartes recto/verso.', 'bulk_generate' => 'Générer les QSL sélectionnées', 'select_all' => 'Tout sélectionner', 'select_none' => 'Tout désélectionner', 'qso_search_ph' => 'Filtrer par call, date, mode...', 'qsl_search_ph' => 'Rechercher une QSL (titre, call, bande...)', 'all_bands' => 'Toutes bandes', 'all_modes' => 'Tous modes', 'preview_dynamic' => 'Aperçu dynamique selon les champs du formulaire.', 'adif_processing' => 'Traitement des fichiers ADIF en cours...', 'adif_import_error' => 'Échec de l’import ADIF.', 'adif_imported_status' => '{imported} QSO importé(s) depuis {files} fichier(s).'],
    'en' => $qslEnglishMessages,
    'de' => ['studio' => 'QSL Studio · einfach, geführt, effizient', 'studio_help' => 'Alles ist auf Tempo ausgelegt: QSOs importieren, Karten erstellen und ohne Reibung exportieren.', 'design' => '1) QSL-Hintergründe gestalten', 'create' => '2) QSL-Karten einfach erstellen', 'manage' => '3) Importierte QSOs', 'generated' => 'Erstellte QSL-Karten', 'filter' => 'Filtern', 'reset' => 'Zurücksetzen', 'page' => 'Seite', 'previous' => 'Zurück', 'next' => 'Weiter', 'nav_design' => '1 · Design anpassen', 'nav_design_help' => 'Fügen Sie ein Bild, eine Volltonfarbe, einen Verlauf oder eine fertige Palette hinzu.', 'nav_create' => '2 · Erstellen / importieren', 'nav_create_help' => 'Erstellen Sie eine manuelle QSL oder importieren Sie ADIF per Drag & Drop.', 'err_select_bg' => 'Bitte wählen Sie ein Hintergrundbild aus.', 'ok_bg_image' => 'Bildhintergrund gespeichert.', 'err_gradient_invalid' => 'Ungültige Verlauf-Farben.', 'ok_bg_gradient' => 'Verlaufshintergrund gespeichert.', 'err_solid_invalid' => 'Ungültige Volltonfarbe.', 'ok_bg_solid' => 'Einfarbiger Hintergrund gespeichert.', 'err_palette_invalid' => 'Ungültige vordefinierte Palette.', 'ok_bg_palette' => 'Vordefinierte Palette gespeichert.', 'ok_bg_default' => 'Standardhintergrund aktualisiert.', 'ok_bg_deleted' => 'Hintergrund gelöscht.', 'err_no_adif' => 'Keine ADIF-Datei empfangen.', 'err_no_valid_adif' => 'Keine gültige ADIF-Datei konnte verarbeitet werden.', 'ok_qso_imported' => 'QSOs aus ADIF-Dateien importiert.', 'err_qso_none' => 'Kein neuer QSO importiert.', 'ok_qsl_generated' => 'QSL-Karten erstellt.', 'err_qsl_none' => 'Keine QSL erstellt. Leere Auswahl oder bereits vorhandene Karten.', 'ok_qsl_created' => 'QSL erstellt.', 'ok_qso_deleted' => 'QSO gelöscht.', 'ok_qsl_deleted' => 'QSL gelöscht.', 'err_unknown_action' => 'Unbekannte QSL-Aktion.', 'label_bg_image' => 'Bildhintergrund', 'label_gradient' => '2-Farben-Verlauf', 'label_delete' => 'Löschen', 'empty_qso' => 'Noch kein QSO importiert.', 'empty_qso_filtered' => 'Kein QSO entspricht den aktiven Filtern.', 'empty_qsl' => 'Noch keine QSL erstellt.', 'empty_qsl_filtered' => 'Keine QSL entspricht der Suche.', 'nav_manage' => '3 · Verwalten und exportieren', 'nav_manage_help' => 'Filtern Sie Ihre QSOs, erzeugen Sie Stapel und exportieren Sie Vorder-/Rückseiten.', 'bulk_generate' => 'Ausgewählte QSL-Karten erzeugen', 'select_all' => 'Alle auswählen', 'select_none' => 'Auswahl aufheben', 'qso_search_ph' => 'Nach Rufzeichen, Datum, Modus filtern...', 'qsl_search_ph' => 'QSL suchen (Titel, Rufzeichen, Band...)', 'all_bands' => 'Alle Bänder', 'all_modes' => 'Alle Modi', 'preview_dynamic' => 'Dynamische Vorschau basierend auf den Formularfeldern.', 'adif_processing' => 'ADIF-Dateien werden verarbeitet...', 'adif_import_error' => 'ADIF-Import fehlgeschlagen.', 'adif_imported_status' => '{imported} QSO aus {files} Datei(en) importiert.'],
    'nl' => ['studio' => 'QSL Studio · eenvoudig, begeleid, efficiënt', 'studio_help' => 'Alles is gericht op snelheid: importeer je QSO’s, maak kaarten en exporteer zonder frictie.', 'design' => '1) Ontwerp je QSL-achtergronden', 'create' => '2) Maak eenvoudig QSL-kaarten', 'manage' => '3) Geïmporteerde QSO’s', 'generated' => 'Gegenereerde QSL-kaarten', 'filter' => 'Filteren', 'reset' => 'Opnieuw instellen', 'page' => 'Pagina', 'previous' => 'Vorige', 'next' => 'Volgende', 'nav_design' => '1 · Ontwerp aanpassen', 'nav_design_help' => 'Voeg een afbeeldingsachtergrond, effen kleur, verloop of kant-en-klaar palet toe.', 'nav_create' => '2 · Maken / importeren', 'nav_create_help' => 'Maak een manuele QSL of importeer ADIF via drag-and-drop.', 'err_select_bg' => 'Selecteer een achtergrondafbeelding.', 'ok_bg_image' => 'Afbeeldingsachtergrond opgeslagen.', 'err_gradient_invalid' => 'Ongeldige verloopkleuren.', 'ok_bg_gradient' => 'Verloopachtergrond opgeslagen.', 'err_solid_invalid' => 'Ongeldige effen kleur.', 'ok_bg_solid' => 'Effen achtergrond opgeslagen.', 'err_palette_invalid' => 'Ongeldig vooraf ingesteld palet.', 'ok_bg_palette' => 'Vooraf ingesteld palet opgeslagen.', 'ok_bg_default' => 'Standaardachtergrond bijgewerkt.', 'ok_bg_deleted' => 'Achtergrond verwijderd.', 'err_no_adif' => 'Geen ADIF-bestand ontvangen.', 'err_no_valid_adif' => 'Geen geldig ADIF-bestand kon worden verwerkt.', 'ok_qso_imported' => 'QSO’s geïmporteerd uit ADIF-bestanden.', 'err_qso_none' => 'Geen nieuwe QSO geïmporteerd.', 'ok_qsl_generated' => 'QSL-kaarten gegenereerd.', 'err_qsl_none' => 'Geen QSL gegenereerd. Lege selectie of kaarten bestaan al.', 'ok_qsl_created' => 'QSL aangemaakt.', 'ok_qso_deleted' => 'QSO verwijderd.', 'ok_qsl_deleted' => 'QSL verwijderd.', 'err_unknown_action' => 'Onbekende QSL-actie.', 'label_bg_image' => 'Afbeeldingsachtergrond', 'label_gradient' => 'Verloop met 2 kleuren', 'label_delete' => 'Verwijderen', 'empty_qso' => 'Nog geen QSO geïmporteerd.', 'empty_qso_filtered' => 'Geen QSO komt overeen met de actieve filters.', 'empty_qsl' => 'Nog geen QSL gegenereerd.', 'empty_qsl_filtered' => 'Geen QSL komt overeen met de zoekopdracht.', 'nav_manage' => '3 · Beheren en exporteren', 'nav_manage_help' => 'Filter je QSO’s, genereer in bulk en exporteer voor-/achterkant kaarten.', 'bulk_generate' => 'Geselecteerde QSL-kaarten genereren', 'select_all' => 'Alles selecteren', 'select_none' => 'Selectie wissen', 'qso_search_ph' => 'Filter op roepnaam, datum, mode...', 'qsl_search_ph' => 'Zoek een QSL (titel, roepnaam, band...)', 'all_bands' => 'Alle banden', 'all_modes' => 'Alle modi', 'preview_dynamic' => 'Dynamische preview op basis van formuliervelden.', 'adif_processing' => 'ADIF-bestanden worden verwerkt...', 'adif_import_error' => 'ADIF-import mislukt.', 'adif_imported_status' => '{imported} QSO geïmporteerd uit {files} bestand(en).'],
    'pt' => ['studio' => 'QSL Studio · simples, guiado e eficiente', 'studio_help' => 'Tudo é pensado para rapidez: importe os seus QSO, crie cartões e exporte sem fricção.', 'design' => '1) Desenhe os seus fundos QSL', 'create' => '2) Crie cartões QSL facilmente', 'manage' => '3) QSO importados', 'generated' => 'Cartões QSL gerados', 'filter' => 'Filtrar', 'reset' => 'Repor', 'page' => 'Página', 'previous' => 'Anterior', 'next' => 'Seguinte', 'nav_design' => '1 · Personalizar design', 'nav_design_help' => 'Adicione um fundo de imagem, uma cor sólida, um gradiente ou uma paleta predefinida.', 'nav_create' => '2 · Criar / importar', 'nav_create_help' => 'Crie um QSL manual ou importe os seus ficheiros ADIF com arrastar e largar.', 'err_select_bg' => 'Selecione uma imagem de fundo.', 'ok_bg_image' => 'Fundo de imagem guardado.', 'err_gradient_invalid' => 'Cores de gradiente inválidas.', 'ok_bg_gradient' => 'Fundo em gradiente guardado.', 'err_solid_invalid' => 'Cor sólida inválida.', 'ok_bg_solid' => 'Fundo de cor sólida guardado.', 'err_palette_invalid' => 'Paleta predefinida inválida.', 'ok_bg_palette' => 'Paleta predefinida guardada.', 'ok_bg_default' => 'Fundo predefinido atualizado.', 'ok_bg_deleted' => 'Fundo eliminado.', 'err_no_adif' => 'Nenhum ficheiro ADIF recebido.', 'err_no_valid_adif' => 'Não foi possível processar nenhum ficheiro ADIF válido.', 'ok_qso_imported' => 'QSO importados de ficheiros ADIF.', 'err_qso_none' => 'Nenhum novo QSO importado.', 'ok_qsl_generated' => 'Cartões QSL gerados.', 'err_qsl_none' => 'Nenhum QSL gerado. Seleção vazia ou cartões já existentes.', 'ok_qsl_created' => 'QSL criado.', 'ok_qso_deleted' => 'QSO eliminado.', 'ok_qsl_deleted' => 'QSL eliminado.', 'err_unknown_action' => 'Ação QSL desconhecida.', 'label_bg_image' => 'Fundo de imagem', 'label_gradient' => 'Gradiente de 2 cores', 'label_delete' => 'Eliminar', 'empty_qso' => 'Ainda não há QSO importados.', 'empty_qso_filtered' => 'Nenhum QSO corresponde aos filtros ativos.', 'empty_qsl' => 'Ainda não há QSL gerados.', 'empty_qsl_filtered' => 'Nenhum QSL corresponde à sua pesquisa.', 'nav_manage' => '3 · Gerir e exportar', 'nav_manage_help' => 'Filtre os seus QSO, gere em lote e exporte cartões frente/verso.', 'bulk_generate' => 'Gerar cartões QSL selecionados', 'select_all' => 'Selecionar tudo', 'select_none' => 'Limpar seleção', 'qso_search_ph' => 'Filtrar por indicativo, data, modo...', 'qsl_search_ph' => 'Pesquisar um QSL (título, indicativo, banda...)', 'all_bands' => 'Todas as bandas', 'all_modes' => 'Todos os modos', 'preview_dynamic' => 'Pré-visualização dinâmica com base nos campos do formulário.', 'adif_processing' => 'A processar ficheiros ADIF...', 'adif_import_error' => 'Falha na importação ADIF.', 'adif_imported_status' => '{imported} QSO importado(s) de {files} ficheiro(s).'],
    'it' => ['studio' => 'QSL Studio · semplice, guidato ed efficiente', 'studio_help' => 'Tutto è pensato per la velocità: importa i tuoi QSO, crea carte ed esportale senza attriti.', 'design' => '1) Progetta i tuoi sfondi QSL', 'create' => '2) Crea facilmente carte QSL', 'manage' => '3) QSO importati', 'generated' => 'Carte QSL generate', 'filter' => 'Filtra', 'reset' => 'Reimposta', 'page' => 'Pagina', 'previous' => 'Precedente', 'next' => 'Successiva', 'nav_design' => '1 · Personalizza design', 'nav_design_help' => 'Aggiungi uno sfondo immagine, un colore pieno, una sfumatura o una palette predefinita.', 'nav_create' => '2 · Crea / importa', 'nav_create_help' => 'Crea una QSL manuale o importa i tuoi file ADIF tramite drag and drop.', 'err_select_bg' => 'Seleziona un’immagine di sfondo.', 'ok_bg_image' => 'Sfondo immagine salvato.', 'err_gradient_invalid' => 'Colori della sfumatura non validi.', 'ok_bg_gradient' => 'Sfondo sfumato salvato.', 'err_solid_invalid' => 'Colore pieno non valido.', 'ok_bg_solid' => 'Sfondo a colore pieno salvato.', 'err_palette_invalid' => 'Palette predefinita non valida.', 'ok_bg_palette' => 'Palette predefinita salvata.', 'ok_bg_default' => 'Sfondo predefinito aggiornato.', 'ok_bg_deleted' => 'Sfondo eliminato.', 'err_no_adif' => 'Nessun file ADIF ricevuto.', 'err_no_valid_adif' => 'Nessun file ADIF valido elaborabile.', 'ok_qso_imported' => 'QSO importati da file ADIF.', 'err_qso_none' => 'Nessun nuovo QSO importato.', 'ok_qsl_generated' => 'Carte QSL generate.', 'err_qsl_none' => 'Nessuna QSL generata. Selezione vuota o carte già esistenti.', 'ok_qsl_created' => 'QSL creata.', 'ok_qso_deleted' => 'QSO eliminato.', 'ok_qsl_deleted' => 'QSL eliminata.', 'err_unknown_action' => 'Azione QSL sconosciuta.', 'label_bg_image' => 'Sfondo immagine', 'label_gradient' => 'Sfumatura a 2 colori', 'label_delete' => 'Elimina', 'empty_qso' => 'Nessun QSO importato al momento.', 'empty_qso_filtered' => 'Nessun QSO corrisponde ai filtri attivi.', 'empty_qsl' => 'Nessuna QSL generata al momento.', 'empty_qsl_filtered' => 'Nessuna QSL corrisponde alla ricerca.', 'nav_manage' => '3 · Gestisci ed esporta', 'nav_manage_help' => 'Filtra i tuoi QSO, genera in batch ed esporta carte fronte/retro.', 'bulk_generate' => 'Genera le carte QSL selezionate', 'select_all' => 'Seleziona tutto', 'select_none' => 'Deseleziona tutto', 'qso_search_ph' => 'Filtra per nominativo, data, modo...', 'qsl_search_ph' => 'Cerca una QSL (titolo, nominativo, banda...)', 'all_bands' => 'Tutte le bande', 'all_modes' => 'Tutti i modi', 'preview_dynamic' => 'Anteprima dinamica in base ai campi del modulo.', 'adif_processing' => 'Elaborazione file ADIF...', 'adif_import_error' => 'Importazione ADIF non riuscita.', 'adif_imported_status' => '{imported} QSO importato/i da {files} file.'],
    'es' => ['studio' => 'QSL Studio · simple, guiado y eficiente', 'studio_help' => 'Todo está diseñado para la rapidez: importa tus QSO, crea tarjetas y expórtalas sin fricción.', 'design' => '1) Diseña tus fondos QSL', 'create' => '2) Crea tarjetas QSL fácilmente', 'manage' => '3) QSO importados', 'generated' => 'Tarjetas QSL generadas', 'filter' => 'Filtrar', 'reset' => 'Restablecer', 'page' => 'Página', 'previous' => 'Anterior', 'next' => 'Siguiente', 'nav_design' => '1 · Personalizar diseño', 'nav_design_help' => 'Añade un fondo de imagen, un color sólido, un degradado o una paleta predefinida.', 'nav_create' => '2 · Crear / importar', 'nav_create_help' => 'Crea una QSL manual o importa tus archivos ADIF mediante arrastrar y soltar.', 'err_select_bg' => 'Selecciona una imagen de fondo.', 'ok_bg_image' => 'Fondo de imagen guardado.', 'err_gradient_invalid' => 'Colores de degradado no válidos.', 'ok_bg_gradient' => 'Fondo degradado guardado.', 'err_solid_invalid' => 'Color sólido no válido.', 'ok_bg_solid' => 'Fondo de color sólido guardado.', 'err_palette_invalid' => 'Paleta predefinida no válida.', 'ok_bg_palette' => 'Paleta predefinida guardada.', 'ok_bg_default' => 'Fondo predeterminado actualizado.', 'ok_bg_deleted' => 'Fondo eliminado.', 'err_no_adif' => 'No se recibió ningún archivo ADIF.', 'err_no_valid_adif' => 'No se pudo procesar ningún archivo ADIF válido.', 'ok_qso_imported' => 'QSO importados desde archivos ADIF.', 'err_qso_none' => 'No se importó ningún QSO nuevo.', 'ok_qsl_generated' => 'Tarjetas QSL generadas.', 'err_qsl_none' => 'No se generó ninguna QSL. Selección vacía o tarjetas ya existentes.', 'ok_qsl_created' => 'QSL creada.', 'ok_qso_deleted' => 'QSO eliminado.', 'ok_qsl_deleted' => 'QSL eliminada.', 'err_unknown_action' => 'Acción QSL desconocida.', 'label_bg_image' => 'Fondo de imagen', 'label_gradient' => 'Degradado de 2 colores', 'label_delete' => 'Eliminar', 'empty_qso' => 'Aún no hay QSO importados.', 'empty_qso_filtered' => 'Ningún QSO coincide con los filtros activos.', 'empty_qsl' => 'Aún no hay QSL generadas.', 'empty_qsl_filtered' => 'Ninguna QSL coincide con tu búsqueda.', 'nav_manage' => '3 · Gestionar y exportar', 'nav_manage_help' => 'Filtra tus QSO, genera en lote y exporta tarjetas anverso/reverso.', 'bulk_generate' => 'Generar tarjetas QSL seleccionadas', 'select_all' => 'Seleccionar todo', 'select_none' => 'Deseleccionar todo', 'qso_search_ph' => 'Filtrar por indicativo, fecha, modo...', 'qsl_search_ph' => 'Buscar una QSL (título, indicativo, banda...)', 'all_bands' => 'Todas las bandas', 'all_modes' => 'Todos los modos', 'preview_dynamic' => 'Vista previa dinámica según los campos del formulario.', 'adif_processing' => 'Procesando archivos ADIF...', 'adif_import_error' => 'Error al importar ADIF.', 'adif_imported_status' => '{imported} QSO importado(s) desde {files} archivo(s).'],


    'ar' => array_replace($qslEnglishMessages, ['studio' => 'استوديو QSL · بسيط، موجّه وفعّال', 'studio_help' => 'كل شيء مصمم للسرعة: استورد سجلات QSO، أنشئ البطاقات وصدّرها بسلاسة.', 'design' => '1) صمّم خلفيات QSL', 'create' => '2) أنشئ بطاقات QSL بسهولة', 'manage' => '3) سجلات QSO المستوردة', 'generated' => 'بطاقات QSL المُولّدة', 'filter' => 'تصفية', 'reset' => 'إعادة تعيين', 'page' => 'الصفحة', 'previous' => 'السابق', 'next' => 'التالي', 'nav_design' => '1 · تخصيص التصميم', 'nav_create' => '2 · إنشاء / استيراد', 'nav_manage' => '3 · إدارة وتصدير', 'bulk_generate' => 'إنشاء بطاقات QSL المحددة', 'select_all' => 'تحديد الكل', 'select_none' => 'إلغاء التحديد', 'all_bands' => 'كل النطاقات', 'all_modes' => 'كل الأنماط', 'adif_processing' => 'جارٍ معالجة ملفات ADIF...', 'adif_import_error' => 'فشل استيراد ADIF.', 'nav_design_help' => 'أضف خلفية صورة أو لونا ثابتا أو تدرجا أو لوحة جاهزة للاستخدام.', 'nav_create_help' => 'أنشئ بطاقة QSL يدويا أو استورد ملفات ADIF بالسحب والإفلات.', 'err_select_bg' => 'يرجى اختيار صورة خلفية.', 'ok_bg_image' => 'تم حفظ خلفية الصورة.', 'err_gradient_invalid' => 'ألوان التدرج غير صالحة.', 'ok_bg_gradient' => 'تم حفظ خلفية التدرج.', 'err_solid_invalid' => 'اللون الثابت غير صالح.', 'ok_bg_solid' => 'تم حفظ خلفية اللون الثابت.', 'err_palette_invalid' => 'اللوحة المحددة مسبقا غير صالحة.', 'ok_bg_palette' => 'تم حفظ اللوحة المحددة مسبقا.', 'ok_bg_default' => 'تم تحديث الخلفية الافتراضية.', 'ok_bg_deleted' => 'تم حذف الخلفية.', 'err_no_adif' => 'لم يتم استلام أي ملف ADIF.', 'err_no_valid_adif' => 'تعذرت معالجة أي ملف ADIF صالح.', 'ok_qso_imported' => 'تم استيراد QSO من ملفات ADIF.', 'err_qso_none' => 'لم يتم استيراد أي QSO جديد.', 'ok_qsl_generated' => 'تم إنشاء بطاقات QSL.', 'err_qsl_none' => 'لم يتم إنشاء أي QSL. التحديد فارغ أو البطاقات موجودة مسبقا.', 'ok_qsl_created' => 'تم إنشاء QSL.', 'ok_qso_deleted' => 'تم حذف QSO.', 'ok_qsl_deleted' => 'تم حذف QSL.', 'err_unknown_action' => 'إجراء QSL غير معروف.', 'label_bg_image' => 'خلفية صورة', 'label_gradient' => 'تدرج بلونين', 'label_delete' => 'حذف', 'empty_qso' => 'لا توجد QSO مستوردة حتى الآن.', 'empty_qso_filtered' => 'لا توجد QSO تطابق المرشحات النشطة.', 'empty_qsl' => 'لا توجد QSL منشأة حتى الآن.', 'empty_qsl_filtered' => 'لا توجد QSL تطابق البحث.', 'nav_manage_help' => 'صف QSO الخاصة بك وأنشئها دفعة واحدة وصدّر البطاقات الأمامية والخلفية.', 'qso_search_ph' => 'صف حسب النداء أو التاريخ أو النمط...', 'qsl_search_ph' => 'ابحث عن QSL (العنوان، النداء، النطاق...)', 'preview_dynamic' => 'معاينة ديناميكية حسب حقول النموذج.', 'adif_imported_status' => 'تم استيراد {imported} QSO من {files} ملف.']),
    'bn' => array_replace($qslEnglishMessages, ['studio' => 'QSL স্টুডিও · সহজ, নির্দেশিত, কার্যকর', 'studio_help' => 'সবকিছু দ্রুততার জন্য তৈরি: আপনার QSO ইমপোর্ট করুন, কার্ড তৈরি করুন এবং সহজে এক্সপোর্ট করুন।', 'design' => '1) আপনার QSL ব্যাকগ্রাউন্ড ডিজাইন করুন', 'create' => '2) সহজে QSL কার্ড তৈরি করুন', 'manage' => '3) ইমপোর্ট করা QSO', 'generated' => 'তৈরি হওয়া QSL কার্ড', 'filter' => 'ফিল্টার', 'reset' => 'রিসেট', 'page' => 'পৃষ্ঠা', 'previous' => 'পূর্ববর্তী', 'next' => 'পরবর্তী', 'nav_design' => '1 · ডিজাইন কাস্টমাইজ', 'nav_create' => '2 · তৈরি / ইমপোর্ট', 'nav_manage' => '3 · পরিচালনা ও এক্সপোর্ট', 'bulk_generate' => 'নির্বাচিত QSL কার্ড তৈরি করুন', 'select_all' => 'সব নির্বাচন', 'select_none' => 'নির্বাচন বাতিল', 'all_bands' => 'সব ব্যান্ড', 'all_modes' => 'সব মোড', 'adif_processing' => 'ADIF ফাইল প্রক্রিয়াকরণ চলছে...', 'adif_import_error' => 'ADIF ইমপোর্ট ব্যর্থ হয়েছে।', 'nav_design_help' => 'ছবির ব্যাকগ্রাউন্ড, একরঙা রং, গ্রেডিয়েন্ট বা প্রস্তুত প্যালেট যোগ করুন।', 'nav_create_help' => 'ম্যানুয়াল QSL তৈরি করুন অথবা ড্র্যাগ-অ্যান্ড-ড্রপে ADIF ফাইল ইমপোর্ট করুন।', 'err_select_bg' => 'অনুগ্রহ করে একটি ব্যাকগ্রাউন্ড ছবি নির্বাচন করুন।', 'ok_bg_image' => 'ছবির ব্যাকগ্রাউন্ড সংরক্ষিত হয়েছে।', 'err_gradient_invalid' => 'গ্রেডিয়েন্টের রং বৈধ নয়।', 'ok_bg_gradient' => 'গ্রেডিয়েন্ট ব্যাকগ্রাউন্ড সংরক্ষিত হয়েছে।', 'err_solid_invalid' => 'একরঙা রং বৈধ নয়।', 'ok_bg_solid' => 'একরঙা ব্যাকগ্রাউন্ড সংরক্ষিত হয়েছে।', 'err_palette_invalid' => 'পূর্বনির্ধারিত প্যালেট বৈধ নয়।', 'ok_bg_palette' => 'পূর্বনির্ধারিত প্যালেট সংরক্ষিত হয়েছে।', 'ok_bg_default' => 'ডিফল্ট ব্যাকগ্রাউন্ড আপডেট হয়েছে।', 'ok_bg_deleted' => 'ব্যাকগ্রাউন্ড মুছে ফেলা হয়েছে।', 'err_no_adif' => 'কোনো ADIF ফাইল পাওয়া যায়নি।', 'err_no_valid_adif' => 'কোনো বৈধ ADIF ফাইল প্রক্রিয়া করা যায়নি।', 'ok_qso_imported' => 'ADIF ফাইল থেকে QSO ইমপোর্ট করা হয়েছে।', 'err_qso_none' => 'নতুন কোনো QSO ইমপোর্ট হয়নি।', 'ok_qsl_generated' => 'QSL কার্ড তৈরি হয়েছে।', 'err_qsl_none' => 'কোনো QSL তৈরি হয়নি। নির্বাচন খালি বা কার্ড আগে থেকেই আছে।', 'ok_qsl_created' => 'QSL তৈরি হয়েছে।', 'ok_qso_deleted' => 'QSO মুছে ফেলা হয়েছে।', 'ok_qsl_deleted' => 'QSL মুছে ফেলা হয়েছে।', 'err_unknown_action' => 'অজানা QSL ক্রিয়া।', 'label_bg_image' => 'ছবির ব্যাকগ্রাউন্ড', 'label_gradient' => '২ রঙের গ্রেডিয়েন্ট', 'label_delete' => 'মুছুন', 'empty_qso' => 'এখনও কোনো QSO ইমপোর্ট করা হয়নি।', 'empty_qso_filtered' => 'সক্রিয় ফিল্টারের সাথে কোনো QSO মেলেনি।', 'empty_qsl' => 'এখনও কোনো QSL তৈরি হয়নি।', 'empty_qsl_filtered' => 'আপনার অনুসন্ধানের সাথে কোনো QSL মেলেনি।', 'nav_manage_help' => 'আপনার QSO ফিল্টার করুন, ব্যাচে তৈরি করুন এবং সামনের/পেছনের কার্ড এক্সপোর্ট করুন।', 'qso_search_ph' => 'কলসাইন, তারিখ, মোড দিয়ে ফিল্টার করুন...', 'qsl_search_ph' => 'QSL খুঁজুন (শিরোনাম, কল, ব্যান্ড...)', 'preview_dynamic' => 'ফর্মের ক্ষেত্র অনুযায়ী গতিশীল প্রিভিউ।', 'adif_imported_status' => '{files}টি ফাইল থেকে {imported}টি QSO ইমপোর্ট হয়েছে।']),
    'hi' => array_replace($qslEnglishMessages, ['studio' => 'QSL स्टूडियो · सरल, निर्देशित, प्रभावी', 'studio_help' => 'सब कुछ तेज़ी के लिए बनाया गया है: अपने QSO आयात करें, कार्ड बनाएं और आसानी से निर्यात करें।', 'design' => '1) अपने QSL बैकग्राउंड डिज़ाइन करें', 'create' => '2) आसानी से QSL कार्ड बनाएं', 'manage' => '3) आयातित QSO', 'generated' => 'जनरेट किए गए QSL कार्ड', 'filter' => 'फ़िल्टर', 'reset' => 'रीसेट', 'page' => 'पृष्ठ', 'previous' => 'पिछला', 'next' => 'अगला', 'nav_design' => '1 · डिज़ाइन अनुकूलित करें', 'nav_create' => '2 · बनाएँ / आयात करें', 'nav_manage' => '3 · प्रबंधित करें और निर्यात करें', 'bulk_generate' => 'चयनित QSL कार्ड जनरेट करें', 'select_all' => 'सभी चुनें', 'select_none' => 'चयन हटाएँ', 'all_bands' => 'सभी बैंड', 'all_modes' => 'सभी मोड', 'adif_processing' => 'ADIF फ़ाइलें प्रोसेस की जा रही हैं...', 'adif_import_error' => 'ADIF आयात विफल हुआ।', 'nav_design_help' => 'चित्र पृष्ठभूमि, ठोस रंग, ग्रेडिएंट या तैयार पैलेट जोड़ें।', 'nav_create_help' => 'मैनुअल QSL बनाएं या अपनी ADIF फाइलें ड्रैग-एंड-ड्रॉप से आयात करें।', 'err_select_bg' => 'कृपया एक पृष्ठभूमि चित्र चुनें।', 'ok_bg_image' => 'चित्र पृष्ठभूमि सहेजी गई।', 'err_gradient_invalid' => 'ग्रेडिएंट रंग अमान्य हैं।', 'ok_bg_gradient' => 'ग्रेडिएंट पृष्ठभूमि सहेजी गई।', 'err_solid_invalid' => 'ठोस रंग अमान्य है।', 'ok_bg_solid' => 'ठोस रंग पृष्ठभूमि सहेजी गई।', 'err_palette_invalid' => 'पूर्वनिर्धारित पैलेट अमान्य है।', 'ok_bg_palette' => 'पूर्वनिर्धारित पैलेट सहेजा गया।', 'ok_bg_default' => 'डिफ़ॉल्ट पृष्ठभूमि अपडेट हुई।', 'ok_bg_deleted' => 'पृष्ठभूमि हटाई गई।', 'err_no_adif' => 'कोई ADIF फाइल प्राप्त नहीं हुई।', 'err_no_valid_adif' => 'कोई वैध ADIF फाइल संसाधित नहीं की जा सकी।', 'ok_qso_imported' => 'ADIF फाइलों से QSO आयात किए गए।', 'err_qso_none' => 'कोई नया QSO आयात नहीं हुआ।', 'ok_qsl_generated' => 'QSL कार्ड जनरेट हुए।', 'err_qsl_none' => 'कोई QSL जनरेट नहीं हुई। चयन खाली है या कार्ड पहले से मौजूद हैं।', 'ok_qsl_created' => 'QSL बनाई गई।', 'ok_qso_deleted' => 'QSO हटाया गया।', 'ok_qsl_deleted' => 'QSL हटाई गई।', 'err_unknown_action' => 'अज्ञात QSL क्रिया।', 'label_bg_image' => 'चित्र पृष्ठभूमि', 'label_gradient' => '2-रंग ग्रेडिएंट', 'label_delete' => 'हटाएं', 'empty_qso' => 'अभी कोई QSO आयातित नहीं है।', 'empty_qso_filtered' => 'सक्रिय फ़िल्टर से कोई QSO मेल नहीं खाता।', 'empty_qsl' => 'अभी कोई QSL जनरेट नहीं हुई।', 'empty_qsl_filtered' => 'आपकी खोज से कोई QSL मेल नहीं खाती।', 'nav_manage_help' => 'अपने QSO फ़िल्टर करें, बैच में जनरेट करें और सामने/पीछे के कार्ड निर्यात करें।', 'qso_search_ph' => 'कॉलसाइन, तारीख, मोड से फ़िल्टर करें...', 'qsl_search_ph' => 'QSL खोजें (शीर्षक, कॉल, बैंड...)', 'preview_dynamic' => 'फॉर्म फ़ील्ड के अनुसार गतिशील पूर्वावलोकन।', 'adif_imported_status' => '{files} फाइलों से {imported} QSO आयात हुए।']),
    'id' => array_replace($qslEnglishMessages, ['studio' => 'Studio QSL · sederhana, terpandu, efisien', 'studio_help' => 'Semuanya dirancang untuk kecepatan: impor QSO Anda, buat kartu, dan ekspor tanpa hambatan.', 'design' => '1) Rancang latar belakang QSL Anda', 'create' => '2) Buat kartu QSL dengan mudah', 'manage' => '3) QSO yang diimpor', 'generated' => 'Kartu QSL yang dihasilkan', 'filter' => 'Saring', 'reset' => 'Atur ulang', 'page' => 'Halaman', 'previous' => 'Sebelumnya', 'next' => 'Berikutnya', 'nav_design' => '1 · Sesuaikan desain', 'nav_create' => '2 · Buat / impor', 'nav_manage' => '3 · Kelola dan ekspor', 'bulk_generate' => 'Buat kartu QSL terpilih', 'select_all' => 'Pilih semua', 'select_none' => 'Hapus pilihan', 'all_bands' => 'Semua band', 'all_modes' => 'Semua mode', 'adif_processing' => 'Memproses file ADIF...', 'adif_import_error' => 'Impor ADIF gagal.', 'nav_design_help' => 'Tambahkan latar gambar, warna solid, gradasi, atau palet siap pakai.', 'nav_create_help' => 'Buat QSL manual atau impor file ADIF dengan seret dan lepas.', 'err_select_bg' => 'Silakan pilih gambar latar.', 'ok_bg_image' => 'Latar gambar disimpan.', 'err_gradient_invalid' => 'Warna gradasi tidak valid.', 'ok_bg_gradient' => 'Latar gradasi disimpan.', 'err_solid_invalid' => 'Warna solid tidak valid.', 'ok_bg_solid' => 'Latar warna solid disimpan.', 'err_palette_invalid' => 'Palet preset tidak valid.', 'ok_bg_palette' => 'Palet preset disimpan.', 'ok_bg_default' => 'Latar bawaan diperbarui.', 'ok_bg_deleted' => 'Latar dihapus.', 'err_no_adif' => 'Tidak ada file ADIF yang diterima.', 'err_no_valid_adif' => 'Tidak ada file ADIF valid yang dapat diproses.', 'ok_qso_imported' => 'QSO diimpor dari file ADIF.', 'err_qso_none' => 'Tidak ada QSO baru yang diimpor.', 'ok_qsl_generated' => 'Kartu QSL dibuat.', 'err_qsl_none' => 'Tidak ada QSL yang dibuat. Pilihan kosong atau kartu sudah ada.', 'ok_qsl_created' => 'QSL dibuat.', 'ok_qso_deleted' => 'QSO dihapus.', 'ok_qsl_deleted' => 'QSL dihapus.', 'err_unknown_action' => 'Tindakan QSL tidak dikenal.', 'label_bg_image' => 'Latar gambar', 'label_gradient' => 'Gradasi 2 warna', 'label_delete' => 'Hapus', 'empty_qso' => 'Belum ada QSO yang diimpor.', 'empty_qso_filtered' => 'Tidak ada QSO yang cocok dengan filter aktif.', 'empty_qsl' => 'Belum ada QSL yang dibuat.', 'empty_qsl_filtered' => 'Tidak ada QSL yang cocok dengan pencarian Anda.', 'nav_manage_help' => 'Saring QSO, buat secara massal, lalu ekspor kartu depan/belakang.', 'qso_search_ph' => 'Saring menurut callsign, tanggal, mode...', 'qsl_search_ph' => 'Cari QSL (judul, call, band...)', 'preview_dynamic' => 'Pratinjau dinamis berdasarkan isian formulir.', 'adif_imported_status' => '{imported} QSO diimpor dari {files} file.']),
    'ja' => array_replace($qslEnglishMessages, ['studio' => 'QSLスタジオ · シンプル、ガイド付き、効率的', 'studio_help' => 'すべてがスピード重視です。QSOを取り込み、カードを作成し、スムーズに書き出せます。', 'design' => '1) QSL背景をデザイン', 'create' => '2) QSLカードを簡単作成', 'manage' => '3) 取り込み済みQSO', 'generated' => '生成済みQSLカード', 'filter' => '絞り込み', 'reset' => 'リセット', 'page' => 'ページ', 'previous' => '前へ', 'next' => '次へ', 'nav_design' => '1 · デザインをカスタマイズ', 'nav_create' => '2 · 作成 / 取り込み', 'nav_manage' => '3 · 管理と書き出し', 'bulk_generate' => '選択したQSLカードを生成', 'select_all' => 'すべて選択', 'select_none' => '選択を解除', 'all_bands' => 'すべてのバンド', 'all_modes' => 'すべてのモード', 'adif_processing' => 'ADIFファイルを処理中...', 'adif_import_error' => 'ADIFの取り込みに失敗しました。', 'nav_design_help' => '画像背景、単色、グラデーション、またはすぐ使えるパレットを追加できます。', 'nav_create_help' => '手入力でQSLを作成するか、ADIFファイルをドラッグ＆ドロップで取り込みます。', 'err_select_bg' => '背景画像を選択してください。', 'ok_bg_image' => '画像背景を保存しました。', 'err_gradient_invalid' => 'グラデーションの色が無効です。', 'ok_bg_gradient' => 'グラデーション背景を保存しました。', 'err_solid_invalid' => '単色が無効です。', 'ok_bg_solid' => '単色背景を保存しました。', 'err_palette_invalid' => 'プリセットパレットが無効です。', 'ok_bg_palette' => 'プリセットパレットを保存しました。', 'ok_bg_default' => '既定の背景を更新しました。', 'ok_bg_deleted' => '背景を削除しました。', 'err_no_adif' => 'ADIFファイルを受信していません。', 'err_no_valid_adif' => '有効なADIFファイルを処理できませんでした。', 'ok_qso_imported' => 'ADIFファイルからQSOを取り込みました。', 'err_qso_none' => '新しいQSOは取り込まれませんでした。', 'ok_qsl_generated' => 'QSLカードを生成しました。', 'err_qsl_none' => 'QSLは生成されませんでした。選択が空、またはカードが既に存在します。', 'ok_qsl_created' => 'QSLを作成しました。', 'ok_qso_deleted' => 'QSOを削除しました。', 'ok_qsl_deleted' => 'QSLを削除しました。', 'err_unknown_action' => '不明なQSL操作です。', 'label_bg_image' => '画像背景', 'label_gradient' => '2色グラデーション', 'label_delete' => '削除', 'empty_qso' => '取り込み済みQSOはまだありません。', 'empty_qso_filtered' => '有効なフィルターに一致するQSOはありません。', 'empty_qsl' => '生成済みQSLはまだありません。', 'empty_qsl_filtered' => '検索に一致するQSLはありません。', 'nav_manage_help' => 'QSOを絞り込み、一括生成し、表面/裏面カードを書き出します。', 'qso_search_ph' => 'コールサイン、日付、モードで絞り込み...', 'qsl_search_ph' => 'QSLを検索（タイトル、コール、バンド...）', 'preview_dynamic' => 'フォーム項目に基づく動的プレビュー。', 'adif_imported_status' => '{files}個のファイルから{imported}件のQSOを取り込みました。']),
    'ru' => array_replace($qslEnglishMessages, ['studio' => 'QSL Studio · просто, с подсказками, эффективно', 'studio_help' => 'Всё рассчитано на скорость: импортируйте QSO, создавайте карточки и экспортируйте без лишних шагов.', 'design' => '1) Настройте фон QSL', 'create' => '2) Легко создавайте QSL-карточки', 'manage' => '3) Импортированные QSO', 'generated' => 'Сгенерированные QSL-карточки', 'filter' => 'Фильтр', 'reset' => 'Сброс', 'page' => 'Страница', 'previous' => 'Назад', 'next' => 'Вперёд', 'nav_design' => '1 · Настроить дизайн', 'nav_create' => '2 · Создать / импортировать', 'nav_manage' => '3 · Управлять и экспортировать', 'bulk_generate' => 'Сгенерировать выбранные QSL-карточки', 'select_all' => 'Выбрать всё', 'select_none' => 'Снять выбор', 'all_bands' => 'Все диапазоны', 'all_modes' => 'Все режимы', 'adif_processing' => 'Обработка файлов ADIF...', 'adif_import_error' => 'Не удалось импортировать ADIF.', 'nav_design_help' => 'Добавьте фоновое изображение, сплошной цвет, градиент или готовую палитру.', 'nav_create_help' => 'Создайте QSL вручную или импортируйте файлы ADIF перетаскиванием.', 'err_select_bg' => 'Выберите фоновое изображение.', 'ok_bg_image' => 'Фоновое изображение сохранено.', 'err_gradient_invalid' => 'Недопустимые цвета градиента.', 'ok_bg_gradient' => 'Градиентный фон сохранён.', 'err_solid_invalid' => 'Недопустимый сплошной цвет.', 'ok_bg_solid' => 'Сплошной фон сохранён.', 'err_palette_invalid' => 'Недопустимая готовая палитра.', 'ok_bg_palette' => 'Готовая палитра сохранена.', 'ok_bg_default' => 'Фон по умолчанию обновлён.', 'ok_bg_deleted' => 'Фон удалён.', 'err_no_adif' => 'Файл ADIF не получен.', 'err_no_valid_adif' => 'Не удалось обработать ни один корректный файл ADIF.', 'ok_qso_imported' => 'QSO импортированы из файлов ADIF.', 'err_qso_none' => 'Новые QSO не импортированы.', 'ok_qsl_generated' => 'QSL-карточки сгенерированы.', 'err_qsl_none' => 'QSL не сгенерированы. Выбор пуст или карточки уже существуют.', 'ok_qsl_created' => 'QSL создана.', 'ok_qso_deleted' => 'QSO удалено.', 'ok_qsl_deleted' => 'QSL удалена.', 'err_unknown_action' => 'Неизвестное действие QSL.', 'label_bg_image' => 'Фоновое изображение', 'label_gradient' => 'Градиент из 2 цветов', 'label_delete' => 'Удалить', 'empty_qso' => 'Импортированных QSO пока нет.', 'empty_qso_filtered' => 'Нет QSO, соответствующих активным фильтрам.', 'empty_qsl' => 'Сгенерированных QSL пока нет.', 'empty_qsl_filtered' => 'Нет QSL, соответствующих поиску.', 'nav_manage_help' => 'Фильтруйте QSO, генерируйте пакетно и экспортируйте лицевую/оборотную стороны карточек.', 'qso_search_ph' => 'Фильтр по позывному, дате, режиму...', 'qsl_search_ph' => 'Поиск QSL (заголовок, позывной, диапазон...)', 'preview_dynamic' => 'Динамический предпросмотр по полям формы.', 'adif_imported_status' => '{imported} QSO импортировано из {files} файл(ов).']),
    'zh' => array_replace($qslEnglishMessages, ['studio' => 'QSL 工作室 · 简单、引导式、高效', 'studio_help' => '一切都为效率而设计：导入你的 QSO，创建卡片并顺畅导出。', 'design' => '1) 设计你的 QSL 背景', 'create' => '2) 轻松创建 QSL 卡片', 'manage' => '3) 已导入的 QSO', 'generated' => '已生成的 QSL 卡片', 'filter' => '筛选', 'reset' => '重置', 'page' => '页', 'previous' => '上一页', 'next' => '下一页', 'nav_design' => '1 · 自定义设计', 'nav_create' => '2 · 创建 / 导入', 'nav_manage' => '3 · 管理与导出', 'bulk_generate' => '生成所选 QSL 卡片', 'select_all' => '全选', 'select_none' => '取消全选', 'all_bands' => '所有波段', 'all_modes' => '所有模式', 'adif_processing' => '正在处理 ADIF 文件...', 'adif_import_error' => 'ADIF 导入失败。', 'nav_design_help' => '添加图片背景、纯色、渐变或可直接使用的调色板。', 'nav_create_help' => '手动创建 QSL，或通过拖放导入 ADIF 文件。', 'err_select_bg' => '请选择一张背景图片。', 'ok_bg_image' => '图片背景已保存。', 'err_gradient_invalid' => '渐变颜色无效。', 'ok_bg_gradient' => '渐变背景已保存。', 'err_solid_invalid' => '纯色无效。', 'ok_bg_solid' => '纯色背景已保存。', 'err_palette_invalid' => '预设调色板无效。', 'ok_bg_palette' => '预设调色板已保存。', 'ok_bg_default' => '默认背景已更新。', 'ok_bg_deleted' => '背景已删除。', 'err_no_adif' => '未收到 ADIF 文件。', 'err_no_valid_adif' => '无法处理任何有效的 ADIF 文件。', 'ok_qso_imported' => '已从 ADIF 文件导入 QSO。', 'err_qso_none' => '没有导入新的 QSO。', 'ok_qsl_generated' => 'QSL 卡片已生成。', 'err_qsl_none' => '未生成 QSL。选择为空或卡片已存在。', 'ok_qsl_created' => 'QSL 已创建。', 'ok_qso_deleted' => 'QSO 已删除。', 'ok_qsl_deleted' => 'QSL 已删除。', 'err_unknown_action' => '未知的 QSL 操作。', 'label_bg_image' => '图片背景', 'label_gradient' => '双色渐变', 'label_delete' => '删除', 'empty_qso' => '尚未导入 QSO。', 'empty_qso_filtered' => '没有 QSO 符合当前筛选条件。', 'empty_qsl' => '尚未生成 QSL。', 'empty_qsl_filtered' => '没有 QSL 符合你的搜索。', 'nav_manage_help' => '筛选 QSO，批量生成，并导出正反面卡片。', 'qso_search_ph' => '按呼号、日期、模式筛选...', 'qsl_search_ph' => '搜索 QSL（标题、呼号、波段...）', 'preview_dynamic' => '根据表单字段动态预览。', 'adif_imported_status' => '已从 {files} 个文件导入 {imported} 条 QSO。']),
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
                <div class="qsl-live-preview-wrap" data-qsl-manual-preview data-preview-note="<?= e($qt('preview_dynamic')) ?>">
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
                    <p class="help" data-manual-preview-note><?= e($qt('preview_dynamic')) ?></p>
                </div>
                <p><button class="button">Créer ma QSL</button></p>
            </form>
        </section>

        <section class="stack" data-qsl-assistant-panel="adif">
            <div>
                <span class="badge muted">Étape B</span>
                <h2>Import ADIF rapide</h2>
            </div>
            <form method="post" enctype="multipart/form-data" id="adif-dropzone-form" class="stack" data-adif-processing="<?= e($qt('adif_processing')) ?>" data-adif-import-error="<?= e($qt('adif_import_error')) ?>" data-adif-imported-status="<?= e($qt('adif_imported_status')) ?>">
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
<?php include __DIR__ . '/qsl_script.js.php'; ?>

<?php
echo render_layout((string) ob_get_clean(), 'QSL');
