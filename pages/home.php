<?php
declare(strict_types=1);

/** @var string $homeLocale */
$homeLocale = current_locale();
$homeMessages = [
    'fr' => [
        'quote_day' => 'Citation du jour',
        'ham_weather' => 'Météo radioamateur',
        'ham_weather_desc' => 'Recommandations calculées automatiquement selon votre localisation, l\'heure, la météo et la propagation pour identifier les bandes et modes les plus propices aux QSO.',
        'quote_fallback' => 'Chaque contact radio est une nouvelle aventure.',
        'cta_member_area' => 'Accéder à mon espace membre',
        'cta_join_club' => 'Rejoindre le club',
        'cta_newsletter' => 'S\'inscrire à la newsletter',
        'useful_info' => 'Informations utiles',
        'meetings_info' => 'Nos réunions se déroulent le 3e samedi du mois à partir de 14h.',
        'maps_route' => 'Itinéraire Google Maps',
        'open' => 'Ouvrir',
        'public_updating' => 'Les espaces publics sont en cours de mise à jour.',
        'no_news' => 'Aucune actualité publiée pour le moment.',
        'published_on' => 'Publié le',
        'read_news' => 'Lire cette actualité',
        'news_fallback' => 'Consultez la dernière actualité du club.',
        'no_event' => 'Aucun événement planifié actuellement.',
        'event_date_tbd' => 'Date à confirmer',
        'event_fallback' => 'Découvrez les détails du prochain événement du club.',
        'next_date' => 'Prochaine date',
        'event_location' => 'Lieu',
        'view_event' => 'Voir l’événement',
        'weather_updated' => 'Mise à jour météo :',
        'weather_refresh' => 'Rafraîchir maintenant',
        'today_date' => 'Date :',
        'clocks_aria' => 'Horloges UTC et locale',
        'utc_time' => 'Heure UTC',
        'local_time' => 'Heure locale',
        'partner_ad_empty' => 'Aucune publicité partenaire disponible pour le moment.',
        'partner_ad_title' => 'Annonce partenaire',
        'ham_info_title' => 'Informations radioamateur',
        'vhf_voice_label' => 'Phonie VHF :',
        'good_practice_label' => 'Bon réflexe :',
        'vhf_voice_value' => '145.500 MHz (appel simplex régional)',
        'cw_qrp_label' => 'QRG CW QRP :',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz',
        'good_practice_value' => 'annoncer indicatif + QTH + trafic recherché',
        'map_title' => 'Carte Google Map - Radio Club Durnal',
        'address_title' => 'Adresse',
        'contact_people' => 'Personnes de contact',
        'club_spotlight_title' => 'À la une du club',
        'latest_news_title' => 'Dernière actualité',
        'next_event_title' => 'Prochain événement',
        'ad_title' => 'Publicité',
        'spotlight_tool_day' => "L'outil du jour",
        'spotlight_for_sale' => 'Petites annonces / Enchères',
        'spotlight_auction_live' => 'Rechercher',
        'spotlight_sub_1' => 'Petites annonces / Enchères',
        'spotlight_sub_2' => "L'outil du jour",
        'spotlight_sub_3' => 'Rechercher',
        'spotlight_sub_placeholder' => 'Contenu bientôt disponible…',
        'spotlight_tool_day_item' => 'Calcul de la grille depuis une adresse postale',
        'spotlight_tool_day_cta' => 'Ouvrir cet outil',
        'repeater_title' => 'Notre relais',
        'repeater_desc' => 'Retrouvez les informations essentielles concernant notre relai et ses paramètres.',
        'repeater_cta' => 'Consulter les informations du relais',
        'uba_title' => 'Union royale belge des amateurs-émetteurs a.s.b.l.',
        'uba_desc' => 'Le Radio Club Durnal est affilié à l\'Union Belge des Amateurs-Émetteurs.',
        'uba_cta' => 'Visiter le site de l\'UBA',
        'ibpt_title' => 'IBPT',
        'ibpt_desc' => 'Informations officielles pour l\'utilisation privée de loisir des radioamateurs.',
        'ibpt_cta' => 'Consulter la page IBPT',
        'member_modules_title' => 'Modules accessibles aux membres',
        'member_modules_empty' => 'Aucun module membre disponible actuellement.',
        'member_audience' => 'Membres',
        'page_title' => 'Accueil',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'Publicité partenaire',
        'alt_hero_illustration' => 'Illustration ON4CRD',
        'alt_uba_logo' => 'Logo UBA',
        'alt_repeater_logo' => 'Logo du relais',
    ],
    'en' => [
        'quote_day' => 'Quote of the day',
        'ham_weather' => 'Ham radio weather',
        'ham_weather_desc' => 'Recommendations are computed automatically from your location, time of day, weather and propagation to identify the most favorable bands and modes for QSO.',
        'quote_fallback' => 'Every radio contact is a new adventure.',
        'cta_member_area' => 'Access my member area',
        'cta_join_club' => 'Join the club',
        'cta_newsletter' => 'Subscribe to the newsletter',
        'useful_info' => 'Useful information',
        'meetings_info' => 'Our meetings take place on the 3rd Saturday of each month from 2:00 PM.',
        'maps_route' => 'Google Maps directions',
        'open' => 'Open',
        'public_updating' => 'Public areas are currently being updated.',
        'no_news' => 'No published news at the moment.',
        'published_on' => 'Published on',
        'read_news' => 'Read this update',
        'news_fallback' => 'Check out the club’s latest news update.',
        'no_event' => 'No event is currently scheduled.',
        'event_date_tbd' => 'Date to be confirmed',
        'event_fallback' => 'Discover details about the club’s next event.',
        'next_date' => 'Next date',
        'event_location' => 'Location',
        'view_event' => 'View event',
        'weather_updated' => 'Weather update:',
        'weather_refresh' => 'Refresh now',
        'today_date' => 'Date:',
        'clocks_aria' => 'UTC and local clocks',
        'utc_time' => 'UTC time',
        'local_time' => 'Local time',
        'partner_ad_empty' => 'No partner advertisement available at the moment.',
        'partner_ad_title' => 'Partner advertisement',
        'ham_info_title' => 'Ham radio information',
        'vhf_voice_label' => 'VHF voice:',
        'good_practice_label' => 'Good practice:',
        'vhf_voice_value' => '145.500 MHz (regional simplex calling)',
        'cw_qrp_label' => 'CW QRP frequencies:',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz',
        'good_practice_value' => 'announce callsign + QTH + requested traffic',
        'map_title' => 'Google Map - Radio Club Durnal',
        'address_title' => 'Address',
        'contact_people' => 'Contact persons',
        'club_spotlight_title' => 'Club highlights',
        'latest_news_title' => 'Latest news',
        'next_event_title' => 'Next event',
        'ad_title' => 'Advertisement',
        'spotlight_tool_day' => 'Tool of the day',
        'spotlight_for_sale' => 'Classifieds / Auctions',
        'spotlight_auction_live' => 'Search',
        'spotlight_sub_1' => 'Classifieds / Auctions',
        'spotlight_sub_2' => 'Tool of the day',
        'spotlight_sub_3' => 'Search',
        'spotlight_sub_placeholder' => 'Content coming soon.',
        'spotlight_tool_day_item' => 'Grid calculation from a postal address',
        'spotlight_tool_day_cta' => 'Open this tool',
        'repeater_title' => 'Our repeater',
        'repeater_desc' => 'Find key information about our repeater and settings.',
        'repeater_cta' => 'View repeater information',
        'uba_title' => 'Royal Belgian Amateur Radio Union',
        'uba_desc' => 'Radio Club Durnal is affiliated with the Belgian Amateur Radio Union.',
        'uba_cta' => 'Visit UBA website',
        'ibpt_title' => 'BIPT',
        'ibpt_desc' => 'Official information about private recreational use for amateur radio operators.',
        'ibpt_cta' => 'Open BIPT page',
        'member_modules_title' => 'Modules available to members',
        'member_modules_empty' => 'No member modules are currently available.',
        'member_audience' => 'Members',
        'page_title' => 'Home',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'Partner advertisement',
        'alt_hero_illustration' => 'ON4CRD illustration',
        'alt_uba_logo' => 'UBA logo',
        'alt_repeater_logo' => 'Repeater logo',
    ],
    'de' => [
        'quote_day' => 'Zitat des Tages',
        'ham_weather' => 'Funkwetter',
        'ham_weather_desc' => 'Die Empfehlungen werden automatisch aus Standort, Tageszeit, Wetter und Ausbreitung berechnet, um die besten Bänder und Betriebsarten für QSOs zu ermitteln.',
        'quote_fallback' => 'Jeder Funkkontakt ist ein neues Abenteuer.',
        'cta_member_area' => 'Meinen Mitgliederbereich öffnen',
        'cta_join_club' => 'Dem Club beitreten',
        'cta_newsletter' => 'Newsletter abonnieren',
        'useful_info' => 'Nützliche Informationen',
        'meetings_info' => 'Unsere Treffen finden am 3. Samstag jedes Monats ab 14:00 Uhr statt.',
        'maps_route' => 'Google-Maps-Route',
        'open' => 'Öffnen',
        'public_updating' => 'Die öffentlichen Bereiche werden aktuell aktualisiert.',
        'no_news' => 'Derzeit sind keine Nachrichten veröffentlicht.',
        'published_on' => 'Veröffentlicht am',
        'read_news' => 'Diese Neuigkeit lesen',
        'news_fallback' => 'Lesen Sie die neuesten Neuigkeiten des Clubs.',
        'no_event' => 'Derzeit ist keine Veranstaltung geplant.',
        'event_date_tbd' => 'Datum wird noch bestätigt',
        'event_fallback' => 'Entdecken Sie die Details zur nächsten Clubveranstaltung.',
        'next_date' => 'Nächster Termin',
        'event_location' => 'Ort',
        'view_event' => 'Veranstaltung ansehen',
        'weather_updated' => 'Wetter aktualisiert:',
        'weather_refresh' => 'Jetzt aktualisieren',
        'today_date' => 'Datum:',
        'clocks_aria' => 'UTC- und Ortszeituhren',
        'utc_time' => 'UTC-Zeit',
        'local_time' => 'Ortszeit',
        'partner_ad_empty' => 'Derzeit ist keine Partnerwerbung verfügbar.',
        'partner_ad_title' => 'Partnerwerbung',
        'ham_info_title' => 'Funkamateur-Informationen',
        'vhf_voice_label' => 'VHF-Sprechfunk:',
        'good_practice_label' => 'Gute Praxis:',
        'vhf_voice_value' => '145.500 MHz (regionaler Simplex-Anruf)',
        'cw_qrp_label' => 'CW-QRP-Frequenzen:',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz',
        'good_practice_value' => 'Rufzeichen + QTH + gewünschter Verkehr ansagen',
        'map_title' => 'Google-Karte - Radio Club Durnal',
        'address_title' => 'Adresse',
        'contact_people' => 'Kontaktpersonen',
        'club_spotlight_title' => 'Im Fokus des Clubs',
        'latest_news_title' => 'Neueste Nachricht',
        'next_event_title' => 'Nächstes Ereignis',
        'ad_title' => 'Werbung',
        'spotlight_tool_day' => 'Werkzeug des Tages',
        'spotlight_for_sale' => 'Kleinanzeigen / Auktionen',
        'spotlight_auction_live' => 'Suchen',
        'spotlight_sub_1' => 'Kleinanzeigen / Auktionen',
        'spotlight_sub_2' => 'Werkzeug des Tages',
        'spotlight_sub_3' => 'Suchen',
        'spotlight_sub_placeholder' => 'Inhalte folgen in Kürze.',
        'spotlight_tool_day_item' => 'Grid-Berechnung aus einer Postadresse',
        'spotlight_tool_day_cta' => 'Werkzeug öffnen',
        'repeater_title' => 'Unser Relais',
        'repeater_desc' => 'Finden Sie die wichtigsten Informationen über unser Relais und seine Parameter.',
        'repeater_cta' => 'Relaisinformationen ansehen',
        'uba_title' => 'Königliche Belgische Union der Funkamateure (UBA)',
        'uba_desc' => 'Der Radio Club Durnal ist der belgischen Funkamateure-Union angeschlossen.',
        'uba_cta' => 'UBA-Website besuchen',
        'ibpt_title' => 'BIPT',
        'ibpt_desc' => 'Offizielle Informationen zur privaten Freizeitnutzung für Funkamateure.',
        'ibpt_cta' => 'BIPT-Seite öffnen',
        'member_modules_title' => 'Module für Mitglieder',
        'member_modules_empty' => 'Derzeit sind keine Mitgliedermodule verfügbar.',
        'member_audience' => 'Mitglieder',
        'page_title' => 'Startseite',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'Partnerwerbung',
        'alt_hero_illustration' => 'ON4CRD-Illustration',
        'alt_uba_logo' => 'UBA-Logo',
        'alt_repeater_logo' => 'Relais-Logo',
    ],
    'nl' => [
        'quote_day' => 'Quote van de dag',
        'ham_weather' => 'Zendweer voor radioamateurs',
        'ham_weather_desc' => 'Aanbevelingen worden automatisch berekend op basis van uw locatie, tijdstip, weer en propagatie om de beste banden en modes voor QSO te bepalen.',
        'quote_fallback' => 'Elk radiocontact is een nieuw avontuur.',
        'cta_member_area' => 'Mijn ledengedeelte openen',
        'cta_join_club' => 'Word lid van de club',
        'cta_newsletter' => 'Inschrijven op de nieuwsbrief',
        'useful_info' => 'Nuttige informatie',
        'meetings_info' => 'Onze bijeenkomsten vinden plaats op de 3e zaterdag van elke maand vanaf 14:00 uur.',
        'maps_route' => 'Route via Google Maps',
        'open' => 'Openen',
        'public_updating' => 'Openbare ruimtes worden momenteel bijgewerkt.',
        'no_news' => 'Momenteel is er geen gepubliceerd nieuws.',
        'published_on' => 'Gepubliceerd op',
        'read_news' => 'Lees dit nieuws',
        'news_fallback' => 'Bekijk het laatste nieuws van de club.',
        'no_event' => 'Er staat momenteel geen evenement gepland.',
        'event_date_tbd' => 'Datum wordt nog bevestigd',
        'event_fallback' => 'Ontdek de details van het volgende clubevenement.',
        'next_date' => 'Volgende datum',
        'event_location' => 'Locatie',
        'view_event' => 'Evenement bekijken',
        'weather_updated' => 'Weer bijgewerkt:',
        'weather_refresh' => 'Nu verversen',
        'today_date' => 'Datum:',
        'clocks_aria' => 'UTC- en lokale klokken',
        'utc_time' => 'UTC-tijd',
        'local_time' => 'Lokale tijd',
        'partner_ad_empty' => 'Er is momenteel geen partneradvertentie beschikbaar.',
        'partner_ad_title' => 'Partneradvertentie',
        'ham_info_title' => 'Radioamateurinformatie',
        'vhf_voice_label' => 'VHF-spraak:',
        'good_practice_label' => 'Goede reflex:',
        'vhf_voice_value' => '145.500 MHz (regionale simplex-oproep)',
        'cw_qrp_label' => 'CW QRP-frequenties:',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz',
        'good_practice_value' => 'roepnaam + QTH + gewenst verkeer aankondigen',
        'map_title' => 'Google Map - Radio Club Durnal',
        'address_title' => 'Adres',
        'contact_people' => 'Contactpersonen',
        'club_spotlight_title' => 'In de kijker van de club',
        'latest_news_title' => 'Laatste nieuws',
        'next_event_title' => 'Volgend evenement',
        'ad_title' => 'Advertentie',
        'spotlight_tool_day' => 'Tool van de dag',
        'spotlight_for_sale' => 'Kleine advertenties / Veilingen',
        'spotlight_auction_live' => 'Zoeken',
        'spotlight_sub_1' => 'Kleine advertenties / Veilingen',
        'spotlight_sub_2' => 'Tool van de dag',
        'spotlight_sub_3' => 'Zoeken',
        'spotlight_sub_placeholder' => 'Inhoud binnenkort beschikbaar.',
        'spotlight_tool_day_item' => 'Grid berekenen vanaf een postadres',
        'spotlight_tool_day_cta' => 'Tool openen',
        'repeater_title' => 'Ons relais',
        'repeater_desc' => 'Vind de belangrijkste informatie over ons relais en zijn parameters.',
        'repeater_cta' => 'Bekijk relaisinformatie',
        'uba_title' => 'Koninklijke Unie van Belgische Zendamateurs (UBA)',
        'uba_desc' => 'Radio Club Durnal is aangesloten bij de Belgische Zendamateurunie.',
        'uba_cta' => 'Bezoek de UBA-website',
        'ibpt_title' => 'BIPT',
        'ibpt_desc' => 'Officiële informatie over privé vrijetijdsgebruik voor radioamateurs.',
        'ibpt_cta' => 'BIPT-pagina openen',
        'member_modules_title' => 'Modules voor leden',
        'member_modules_empty' => 'Er zijn momenteel geen ledenmodules beschikbaar.',
        'member_audience' => 'Leden',
        'page_title' => 'Startpagina',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'Partneradvertentie',
        'alt_hero_illustration' => 'ON4CRD-illustratie',
        'alt_uba_logo' => 'UBA-logo',
        'alt_repeater_logo' => 'Relaislogo',
    ],
    'es' => [
        'quote_day' => 'Cita del día',
        'ham_weather' => 'Meteorología para radioaficionados',
        'ham_weather_desc' => 'Las recomendaciones se calculan automáticamente según su ubicación, hora, meteorología y propagación para identificar las bandas y modos más favorables para QSO.',
        'quote_fallback' => 'Cada contacto de radio es una nueva aventura.',
        'cta_member_area' => 'Acceder a mi espacio de socio',
        'cta_join_club' => 'Unirse al club',
        'cta_newsletter' => 'Suscribirse al boletín',
        'useful_info' => 'Información útil',
        'meetings_info' => 'Nuestras reuniones se celebran el 3.º sábado de cada mes a partir de las 14:00.',
        'maps_route' => 'Ruta en Google Maps',
        'open' => 'Abrir',
        'public_updating' => 'Las áreas públicas se están actualizando.',
        'no_news' => 'No hay noticias publicadas por el momento.',
        'published_on' => 'Publicado el',
        'read_news' => 'Leer esta noticia',
        'news_fallback' => 'Consulte la última noticia del club.',
        'no_event' => 'No hay ningún evento programado por ahora.',
        'event_date_tbd' => 'Fecha pendiente de confirmación',
        'event_fallback' => 'Descubra los detalles del próximo evento del club.',
        'next_date' => 'Próxima fecha',
        'event_location' => 'Lugar',
        'view_event' => 'Ver evento',
        'weather_updated' => 'Meteorología actualizada:',
        'weather_refresh' => 'Actualizar ahora',
        'today_date' => 'Fecha:',
        'clocks_aria' => 'Relojes UTC y local',
        'utc_time' => 'Hora UTC',
        'local_time' => 'Hora local',
        'partner_ad_empty' => 'No hay publicidad de socios disponible por el momento.',
        'partner_ad_title' => 'Publicidad de socio',
        'ham_info_title' => 'Información de radioafición',
        'vhf_voice_label' => 'VHF fonía:',
        'good_practice_label' => 'Buena práctica:',
        'vhf_voice_value' => '145.500 MHz (llamada simplex regional)',
        'cw_qrp_label' => 'Frecuencias CW QRP:',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz',
        'good_practice_value' => 'anunciar indicativo + QTH + tráfico solicitado',
        'map_title' => 'Google Map - Radio Club Durnal',
        'address_title' => 'Dirección',
        'contact_people' => 'Personas de contacto',
        'club_spotlight_title' => 'Destacados del club',
        'latest_news_title' => 'Última noticia',
        'next_event_title' => 'Próximo evento',
        'ad_title' => 'Publicidad',
        'spotlight_tool_day' => 'Herramienta del día',
        'spotlight_for_sale' => 'Tienda / Subastas',
        'spotlight_auction_live' => 'Buscar',
        'spotlight_sub_1' => 'Tienda / Subastas',
        'spotlight_sub_2' => 'Herramienta del día',
        'spotlight_sub_3' => 'Buscar',
        'spotlight_sub_placeholder' => 'Contenido disponible próximamente.',
        'spotlight_tool_day_item' => 'Cálculo de grid desde una dirección postal',
        'spotlight_tool_day_cta' => 'Abrir esta herramienta',
        'repeater_title' => 'Nuestro repetidor',
        'repeater_desc' => 'Encuentre la información esencial sobre nuestro repetidor y sus parámetros.',
        'repeater_cta' => 'Ver información del repetidor',
        'uba_title' => 'Unión Real Belga de Radioaficionados',
        'uba_desc' => 'Radio Club Durnal está afiliado a la Unión Belga de Radioaficionados.',
        'uba_cta' => 'Visitar el sitio de UBA',
        'ibpt_title' => 'IBPT',
        'ibpt_desc' => 'Información oficial para el uso privado recreativo de la radioafición.',
        'ibpt_cta' => 'Abrir la página del IBPT',
        'member_modules_title' => 'Módulos accesibles para socios',
        'member_modules_empty' => 'No hay módulos de socios disponibles actualmente.',
        'member_audience' => 'Socios',
        'page_title' => 'Inicio',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'Publicidad de socio',
        'alt_hero_illustration' => 'Ilustración ON4CRD',
        'alt_uba_logo' => 'Logotipo UBA',
        'alt_repeater_logo' => 'Logotipo del repetidor',
    ],
    'it' => [
        'quote_day' => 'Citazione del giorno',
        'ham_weather' => 'Meteorologia radioamatoriale',
        'ham_weather_desc' => 'Le raccomandazioni sono calcolate automaticamente in base a posizione, orario, meteo e propagazione per identificare le bande e i modi più favorevoli ai QSO.',
        'quote_fallback' => 'Ogni contatto radio è una nuova avventura.',
        'cta_member_area' => 'Accedi alla mia area soci',
        'cta_join_club' => 'Unisciti al club',
        'cta_newsletter' => 'Iscriviti alla newsletter',
        'useful_info' => 'Informazioni utili',
        'meetings_info' => 'I nostri incontri si svolgono il 3° sabato del mese dalle 14:00.',
        'maps_route' => 'Itinerario Google Maps',
        'open' => 'Apri',
        'public_updating' => 'Le aree pubbliche sono in aggiornamento.',
        'no_news' => 'Nessuna notizia pubblicata al momento.',
        'published_on' => 'Pubblicato il',
        'read_news' => 'Leggi questa notizia',
        'news_fallback' => 'Consulta l’ultima notizia del club.',
        'no_event' => 'Nessun evento programmato al momento.',
        'event_date_tbd' => 'Data da confermare',
        'event_fallback' => 'Scopri i dettagli del prossimo evento del club.',
        'next_date' => 'Prossima data',
        'event_location' => 'Luogo',
        'view_event' => 'Vedi evento',
        'weather_updated' => 'Meteorologia aggiornata:',
        'weather_refresh' => 'Aggiorna ora',
        'today_date' => 'Data:',
        'clocks_aria' => 'Orologi UTC e locale',
        'utc_time' => 'Ora UTC',
        'local_time' => 'Ora locale',
        'partner_ad_empty' => 'Nessuna pubblicità partner disponibile al momento.',
        'partner_ad_title' => 'Pubblicità partner',
        'ham_info_title' => 'Informazioni radioamatoriali',
        'vhf_voice_label' => 'Fonìa VHF:',
        'good_practice_label' => 'Buona pratica:',
        'vhf_voice_value' => '145.500 MHz (chiamata simplex regionale)',
        'cw_qrp_label' => 'Frequenze CW QRP:',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz',
        'good_practice_value' => 'annunciare nominativo + QTH + traffico richiesto',
        'map_title' => 'Google Map - Radio Club Durnal',
        'address_title' => 'Indirizzo',
        'contact_people' => 'Persone di contatto',
        'club_spotlight_title' => 'In evidenza del club',
        'latest_news_title' => 'Ultima notizia',
        'next_event_title' => 'Prossimo evento',
        'ad_title' => 'Pubblicità',
        'spotlight_tool_day' => 'Strumento del giorno',
        'spotlight_for_sale' => 'Negozio / Aste',
        'spotlight_auction_live' => 'Cerca',
        'spotlight_sub_1' => 'Negozio / Aste',
        'spotlight_sub_2' => 'Strumento del giorno',
        'spotlight_sub_3' => 'Cerca',
        'spotlight_sub_placeholder' => 'Contenuto disponibile a breve.',
        'spotlight_tool_day_item' => 'Calcolo grid da un indirizzo postale',
        'spotlight_tool_day_cta' => 'Apri questo strumento',
        'repeater_title' => 'Il nostro ripetitore',
        'repeater_desc' => 'Trova le informazioni essenziali sul nostro ripetitore e i suoi parametri.',
        'repeater_cta' => 'Visualizza informazioni ripetitore',
        'uba_title' => 'Unione Reale Belga dei Radioamatori',
        'uba_desc' => 'Radio Club Durnal è affiliato all’Unione Belga dei Radioamatori.',
        'uba_cta' => 'Visita il sito UBA',
        'ibpt_title' => 'IBPT',
        'ibpt_desc' => 'Informazioni ufficiali sull’uso privato ricreativo per radioamatori.',
        'ibpt_cta' => 'Apri la pagina IBPT',
        'member_modules_title' => 'Moduli accessibili ai soci',
        'member_modules_empty' => 'Nessun modulo soci disponibile al momento.',
        'member_audience' => 'Soci',
        'page_title' => 'Home',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'Pubblicità partner',
        'alt_hero_illustration' => 'Illustrazione ON4CRD',
        'alt_uba_logo' => 'Logo UBA',
        'alt_repeater_logo' => 'Logo del ripetitore',
    ],
    'pt' => [
        'quote_day' => 'Citação do dia',
        'ham_weather' => 'Meteorologia radioamador',
        'ham_weather_desc' => 'As recomendações são calculadas automaticamente com base na sua localização, hora, meteorologia e propagação para identificar as bandas e modos mais favoráveis para QSO.',
        'quote_fallback' => 'Cada contacto de rádio é uma nova aventura.',
        'cta_member_area' => 'Aceder à minha área de membro',
        'cta_join_club' => 'Juntar-se ao clube',
        'cta_newsletter' => 'Subscrever a newsletter',
        'useful_info' => 'Informações úteis',
        'meetings_info' => 'As nossas reuniões decorrem no 3.º sábado de cada mês a partir das 14h00.',
        'maps_route' => 'Rota no Google Maps',
        'open' => 'Abrir',
        'public_updating' => 'As áreas públicas estão em atualização.',
        'no_news' => 'Não há notícias publicadas neste momento.',
        'published_on' => 'Publicado em',
        'read_news' => 'Ler esta notícia',
        'news_fallback' => 'Consulte a notícia mais recente do clube.',
        'no_event' => 'Não há evento agendado de momento.',
        'event_date_tbd' => 'Data por confirmar',
        'event_fallback' => 'Descubra os detalhes do próximo evento do clube.',
        'next_date' => 'Próxima data',
        'event_location' => 'Local',
        'view_event' => 'Ver evento',
        'weather_updated' => 'Meteorologia atualizada:',
        'weather_refresh' => 'Atualizar agora',
        'today_date' => 'Data:',
        'clocks_aria' => 'Relógios UTC e local',
        'utc_time' => 'Hora UTC',
        'local_time' => 'Hora local',
        'partner_ad_empty' => 'Não há publicidade de parceiro disponível neste momento.',
        'partner_ad_title' => 'Publicidade de parceiro',
        'ham_info_title' => 'Informação radioamador',
        'vhf_voice_label' => 'VHF fonia:',
        'good_practice_label' => 'Boa prática:',
        'vhf_voice_value' => '145.500 MHz (chamada simplex regional)',
        'cw_qrp_label' => 'Frequências CW QRP:',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz',
        'good_practice_value' => 'anunciar indicativo + QTH + tráfego pretendido',
        'map_title' => 'Peta Google - Radio Club Durnal',
        'address_title' => 'Endereço',
        'contact_people' => 'Pessoas de contacto',
        'club_spotlight_title' => 'Destaques do clube',
        'latest_news_title' => 'Última notícia',
        'next_event_title' => 'Próximo evento',
        'ad_title' => 'Publicidade',
        'spotlight_tool_day' => 'Ferramenta do dia',
        'spotlight_for_sale' => 'Classificados / Leilões',
        'spotlight_auction_live' => 'Pesquisar',
        'spotlight_sub_1' => 'Classificados / Leilões',
        'spotlight_sub_2' => 'Ferramenta do dia',
        'spotlight_sub_3' => 'Pesquisar',
        'spotlight_sub_placeholder' => 'Conteúdo disponível em breve.',
        'spotlight_tool_day_item' => 'Cálculo de grid a partir de um endereço postal',
        'spotlight_tool_day_cta' => 'Abrir esta ferramenta',
        'repeater_title' => 'O nosso repetidor',
        'repeater_desc' => 'Encontre as informações essenciais sobre o nosso repetidor e os seus parâmetros.',
        'repeater_cta' => 'Ver informações do repetidor',
        'uba_title' => 'União Real Belga de Radioamadores',
        'uba_desc' => 'O Radio Club Durnal é afiliado à União Belga de Radioamadores.',
        'uba_cta' => 'Visitar o site da UBA',
        'ibpt_title' => 'IBPT',
        'ibpt_desc' => 'Informações oficiais para a utilização privada de lazer por radioamadores.',
        'ibpt_cta' => 'Abrir página do IBPT',
        'member_modules_title' => 'Módulos acessíveis aos membros',
        'member_modules_empty' => 'Não há módulos de membro disponíveis neste momento.',
        'member_audience' => 'Membros',
        'page_title' => 'Início',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'Publicidade de parceiro',
        'alt_hero_illustration' => 'Ilustração ON4CRD',
        'alt_uba_logo' => 'Logótipo UBA',
        'alt_repeater_logo' => 'Logótipo do repetidor',
    ],
    'ar' => [
        'quote_day' => 'اقتباس اليوم',
        'cta_member_area' => 'الدخول إلى مساحة الأعضاء',
        'cta_join_club' => 'انضم إلى النادي',
        'cta_newsletter' => 'الاشتراك في النشرة',
        'useful_info' => 'معلومات مفيدة',
        'open' => 'فتح',
        'latest_news_title' => 'آخر الأخبار',
        'next_event_title' => 'الحدث القادم',
        'repeater_title' => 'مكررنا',
        'ham_weather' => 'طقس هواة الراديو',
        'quote_fallback' => 'كل اتصال لاسلكي مغامرة جديدة.',
        'maps_route' => 'اتجاهات خرائط Google',
        'public_updating' => 'يجري تحديث المساحات العامة حالياً.',
        'read_news' => 'اقرأ هذا الخبر',
        'partner_ad_title' => 'إعلان شريك',
        'partner_ad_empty' => 'لا يوجد إعلان شريك متاح حالياً.',
        'ham_info_title' => 'معلومات هواة الراديو',
        'map_title' => 'خريطة Google - Radio Club Durnal',
        'next_date' => 'التاريخ القادم',
        'clocks_aria' => 'ساعات UTC والمحلية',
        'spotlight_tool_day' => 'أداة اليوم',
        'spotlight_sub_2' => 'أداة اليوم',
        'spotlight_sub_placeholder' => 'المحتوى متاح قريباً.',
        'spotlight_tool_day_cta' => 'فتح هذه الأداة',
        'vhf_voice_label' => 'الصوت VHF:',
        'good_practice_label' => 'ممارسة جيدة:',
        'cw_qrp_label' => 'ترددات CW QRP:',
        'contact_people' => 'جهات الاتصال',
        'ad_title' => 'إعلان',
        'spotlight_for_sale' => 'إعلانات مبوبة / مزادات',
        'spotlight_auction_live' => 'بحث',
        'club_spotlight_title' => 'أبرز ما في النادي',
        'news_fallback' => 'اطّلع على آخر أخبار النادي.',
        'event_fallback' => 'اكتشف تفاصيل الحدث القادم للنادي.',
        'repeater_desc' => 'اعثر على المعلومات الأساسية حول مكررنا وإعداداته.',
        'repeater_cta' => 'عرض معلومات المكرر',
        'uba_cta' => 'زيارة موقع UBA',
        'ibpt_cta' => 'فتح صفحة IBPT',
        'ham_weather_desc' => 'يتم حساب التوصيات تلقائياً حسب موقعك والوقت والطقس والانتشار لتحديد أفضل النطاقات والأنماط لإجراء QSO.',
        'meetings_info' => 'تُعقد اجتماعاتنا يوم السبت الثالث من كل شهر ابتداءً من الساعة 14:00.',
        'weather_refresh' => 'تحديث الآن',
        'today_date' => 'التاريخ:',
        'vhf_voice_value' => '145.500 MHz (نداء بسيط إقليمي)',
        'good_practice_value' => 'أعلن النداء + QTH + نوع الاتصال المطلوب',
        'spotlight_tool_day_item' => 'حساب الشبكة انطلاقاً من عنوان بريدي',
        'address_title' => 'العنوان',
        'utc_time' => 'توقيت UTC',
        'local_time' => 'التوقيت المحلي',
        'event_location' => 'الموقع',
        'published_on' => 'نُشر في',
        'weather_updated' => 'تحديث الطقس:',
        'no_news' => 'لا توجد أخبار منشورة حالياً.',
        'no_event' => 'لا توجد فعاليات مجدولة حالياً.',
        'event_date_tbd' => 'سيتم تأكيد التاريخ',
        'member_modules_title' => 'الوحدات المتاحة للأعضاء',
        'member_modules_empty' => 'لا توجد وحدات أعضاء متاحة حالياً.',
        'member_audience' => 'الأعضاء',
        'page_title' => 'الرئيسية',
        'view_event' => 'عرض الحدث',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz (ترددات CW QRP)',
        'spotlight_sub_1' => 'إعلانات مبوبة / مزادات',
        'spotlight_sub_3' => 'بحث',
        'uba_title' => 'الاتحاد الملكي البلجيكي لهواة الراديو',
        'uba_desc' => 'Radio Club Durnal منتسب إلى الاتحاد البلجيكي لهواة الراديو.',
        'ibpt_title' => 'المعهد البلجيكي للخدمات البريدية والاتصالات',
        'ibpt_desc' => 'معلومات رسمية حول الاستخدام الترفيهي الخاص لمشغلي راديو الهواة.',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'إعلان شريك',
        'alt_hero_illustration' => 'رسم ON4CRD',
        'alt_uba_logo' => 'شعار UBA',
        'alt_repeater_logo' => 'شعار المكرّر',
    ],
    'bn' => [
        'quote_day' => 'দিনের উক্তি',
        'cta_member_area' => 'আমার সদস্য এলাকায় যান',
        'cta_join_club' => 'ক্লাবে যোগ দিন',
        'cta_newsletter' => 'নিউজলেটারে সাবস্ক্রাইব করুন',
        'useful_info' => 'উপকারী তথ্য',
        'open' => 'খুলুন',
        'latest_news_title' => 'সর্বশেষ সংবাদ',
        'next_event_title' => 'পরবর্তী ইভেন্ট',
        'repeater_title' => 'আমাদের রিপিটার',
        'ham_weather' => 'রেডিও অপেশাদার আবহাওয়া',
        'quote_fallback' => 'প্রতিটি রেডিও যোগাযোগ একটি নতুন অভিযান।',
        'maps_route' => 'Google Maps নির্দেশনা',
        'public_updating' => 'পাবলিক অংশ বর্তমানে আপডেট করা হচ্ছে।',
        'read_news' => 'এই সংবাদ পড়ুন',
        'partner_ad_title' => 'পার্টনার বিজ্ঞাপন',
        'partner_ad_empty' => 'এই মুহূর্তে কোনো পার্টনার বিজ্ঞাপন নেই।',
        'ham_info_title' => 'রেডিও অপেশাদার তথ্য',
        'map_title' => 'Google মানচিত্র - Radio Club Durnal',
        'next_date' => 'পরবর্তী তারিখ',
        'clocks_aria' => 'UTC ও স্থানীয় ঘড়ি',
        'spotlight_tool_day' => 'আজকের টুল',
        'spotlight_sub_2' => 'আজকের টুল',
        'spotlight_sub_placeholder' => 'কনটেন্ট শিগগিরই আসছে।',
        'spotlight_tool_day_cta' => 'এই টুল খুলুন',
        'vhf_voice_label' => 'VHF ভয়েস:',
        'good_practice_label' => 'ভাল অনুশীলন:',
        'cw_qrp_label' => 'CW QRP ফ্রিকোয়েন্সি:',
        'contact_people' => 'যোগাযোগের ব্যক্তি',
        'ad_title' => 'বিজ্ঞাপন',
        'spotlight_for_sale' => 'ক্লাসিফাইড / নিলাম',
        'spotlight_auction_live' => 'অনুসন্ধান',
        'club_spotlight_title' => 'ক্লাবের সেরা অংশ',
        'news_fallback' => 'ক্লাবের সর্বশেষ সংবাদ দেখুন।',
        'event_fallback' => 'ক্লাবের পরবর্তী ইভেন্টের বিস্তারিত জানুন।',
        'repeater_desc' => 'আমাদের রিপিটার ও সেটিংস সম্পর্কে মূল তথ্য জানুন।',
        'repeater_cta' => 'রিপিটার তথ্য দেখুন',
        'uba_cta' => 'UBA ওয়েবসাইট দেখুন',
        'ibpt_cta' => 'IBPT পৃষ্ঠা খুলুন',
        'ham_weather_desc' => 'আপনার অবস্থান, সময়, আবহাওয়া ও প্রোপাগেশন অনুযায়ী QSO-এর জন্য সবচেয়ে উপযোগী ব্যান্ড ও মোড স্বয়ংক্রিয়ভাবে নির্ধারণ করা হয়।',
        'meetings_info' => 'আমাদের সভা প্রতি মাসের ৩য় শনিবার দুপুর ২:০০টা থেকে অনুষ্ঠিত হয়।',
        'weather_refresh' => 'এখন রিফ্রেশ করুন',
        'today_date' => 'তারিখ:',
        'vhf_voice_value' => '145.500 MHz (আঞ্চলিক সিমপ্লেক্স কলিং)',
        'good_practice_value' => 'কলসাইন + QTH + প্রয়োজনীয় ট্রাফিক ঘোষণা করুন',
        'spotlight_tool_day_item' => 'ডাক ঠিকানা থেকে গ্রিড গণনা',
        'address_title' => 'ঠিকানা',
        'utc_time' => 'UTC সময়',
        'local_time' => 'স্থানীয় সময়',
        'event_location' => 'স্থান',
        'published_on' => 'প্রকাশিত',
        'weather_updated' => 'আবহাওয়া আপডেট:',
        'no_news' => 'এই মুহূর্তে কোনো সংবাদ প্রকাশিত হয়নি।',
        'no_event' => 'এই মুহূর্তে কোনো ইভেন্ট নির্ধারিত নেই।',
        'event_date_tbd' => 'তারিখ পরে নিশ্চিত হবে',
        'member_modules_title' => 'সদস্যদের জন্য উপলব্ধ মডিউল',
        'member_modules_empty' => 'এই মুহূর্তে কোনো সদস্য মডিউল উপলব্ধ নেই।',
        'member_audience' => 'সদস্যরা',
        'page_title' => 'হোম',
        'view_event' => 'ইভেন্ট দেখুন',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz (CW QRP ফ্রিকোয়েন্সি)',
        'spotlight_sub_1' => 'ক্লাসিফাইড / নিলাম',
        'spotlight_sub_3' => 'অনুসন্ধান',
        'uba_title' => 'রয়্যাল বেলজিয়ান অ্যামেচার রেডিও ইউনিয়ন',
        'uba_desc' => 'Radio Club Durnal বেলজিয়ান অ্যামেচার রেডিও ইউনিয়নের সাথে সংযুক্ত।',
        'ibpt_title' => 'BIPT',
        'ibpt_desc' => 'অ্যামেচার রেডিও অপারেটরদের ব্যক্তিগত বিনোদনমূলক ব্যবহারের সরকারি তথ্য।',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'পার্টনার বিজ্ঞাপন',
        'alt_hero_illustration' => 'ON4CRD ইলাস্ট্রেশন',
        'alt_uba_logo' => 'UBA লোগো',
        'alt_repeater_logo' => 'রিপিটার লোগো',
    ],
    'hi' => [
        'quote_day' => 'आज का उद्धरण',
        'cta_member_area' => 'मेरे सदस्य क्षेत्र में जाएँ',
        'cta_join_club' => 'क्लब से जुड़ें',
        'cta_newsletter' => 'न्यूज़लेटर की सदस्यता लें',
        'useful_info' => 'उपयोगी जानकारी',
        'open' => 'खोलें',
        'latest_news_title' => 'ताज़ा समाचार',
        'next_event_title' => 'अगला कार्यक्रम',
        'repeater_title' => 'हमारा रिपीटर',
        'ham_weather' => 'शौकिया रेडियो मौसम',
        'quote_fallback' => 'हर रेडियो संपर्क एक नया रोमांच है।',
        'maps_route' => 'Google Maps मार्ग',
        'public_updating' => 'सार्वजनिक क्षेत्र अभी अपडेट किए जा रहे हैं।',
        'read_news' => 'यह समाचार पढ़ें',
        'partner_ad_title' => 'साझेदार विज्ञापन',
        'partner_ad_empty' => 'फिलहाल कोई साझेदार विज्ञापन उपलब्ध नहीं है।',
        'ham_info_title' => 'शौकिया रेडियो जानकारी',
        'map_title' => 'Google मानचित्र - Radio Club Durnal',
        'next_date' => 'अगली तारीख',
        'clocks_aria' => 'UTC और स्थानीय घड़ियाँ',
        'spotlight_tool_day' => 'आज का टूल',
        'spotlight_sub_2' => 'आज का टूल',
        'spotlight_sub_placeholder' => 'सामग्री जल्द उपलब्ध होगी।',
        'spotlight_tool_day_cta' => 'यह टूल खोलें',
        'vhf_voice_label' => 'VHF वॉइस:',
        'good_practice_label' => 'अच्छी प्रथा:',
        'cw_qrp_label' => 'CW QRP आवृत्तियाँ:',
        'contact_people' => 'संपर्क व्यक्ति',
        'ad_title' => 'विज्ञापन',
        'spotlight_for_sale' => 'वर्गीकृत / नीलामी',
        'spotlight_auction_live' => 'खोजें',
        'club_spotlight_title' => 'क्लब की झलकियाँ',
        'news_fallback' => 'क्लब की नवीनतम खबर देखें।',
        'event_fallback' => 'क्लब के अगले कार्यक्रम का विवरण देखें।',
        'repeater_desc' => 'हमारे रिपीटर और सेटिंग्स की मुख्य जानकारी देखें।',
        'repeater_cta' => 'रिपीटर जानकारी देखें',
        'uba_cta' => 'UBA वेबसाइट देखें',
        'ibpt_cta' => 'IBPT पृष्ठ खोलें',
        'ham_weather_desc' => 'आपके स्थान, समय, मौसम और प्रसार के आधार पर QSO के लिए सर्वोत्तम बैंड और मोड स्वतः निर्धारित किए जाते हैं।',
        'meetings_info' => 'हमारी बैठकें हर महीने के तीसरे शनिवार को दोपहर 2:00 बजे से होती हैं।',
        'weather_refresh' => 'अभी ताज़ा करें',
        'today_date' => 'तारीख:',
        'vhf_voice_value' => '145.500 MHz (क्षेत्रीय सिंप्लेक्स कॉलिंग)',
        'good_practice_value' => 'कॉलसाइन + QTH + अपेक्षित ट्रैफिक घोषित करें',
        'spotlight_tool_day_item' => 'डाक पते से ग्रिड गणना',
        'address_title' => 'पता',
        'utc_time' => 'UTC समय',
        'local_time' => 'स्थानीय समय',
        'event_location' => 'स्थान',
        'published_on' => 'प्रकाशित',
        'weather_updated' => 'मौसम अपडेट:',
        'no_news' => 'फिलहाल कोई समाचार प्रकाशित नहीं है।',
        'no_event' => 'फिलहाल कोई कार्यक्रम निर्धारित नहीं है।',
        'event_date_tbd' => 'तारीख़ की पुष्टि शेष',
        'member_modules_title' => 'सदस्यों के लिए उपलब्ध मॉड्यूल',
        'member_modules_empty' => 'इस समय कोई सदस्य मॉड्यूल उपलब्ध नहीं है।',
        'member_audience' => 'सदस्य',
        'page_title' => 'होम',
        'view_event' => 'कार्यक्रम देखें',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz (CW QRP आवृत्तियाँ)',
        'spotlight_sub_1' => 'वर्गीकृत विज्ञापन / नीलामी',
        'spotlight_sub_3' => 'खोज',
        'uba_title' => 'रॉयल बेल्जियन एमेच्योर रेडियो यूनियन',
        'uba_desc' => 'Radio Club Durnal बेल्जियन एमेच्योर रेडियो यूनियन से संबद्ध है।',
        'ibpt_title' => 'BIPT',
        'ibpt_desc' => 'एमेच्योर रेडियो ऑपरेटरों के निजी मनोरंजक उपयोग के बारे में आधिकारिक जानकारी।',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'साझेदार विज्ञापन',
        'alt_hero_illustration' => 'ON4CRD चित्रण',
        'alt_uba_logo' => 'UBA लोगो',
        'alt_repeater_logo' => 'रीपीटर लोगो',
    ],
    'id' => [
        'quote_day' => 'Kutipan hari ini',
        'cta_member_area' => 'Akses area anggota saya',
        'cta_join_club' => 'Bergabung dengan klub',
        'cta_newsletter' => 'Berlangganan newsletter',
        'useful_info' => 'Informasi berguna',
        'open' => 'Buka',
        'latest_news_title' => 'Berita terbaru',
        'next_event_title' => 'Acara berikutnya',
        'repeater_title' => 'Repeater kami',
        'ham_weather' => 'Cuaca radio amatir',
        'quote_fallback' => 'Setiap kontak radio adalah petualangan baru.',
        'maps_route' => 'Rute Google Maps',
        'public_updating' => 'Area publik sedang diperbarui.',
        'read_news' => 'Baca berita ini',
        'partner_ad_title' => 'Iklan mitra',
        'partner_ad_empty' => 'Saat ini tidak ada iklan mitra yang tersedia.',
        'ham_info_title' => 'Informasi radio amatir',
        'map_title' => 'Peta Google - Radio Club Durnal',
        'next_date' => 'Tanggal berikutnya',
        'clocks_aria' => 'Jam UTC dan lokal',
        'spotlight_tool_day' => 'Alat hari ini',
        'spotlight_sub_2' => 'Alat hari ini',
        'spotlight_sub_placeholder' => 'Konten akan segera tersedia.',
        'spotlight_tool_day_cta' => 'Buka alat ini',
        'vhf_voice_label' => 'Suara VHF:',
        'good_practice_label' => 'Praktik baik:',
        'cw_qrp_label' => 'Frekuensi CW QRP:',
        'contact_people' => 'Narahubung',
        'ad_title' => 'Iklan',
        'spotlight_for_sale' => 'Iklan baris / Lelang',
        'spotlight_auction_live' => 'Cari',
        'club_spotlight_title' => 'Sorotan klub',
        'news_fallback' => 'Lihat berita terbaru dari klub.',
        'event_fallback' => 'Temukan detail acara klub berikutnya.',
        'view_event' => 'Lihat acara',
        'repeater_desc' => 'Temukan informasi penting tentang repeater dan pengaturan kami.',
        'repeater_cta' => 'Lihat informasi repeater',
        'uba_cta' => 'Kunjungi situs UBA',
        'ibpt_cta' => 'Buka halaman IBPT',
        'ham_weather_desc' => 'Rekomendasi dihitung otomatis dari lokasi, waktu, cuaca, dan propagasi untuk menentukan band serta mode QSO yang paling sesuai.',
        'meetings_info' => 'Pertemuan kami berlangsung setiap Sabtu ke-3 tiap bulan mulai pukul 14.00.',
        'weather_refresh' => 'Segarkan sekarang',
        'today_date' => 'Tanggal:',
        'vhf_voice_value' => '145.500 MHz (panggilan simplex regional)',
        'good_practice_value' => 'umumkan callsign + QTH + trafik yang diminta',
        'spotlight_tool_day_item' => 'Perhitungan grid dari alamat pos',
        'address_title' => 'Alamat',
        'utc_time' => 'Waktu UTC',
        'local_time' => 'Waktu lokal',
        'event_location' => 'Lokasi',
        'published_on' => 'Dipublikasikan pada',
        'weather_updated' => 'Pembaruan cuaca:',
        'no_news' => 'Belum ada berita yang dipublikasikan saat ini.',
        'no_event' => 'Belum ada acara yang dijadwalkan saat ini.',
        'event_date_tbd' => 'Tanggal akan dikonfirmasi',
        'member_modules_title' => 'Modul yang tersedia untuk anggota',
        'member_modules_empty' => 'Saat ini tidak ada modul anggota yang tersedia.',
        'member_audience' => 'Anggota',
        'page_title' => 'Beranda',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz (frekuensi CW QRP)',
        'spotlight_sub_1' => 'Iklan baris / Lelang',
        'spotlight_sub_3' => 'Cari',
        'uba_title' => 'Uni Radio Amatir Kerajaan Belgia',
        'uba_desc' => 'Radio Club Durnal berafiliasi dengan Belgian Amateur Radio Union.',
        'ibpt_title' => 'BIPT',
        'ibpt_desc' => 'Informasi resmi tentang penggunaan rekreasi pribadi bagi operator radio amatir.',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'Iklan mitra',
        'alt_hero_illustration' => 'Ilustrasi ON4CRD',
        'alt_uba_logo' => 'Logo UBA',
        'alt_repeater_logo' => 'Logo repeater',
    ],
    'ja' => [
        'quote_day' => '今日の名言',
        'cta_member_area' => '会員エリアへ',
        'cta_join_club' => 'クラブに参加',
        'cta_newsletter' => 'ニュースレター登録',
        'useful_info' => '便利な情報',
        'open' => '開く',
        'latest_news_title' => '最新ニュース',
        'next_event_title' => '次のイベント',
        'repeater_title' => '私たちのレピーター',
        'ham_weather' => 'アマチュア無線天気',
        'quote_fallback' => 'すべての無線交信は新しい冒険です。',
        'maps_route' => 'Googleマップの経路',
        'public_updating' => '公開エリアは現在更新中です。',
        'read_news' => 'このニュースを読む',
        'partner_ad_title' => 'パートナー広告',
        'partner_ad_empty' => '現在利用可能なパートナー広告はありません。',
        'ham_info_title' => 'アマチュア無線情報',
        'map_title' => 'Googleマップ - Radio Club Durnal',
        'next_date' => '次の日程',
        'clocks_aria' => 'UTCと現地の時計',
        'spotlight_tool_day' => '今日のツール',
        'spotlight_sub_2' => '今日のツール',
        'spotlight_sub_placeholder' => 'コンテンツは近日公開予定です。',
        'spotlight_tool_day_cta' => 'このツールを開く',
        'vhf_voice_label' => 'VHF音声:',
        'good_practice_label' => '良い運用:',
        'cw_qrp_label' => 'CW QRP周波数:',
        'contact_people' => '連絡先',
        'ad_title' => '広告',
        'spotlight_for_sale' => 'クラシファイド / オークション',
        'spotlight_auction_live' => '検索',
        'club_spotlight_title' => 'クラブの注目情報',
        'news_fallback' => 'クラブの最新ニュースをご覧ください。',
        'event_fallback' => '次回クラブイベントの詳細をご確認ください。',
        'view_event' => 'イベントを見る',
        'repeater_desc' => '当クラブのレピーターと設定に関する重要情報を確認できます。',
        'repeater_cta' => 'レピーター情報を見る',
        'uba_cta' => 'UBAサイトを見る',
        'ibpt_cta' => 'IBPTページを開く',
        'ham_weather_desc' => '位置情報、時間帯、天候、伝搬状況から、QSOに最適なバンドとモードを自動で算出します。',
        'meetings_info' => '定例会は毎月第3土曜日14:00から開催しています。',
        'weather_refresh' => '今すぐ更新',
        'today_date' => '日付:',
        'vhf_voice_value' => '145.500 MHz（地域シンプレックス呼出）',
        'good_practice_value' => 'コールサイン + QTH + 希望トラフィックを告知',
        'spotlight_tool_day_item' => '住所からグリッドを計算',
        'address_title' => '住所',
        'utc_time' => 'UTC時刻',
        'local_time' => '現地時刻',
        'event_location' => '場所',
        'published_on' => '公開日',
        'weather_updated' => '天気更新:',
        'no_news' => '現在公開されているニュースはありません。',
        'no_event' => '現在予定されているイベントはありません。',
        'event_date_tbd' => '日程は未定',
        'member_modules_title' => 'メンバー向けモジュール',
        'member_modules_empty' => '現在利用可能なメンバーモジュールはありません。',
        'member_audience' => 'メンバー',
        'page_title' => 'ホーム',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz（CW QRP周波数）',
        'spotlight_sub_1' => 'クラシファイド / オークション',
        'spotlight_sub_3' => '検索',
        'uba_title' => 'ベルギー王立アマチュア無線連盟',
        'uba_desc' => 'Radio Club Durnalはベルギー・アマチュア無線連盟に加盟しています。',
        'ibpt_title' => 'BIPT',
        'ibpt_desc' => 'アマチュア無線家の私的レクリエーション利用に関する公式情報。',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'パートナー広告',
        'alt_hero_illustration' => 'ON4CRDイラスト',
        'alt_uba_logo' => 'UBAロゴ',
        'alt_repeater_logo' => 'レピーターロゴ',
    ],
    'ru' => [
        'quote_day' => 'Цитата дня',
        'cta_member_area' => 'Перейти в личный кабинет',
        'cta_join_club' => 'Вступить в клуб',
        'cta_newsletter' => 'Подписаться на рассылку',
        'useful_info' => 'Полезная информация',
        'open' => 'Открыть',
        'latest_news_title' => 'Последние новости',
        'next_event_title' => 'Следующее событие',
        'repeater_title' => 'Наш репитер',
        'ham_weather' => 'Погода для радиолюбителей',
        'quote_fallback' => 'Каждый радиоконтакт — новое приключение.',
        'maps_route' => 'Маршрут в Google Maps',
        'public_updating' => 'Публичные разделы сейчас обновляются.',
        'read_news' => 'Читать новость',
        'partner_ad_title' => 'Партнёрская реклама',
        'partner_ad_empty' => 'Сейчас партнёрская реклама недоступна.',
        'ham_info_title' => 'Информация для радиолюбителей',
        'map_title' => 'Google Карта - Radio Club Durnal',
        'next_date' => 'Следующая дата',
        'clocks_aria' => 'Часы UTC и местные',
        'spotlight_tool_day' => 'Инструмент дня',
        'spotlight_sub_2' => 'Инструмент дня',
        'spotlight_sub_placeholder' => 'Контент скоро появится.',
        'spotlight_tool_day_cta' => 'Открыть этот инструмент',
        'vhf_voice_label' => 'VHF голос:',
        'good_practice_label' => 'Хорошая практика:',
        'cw_qrp_label' => 'Частоты CW QRP:',
        'contact_people' => 'Контактные лица',
        'ad_title' => 'Реклама',
        'spotlight_for_sale' => 'Объявления / Аукционы',
        'spotlight_auction_live' => 'Поиск',
        'club_spotlight_title' => 'Главное в клубе',
        'news_fallback' => 'Посмотрите последние новости клуба.',
        'event_fallback' => 'Узнайте подробности следующего события клуба.',
        'view_event' => 'Смотреть событие',
        'repeater_desc' => 'Ключевая информация о нашем репитере и его параметрах.',
        'repeater_cta' => 'Смотреть информацию о репитере',
        'uba_cta' => 'Перейти на сайт UBA',
        'ibpt_cta' => 'Открыть страницу IBPT',
        'ham_weather_desc' => 'Рекомендации автоматически рассчитываются по вашему местоположению, времени, погоде и прохождению для выбора лучших диапазонов и режимов QSO.',
        'meetings_info' => 'Наши встречи проходят в 3-ю субботу каждого месяца, начиная с 14:00.',
        'weather_refresh' => 'Обновить сейчас',
        'today_date' => 'Дата:',
        'vhf_voice_value' => '145.500 MHz (региональный simplex-вызов)',
        'good_practice_value' => 'объявляйте позывной + QTH + запрашиваемый трафик',
        'spotlight_tool_day_item' => 'Расчёт грид-квадрата по почтовому адресу',
        'address_title' => 'Адрес',
        'utc_time' => 'Время UTC',
        'local_time' => 'Местное время',
        'event_location' => 'Место',
        'published_on' => 'Опубликовано',
        'weather_updated' => 'Обновление погоды:',
        'no_news' => 'Сейчас нет опубликованных новостей.',
        'no_event' => 'Сейчас нет запланированных событий.',
        'event_date_tbd' => 'Дата уточняется',
        'member_modules_title' => 'Модули, доступные участникам',
        'member_modules_empty' => 'Сейчас нет доступных модулей для участников.',
        'member_audience' => 'Участники',
        'page_title' => 'Главная',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz (частоты CW QRP)',
        'spotlight_sub_1' => 'Объявления / Аукционы',
        'spotlight_sub_3' => 'Поиск',
        'uba_title' => 'Королевский бельгийский союз радиолюбителей',
        'uba_desc' => 'Radio Club Durnal входит в Бельгийский союз радиолюбителей.',
        'ibpt_title' => 'BIPT',
        'ibpt_desc' => 'Официальная информация о частном любительском использовании для радиолюбителей.',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => 'Партнёрская реклама',
        'alt_hero_illustration' => 'Иллюстрация ON4CRD',
        'alt_uba_logo' => 'Логотип UBA',
        'alt_repeater_logo' => 'Логотип репитера',
    ],
    'zh' => [
        'quote_day' => '今日语录',
        'cta_member_area' => '进入我的会员空间',
        'cta_join_club' => '加入俱乐部',
        'cta_newsletter' => '订阅通讯',
        'useful_info' => '实用信息',
        'open' => '打开',
        'latest_news_title' => '最新消息',
        'next_event_title' => '下一个活动',
        'repeater_title' => '我们的中继台',
        'ham_weather' => '业余无线电天气',
        'quote_fallback' => '每一次无线电联络都是新的冒险。',
        'maps_route' => 'Google 地图路线',
        'public_updating' => '公共区域正在更新中。',
        'read_news' => '阅读此新闻',
        'partner_ad_title' => '合作伙伴广告',
        'partner_ad_empty' => '目前没有可用的合作伙伴广告。',
        'ham_info_title' => '业余无线电信息',
        'map_title' => 'Google 地图 - Radio Club Durnal',
        'next_date' => '下一日期',
        'clocks_aria' => 'UTC 与本地时钟',
        'spotlight_tool_day' => '今日工具',
        'spotlight_sub_2' => '今日工具',
        'spotlight_sub_placeholder' => '内容即将上线。',
        'spotlight_tool_day_cta' => '打开此工具',
        'vhf_voice_label' => 'VHF 语音：',
        'good_practice_label' => '良好实践：',
        'cw_qrp_label' => 'CW QRP 频率：',
        'contact_people' => '联系人',
        'ad_title' => '广告',
        'spotlight_for_sale' => '分类信息 / 拍卖',
        'spotlight_auction_live' => '搜索',
        'club_spotlight_title' => '俱乐部焦点',
        'news_fallback' => '查看俱乐部最新新闻。',
        'event_fallback' => '了解俱乐部下一场活动详情。',
        'view_event' => '查看活动',
        'repeater_desc' => '查看我们中继台及其参数的关键信息。',
        'repeater_cta' => '查看中继台信息',
        'uba_cta' => '访问 UBA 网站',
        'ibpt_cta' => '打开 IBPT 页面',
        'ham_weather_desc' => '系统会根据您的位置、时间、天气和传播情况自动计算建议，帮助选择最适合 QSO 的波段与模式。',
        'meetings_info' => '我们的会议在每月第3个星期六下午 14:00 开始举行。',
        'weather_refresh' => '立即刷新',
        'today_date' => '日期：',
        'vhf_voice_value' => '145.500 MHz（区域单工呼叫）',
        'good_practice_value' => '报出呼号 + QTH + 所需通信内容',
        'spotlight_tool_day_item' => '根据邮政地址计算网格',
        'address_title' => '地址',
        'utc_time' => 'UTC 时间',
        'local_time' => '本地时间',
        'event_location' => '地点',
        'published_on' => '发布于',
        'weather_updated' => '天气更新：',
        'no_news' => '目前暂无已发布新闻。',
        'no_event' => '目前暂无已安排活动。',
        'event_date_tbd' => '日期待确认',
        'member_modules_title' => '会员可用模块',
        'member_modules_empty' => '当前没有可用的会员模块。',
        'member_audience' => '会员',
        'page_title' => '首页',
        'cw_qrp_value' => '7.030 MHz • 14.060 MHz（CW QRP 频率）',
        'spotlight_sub_1' => '分类信息 / 拍卖',
        'spotlight_sub_3' => '搜索',
        'uba_title' => '比利时皇家业余无线电联盟',
        'uba_desc' => 'Radio Club Durnal 隶属于比利时业余无线电联盟。',
        'ibpt_title' => 'BIPT',
        'ibpt_desc' => '面向业余无线电操作员私人休闲使用的官方信息。',
        'club_name' => 'Radio Club Durnal',
        'venue_line_1' => 'Bocq Arena',
        'venue_line_2' => 'Rue des Écoles',
        'venue_line_3' => '5530 Purnode',
        'alt_partner_ad' => '合作伙伴广告',
        'alt_hero_illustration' => 'ON4CRD 插图',
        'alt_uba_logo' => 'UBA 标志',
        'alt_repeater_logo' => '中继台标志',
    ],

];
$homeEnglishFallbackLocales = ['ar', 'bn', 'hi', 'id', 'ja', 'ru', 'zh'];
foreach ($homeEnglishFallbackLocales as $localeCode) {
    if (!isset($homeMessages[$localeCode])) {
        $homeMessages[$localeCode] = [];
    }
    $homeMessages[$localeCode] = array_replace($homeMessages['en'], $homeMessages[$localeCode]);
}
$homeI18n = [];
foreach (array_keys($homeMessages['fr']) as $key) {
    $pool = [];
    foreach ($homeMessages as $lang => $messages) {
        if (isset($messages[$key]) && is_string($messages[$key])) {
            $pool[$lang] = $messages[$key];
        }
    }
    $value = trim(i18n_localized_value($pool, $homeLocale, 'fr'));
    if ($value === '') {
        $value = trim((string) ($homeMessages['fr'][$key] ?? ''));
    }
    $homeI18n[$key] = $value;
}
$homeExtraMessages = [
    'fr' => [
        'journalist_title' => 'Vous êtes journaliste',
        'journalist_desc' => 'Accédez directement à notre dossier de presse pour préparer vos publications et reportages.',
        'journalist_cta' => 'Consulter le dossier presse',
        'teacher_title' => 'Vous êtes enseignant',
        'teacher_desc' => 'Retrouvez nos dossiers pédagogiques pour vos activités scolaires et vos projets éducatifs.',
        'teacher_cta' => 'Voir les dossiers pédagogiques',
        'join_title' => 'Prêt à rejoindre une communauté radio active et structurée ?',
        'join_desc' => 'La nouvelle page d’accueil met en évidence les modules clés pour trouver rapidement l’information utile et participer aux projets ON4CRD.',
        'contact_title' => 'Nous contacter',
        'contact_name' => 'Nom',
        'contact_email' => 'E-mail',
        'contact_message' => 'Message',
        'contact_send' => 'Envoyer',
        'important_info_title' => 'Informations importantes',
        'link_terms' => "Conditions générales d'utilisation",
        'link_legal' => 'Mentions légales',
        'link_internal_rules' => "Règlement d'ordre intérieur",
        'link_donate' => 'Faire un don',
        'link_sponsoring' => 'Sponsoring',
        'link_code_q' => 'Code Q',
        'link_code_cw' => 'Code CW',
        'link_bandplan_on3' => 'Band plan ON3',
        'link_bandplan_on2' => 'Band plan ON2',
        'link_bandplan_harec' => 'Band plan HAREC',
        'utc_datetime' => 'Date/heure UTC',
        'local_datetime' => 'Date/heure locale',
        'hero_tagline' => 'ON4CRD · Connecter, expérimenter, partager',
        'ham_weather_aria' => 'Météo radioamateur',
        'venue_address' => 'Bocq Arena, rue des Écoles, 5530 Purnode',
        'quote_aria' => "Citation mise à l'honneur",
        'clock_aria' => 'Horloges UTC et locale',
    ],
    'en' => [
        'journalist_title' => 'Are you a journalist',
        'journalist_desc' => 'Access our press kit directly for your articles and reports.',
        'journalist_cta' => 'Open press kit',
        'teacher_title' => 'Are you a teacher',
        'teacher_desc' => 'Find our educational resources for school activities and projects.',
        'teacher_cta' => 'View educational resources',
        'join_title' => 'Ready to join an active and structured radio community?',
        'join_desc' => 'The new homepage highlights key modules to quickly find useful info and join ON4CRD projects.',
        'contact_title' => 'Contact us',
        'contact_name' => 'Name',
        'contact_email' => 'Email',
        'contact_message' => 'Message',
        'contact_send' => 'Send',
        'important_info_title' => 'Important information',
        'link_terms' => 'Terms of use',
        'link_legal' => 'Legal notice',
        'link_internal_rules' => 'Internal regulations',
        'link_donate' => 'Make a donation',
        'link_sponsoring' => 'Sponsorship',
        'link_code_q' => 'Q code',
        'link_code_cw' => 'CW code',
        'link_bandplan_on3' => 'ON3 band plan',
        'link_bandplan_on2' => 'ON2 band plan',
        'link_bandplan_harec' => 'HAREC band plan',
        'utc_datetime' => 'UTC date/time',
        'local_datetime' => 'Local date/time',
        'hero_tagline' => 'ON4CRD · Connect, experiment, share',
        'ham_weather_aria' => 'Ham radio weather',
        'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode',
        'quote_aria' => 'Featured quote',
        'clock_aria' => 'UTC and local clocks',
    ],
    'de' => [
        'journalist_title' => 'Sind Sie Journalist?',
        'journalist_desc' => 'Greifen Sie direkt auf unsere Pressemappe zu, um Beiträge und Reportagen vorzubereiten.',
        'journalist_cta' => 'Pressemappe öffnen',
        'teacher_title' => 'Sind Sie Lehrkraft?',
        'teacher_desc' => 'Finden Sie unsere pädagogischen Unterlagen für Schulaktivitäten und Projekte.',
        'teacher_cta' => 'Pädagogische Unterlagen ansehen',
        'join_title' => 'Bereit, einer aktiven und strukturierten Funkgemeinschaft beizutreten?',
        'join_desc' => 'Die neue Startseite hebt Kernmodule hervor, um schnell nützliche Informationen zu finden und an ON4CRD-Projekten teilzunehmen.',
        'contact_title' => 'Kontakt',
        'contact_name' => 'Name',
        'contact_email' => 'E-Mail',
        'contact_message' => 'Nachricht',
        'contact_send' => 'Senden',
        'important_info_title' => 'Wichtige Informationen',
        'link_terms' => 'Nutzungsbedingungen',
        'link_legal' => 'Impressum',
        'link_internal_rules' => 'Interne Ordnung',
        'link_donate' => 'Spenden',
        'link_sponsoring' => 'Sponsoring',
        'link_code_q' => 'Q-Code',
        'link_code_cw' => 'CW-Code',
        'link_bandplan_on3' => 'Bandplan ON3',
        'link_bandplan_on2' => 'Bandplan ON2',
        'link_bandplan_harec' => 'Bandplan HAREC',
        'utc_datetime' => 'UTC-Datum/Uhrzeit',
        'local_datetime' => 'Lokale Datum/Uhrzeit',
        'hero_tagline' => 'ON4CRD · Verbinden, experimentieren, teilen',
        'ham_weather_aria' => 'Funkwetter',
        'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode',
        'quote_aria' => 'Hervorgehobenes Zitat',
        'clock_aria' => 'UTC- und Ortszeituhren',
    ],
    'nl' => [
        'journalist_title' => 'Bent u journalist?',
        'journalist_desc' => 'Ga rechtstreeks naar ons persdossier voor publicaties en reportages.',
        'journalist_cta' => 'Bekijk het persdossier',
        'teacher_title' => 'Bent u leerkracht?',
        'teacher_desc' => 'Vind onze pedagogische dossiers voor schoolactiviteiten en educatieve projecten.',
        'teacher_cta' => 'Bekijk pedagogische dossiers',
        'join_title' => 'Klaar om een actieve en gestructureerde radiocommunity te vervoegen?',
        'join_desc' => 'De nieuwe startpagina benadrukt kernmodules om snel nuttige info te vinden en deel te nemen aan ON4CRD-projecten.',
        'contact_title' => 'Contacteer ons',
        'contact_name' => 'Naam',
        'contact_email' => 'E-mail',
        'contact_message' => 'Bericht',
        'contact_send' => 'Verzenden',
        'important_info_title' => 'Belangrijke informatie',
        'link_terms' => 'Algemene gebruiksvoorwaarden',
        'link_legal' => 'Juridische vermeldingen',
        'link_internal_rules' => 'Huishoudelijk reglement',
        'link_donate' => 'Doneer',
        'link_sponsoring' => 'Sponsoring',
        'link_code_q' => 'Q-code',
        'link_code_cw' => 'CW-code',
        'link_bandplan_on3' => 'Bandplan ON3',
        'link_bandplan_on2' => 'Bandplan ON2',
        'link_bandplan_harec' => 'Bandplan HAREC',
        'utc_datetime' => 'UTC-datum/tijd',
        'local_datetime' => 'Lokale datum/tijd',
        'hero_tagline' => 'ON4CRD · Verbinden, experimenteren, delen',
        'ham_weather_aria' => 'Zendweer voor radioamateurs',
        'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode',
        'quote_aria' => 'Uitgelichte quote',
        'clock_aria' => 'UTC- en lokale klokken',
    ],

    'es' => [
        'journalist_title' => '¿Es periodista?', 'journalist_desc' => 'Acceda directamente a nuestro dossier de prensa para preparar sus publicaciones y reportajes.', 'journalist_cta' => 'Ver dossier de prensa', 'teacher_title' => '¿Es docente?', 'teacher_desc' => 'Encuentre nuestros recursos pedagógicos para actividades escolares y proyectos educativos.', 'teacher_cta' => 'Ver recursos pedagógicos', 'join_title' => '¿Listo para unirse a una comunidad de radio activa y estructurada?', 'join_desc' => 'La nueva página de inicio destaca los módulos clave para encontrar rápidamente la información útil y participar en los proyectos de ON4CRD.', 'contact_title' => 'Contáctenos', 'contact_name' => 'Nombre', 'contact_email' => 'Correo electrónico', 'contact_message' => 'Mensaje', 'contact_send' => 'Enviar', 'important_info_title' => 'Información importante', 'link_terms' => 'Condiciones generales de uso', 'link_legal' => 'Aviso legal', 'link_internal_rules' => 'Reglamento interno', 'link_donate' => 'Hacer una donación', 'link_sponsoring' => 'Patrocinio', 'link_code_q' => 'Código Q', 'link_code_cw' => 'Código CW', 'link_bandplan_on3' => 'Plan de bandas ON3', 'link_bandplan_on2' => 'Plan de bandas ON2', 'link_bandplan_harec' => 'Plan de bandas HAREC', 'utc_datetime' => 'Fecha/hora UTC', 'local_datetime' => 'Fecha/hora local', 'hero_tagline' => 'ON4CRD · Conectar, experimentar, compartir', 'ham_weather_aria' => 'Meteo para radioaficionados', 'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode', 'quote_aria' => 'Cita destacada', 'clock_aria' => 'Relojes UTC y local',
    ],
    'it' => [
        'journalist_title' => 'Sei giornalista?', 'journalist_desc' => 'Accedi direttamente al nostro dossier stampa per preparare articoli e servizi.', 'journalist_cta' => 'Consulta il dossier stampa', 'teacher_title' => 'Sei insegnante?', 'teacher_desc' => 'Trova i nostri materiali didattici per attività scolastiche e progetti educativi.', 'teacher_cta' => 'Vedi materiali didattici', 'join_title' => 'Pronto a unirti a una comunità radio attiva e strutturata?', 'join_desc' => 'La nuova home evidenzia i moduli chiave per trovare rapidamente informazioni utili e partecipare ai progetti ON4CRD.', 'contact_title' => 'Contattaci', 'contact_name' => 'Nome', 'contact_email' => 'E-mail', 'contact_message' => 'Messaggio', 'contact_send' => 'Invia', 'important_info_title' => 'Informazioni importanti', 'link_terms' => 'Condizioni generali d’uso', 'link_legal' => 'Note legali', 'link_internal_rules' => 'Regolamento interno', 'link_donate' => 'Fai una donazione', 'link_sponsoring' => 'Sponsorizzazione', 'link_code_q' => 'Codice Q', 'link_code_cw' => 'Codice CW', 'link_bandplan_on3' => 'Piano bande ON3', 'link_bandplan_on2' => 'Piano bande ON2', 'link_bandplan_harec' => 'Piano bande HAREC', 'utc_datetime' => 'Data/ora UTC', 'local_datetime' => 'Data/ora locale', 'hero_tagline' => 'ON4CRD · Connettere, sperimentare, condividere', 'ham_weather_aria' => 'Meteo radioamatoriale', 'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode', 'quote_aria' => 'Citazione in evidenza', 'clock_aria' => 'Orologi UTC e locale',
    ],
    'pt' => [
        'journalist_title' => 'É jornalista?', 'journalist_desc' => 'Aceda diretamente ao nosso dossier de imprensa para preparar publicações e reportagens.', 'journalist_cta' => 'Ver dossier de imprensa', 'teacher_title' => 'É professor?', 'teacher_desc' => 'Encontre os nossos recursos pedagógicos para atividades escolares e projetos educativos.', 'teacher_cta' => 'Ver recursos pedagógicos', 'join_title' => 'Pronto para se juntar a uma comunidade de rádio ativa e estruturada?', 'join_desc' => 'A nova página inicial destaca os módulos principais para encontrar rapidamente informação útil e participar nos projetos ON4CRD.', 'contact_title' => 'Contacte-nos', 'contact_name' => 'Nome', 'contact_email' => 'Correio eletrónico', 'contact_message' => 'Mensagem', 'contact_send' => 'Enviar', 'important_info_title' => 'Informações importantes', 'link_terms' => 'Condições gerais de utilização', 'link_legal' => 'Aviso legal', 'link_internal_rules' => 'Regulamento interno', 'link_donate' => 'Fazer um donativo', 'link_sponsoring' => 'Patrocínio', 'link_code_q' => 'Código Q', 'link_code_cw' => 'Código CW', 'link_bandplan_on3' => 'Plano de bandas ON3', 'link_bandplan_on2' => 'Plano de bandas ON2', 'link_bandplan_harec' => 'Plano de bandas HAREC', 'utc_datetime' => 'Data/hora UTC', 'local_datetime' => 'Data/hora local', 'hero_tagline' => 'ON4CRD · Ligar, experimentar, partilhar', 'ham_weather_aria' => 'Meteo radioamador', 'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode', 'quote_aria' => 'Citação em destaque', 'clock_aria' => 'Relógios UTC e local',
    ],
    'ar' => [
        'journalist_title' => 'هل أنت صحفي؟',
        'journalist_desc' => 'اطّلع مباشرة على ملفنا الصحفي لإعداد مقالاتك وتقاريرك.',
        'journalist_cta' => 'عرض الملف الصحفي',
        'teacher_title' => 'هل أنت مُدرّس؟',
        'teacher_desc' => 'اعثر على مواردنا التعليمية للأنشطة المدرسية والمشاريع التربوية.',
        'teacher_cta' => 'عرض الموارد التعليمية',
        'join_title' => 'هل أنت مستعد للانضمام إلى مجتمع راديو نشط ومنظم؟',
        'join_desc' => 'تُبرز الصفحة الرئيسية الجديدة الوحدات الأساسية للعثور بسرعة على المعلومات المفيدة والمشاركة في مشاريع ON4CRD.',
        'contact_title' => 'اتصل بنا',
        'contact_name' => 'الاسم',
        'contact_email' => 'البريد الإلكتروني',
        'contact_message' => 'الرسالة',
        'contact_send' => 'إرسال',
        'important_info_title' => 'معلومات مهمة',
        'link_terms' => 'شروط الاستخدام',
        'link_legal' => 'الإشعارات القانونية',
        'link_internal_rules' => 'اللوائح الداخلية',
        'link_donate' => 'قدّم تبرعاً',
        'link_sponsoring' => 'الرعاية',
        'utc_datetime' => 'التاريخ/الوقت UTC',
        'local_datetime' => 'التاريخ/الوقت المحلي',
        'hero_tagline' => 'ON4CRD · تواصل، جرّب، شارك',
        'ham_weather_aria' => 'طقس هواة الراديو',
        'quote_aria' => 'اقتباس مميز',
        'clock_aria' => 'ساعات UTC والمحلية',
        'link_code_q' => 'رموز Q',
        'link_code_cw' => 'رموز CW',
        'link_bandplan_on3' => 'مخطط نطاق ON3',
        'link_bandplan_on2' => 'مخطط نطاق ON2',
        'link_bandplan_harec' => 'مخطط نطاق HAREC',
        'venue_address' => 'Bocq Arena، Rue des Écoles، 5530 Purnode',
    ],
    'bn' => [
        'journalist_title' => 'আপনি কি সাংবাদিক?',
        'journalist_desc' => 'প্রকাশনা ও প্রতিবেদন প্রস্তুতের জন্য সরাসরি আমাদের প্রেস কিট দেখুন।',
        'journalist_cta' => 'প্রেস কিট দেখুন',
        'teacher_title' => 'আপনি কি শিক্ষক?',
        'teacher_desc' => 'স্কুল কার্যক্রম ও শিক্ষামূলক প্রকল্পের জন্য আমাদের রিসোর্স দেখুন।',
        'teacher_cta' => 'শিক্ষা উপকরণ দেখুন',
        'join_title' => 'সক্রিয় ও সুসংগঠিত একটি রেডিও কমিউনিটিতে যোগ দিতে প্রস্তুত?',
        'join_desc' => 'নতুন হোমপেজে মূল মডিউলগুলোকে গুরুত্ব দেওয়া হয়েছে যাতে দ্রুত দরকারি তথ্য পাওয়া যায় এবং ON4CRD প্রকল্পে অংশ নেওয়া যায়।',
        'contact_title' => 'যোগাযোগ করুন',
        'contact_name' => 'নাম',
        'contact_email' => 'ইমেইল',
        'contact_message' => 'বার্তা',
        'contact_send' => 'পাঠান',
        'important_info_title' => 'গুরুত্বপূর্ণ তথ্য',
        'link_terms' => 'ব্যবহারের শর্তাবলি',
        'link_legal' => 'আইনি নোটিশ',
        'link_internal_rules' => 'অভ্যন্তরীণ বিধিমালা',
        'link_donate' => 'অনুদান দিন',
        'link_sponsoring' => 'স্পনসরশিপ',
        'utc_datetime' => 'UTC তারিখ/সময়',
        'local_datetime' => 'স্থানীয় তারিখ/সময়',
        'hero_tagline' => 'ON4CRD · সংযোগ, পরীক্ষা, ভাগাভাগি',
        'ham_weather_aria' => 'রেডিও অপেশাদার আবহাওয়া',
        'quote_aria' => 'নির্বাচিত উক্তি',
        'clock_aria' => 'UTC ও স্থানীয় ঘড়ি',
        'link_code_q' => 'Q কোড',
        'link_code_cw' => 'CW কোড',
        'link_bandplan_on3' => 'ON3 ব্যান্ড পরিকল্পনা',
        'link_bandplan_on2' => 'ON2 ব্যান্ড পরিকল্পনা',
        'link_bandplan_harec' => 'HAREC ব্যান্ড পরিকল্পনা',
        'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode',
    ],
    'hi' => [
        'journalist_title' => 'क्या आप पत्रकार हैं?',
        'journalist_desc' => 'अपनी रिपोर्ट और प्रकाशनों की तैयारी के लिए हमारा प्रेस किट सीधे देखें।',
        'journalist_cta' => 'प्रेस किट देखें',
        'teacher_title' => 'क्या आप शिक्षक हैं?',
        'teacher_desc' => 'स्कूल गतिविधियों और शैक्षिक परियोजनाओं के लिए हमारे संसाधन देखें।',
        'teacher_cta' => 'शैक्षिक सामग्री देखें',
        'join_title' => 'क्या आप एक सक्रिय और सुव्यवस्थित रेडियो समुदाय से जुड़ने के लिए तैयार हैं?',
        'join_desc' => 'नया होमपेज मुख्य मॉड्यूल को प्रमुखता देता है ताकि उपयोगी जानकारी जल्दी मिले और ON4CRD परियोजनाओं में भाग लिया जा सके।',
        'contact_title' => 'हमसे संपर्क करें',
        'contact_name' => 'नाम',
        'contact_email' => 'ईमेल',
        'contact_message' => 'संदेश',
        'contact_send' => 'भेजें',
        'important_info_title' => 'महत्वपूर्ण जानकारी',
        'link_terms' => 'उपयोग की शर्तें',
        'link_legal' => 'कानूनी सूचना',
        'link_internal_rules' => 'आंतरिक नियम',
        'link_donate' => 'दान करें',
        'link_sponsoring' => 'प्रायोजन',
        'utc_datetime' => 'UTC दिनांक/समय',
        'local_datetime' => 'स्थानीय दिनांक/समय',
        'hero_tagline' => 'ON4CRD · जुड़ें, प्रयोग करें, साझा करें',
        'ham_weather_aria' => 'शौकिया रेडियो मौसम',
        'quote_aria' => 'चयनित उद्धरण',
        'clock_aria' => 'UTC और स्थानीय घड़ियाँ',
        'link_code_q' => 'Q कोड',
        'link_code_cw' => 'CW कोड',
        'link_bandplan_on3' => 'ON3 बैंड योजना',
        'link_bandplan_on2' => 'ON2 बैंड योजना',
        'link_bandplan_harec' => 'HAREC बैंड योजना',
        'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode',
    ],
    'id' => [
        'journalist_title' => 'Apakah Anda jurnalis?',
        'journalist_desc' => 'Akses kit pers kami langsung untuk menyiapkan publikasi dan laporan Anda.',
        'journalist_cta' => 'Lihat kit pers',
        'teacher_title' => 'Apakah Anda guru?',
        'teacher_desc' => 'Temukan sumber daya pendidikan kami untuk kegiatan sekolah dan proyek edukatif.',
        'teacher_cta' => 'Lihat sumber daya pendidikan',
        'join_title' => 'Siap bergabung dengan komunitas radio yang aktif dan terstruktur?',
        'join_desc' => 'Beranda baru menyoroti modul utama agar informasi penting cepat ditemukan dan Anda dapat ikut dalam proyek ON4CRD.',
        'contact_title' => 'Hubungi kami',
        'contact_name' => 'Nama',
        'contact_email' => 'Surel',
        'contact_message' => 'Pesan',
        'contact_send' => 'Kirim',
        'important_info_title' => 'Informasi penting',
        'link_terms' => 'Syarat penggunaan',
        'link_legal' => 'Pemberitahuan hukum',
        'link_internal_rules' => 'Peraturan internal',
        'link_donate' => 'Beri donasi',
        'link_sponsoring' => 'Sponsor',
        'utc_datetime' => 'Tanggal/waktu UTC',
        'local_datetime' => 'Tanggal/waktu lokal',
        'hero_tagline' => 'ON4CRD · Terhubung, bereksperimen, berbagi',
        'ham_weather_aria' => 'Cuaca radio amatir',
        'quote_aria' => 'Kutipan unggulan',
        'clock_aria' => 'Jam UTC dan lokal',
        'link_code_q' => 'Kode Q',
        'link_code_cw' => 'Kode CW',
        'link_bandplan_on3' => 'Bandplan ON3',
        'link_bandplan_on2' => 'Bandplan ON2',
        'link_bandplan_harec' => 'Bandplan HAREC',
        'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode',
    ],
    'ja' => [
        'journalist_title' => 'ジャーナリストの方へ',
        'journalist_desc' => '記事やレポート作成のために、当クラブのプレスキットをご利用ください。',
        'journalist_cta' => 'プレスキットを見る',
        'teacher_title' => '教育関係者の方へ',
        'teacher_desc' => '学校活動や教育プロジェクト向けの教材をご覧ください。',
        'teacher_cta' => '教育資料を見る',
        'join_title' => '活発で体系的な無線コミュニティに参加しませんか？',
        'join_desc' => '新しいホームページでは主要モジュールを強調し、必要な情報をすばやく見つけてON4CRDプロジェクトに参加できます。',
        'contact_title' => 'お問い合わせ',
        'contact_name' => '名前',
        'contact_email' => 'メール',
        'contact_message' => 'メッセージ',
        'contact_send' => '送信',
        'important_info_title' => '重要情報',
        'link_terms' => '利用規約',
        'link_legal' => '法的表示',
        'link_internal_rules' => '内部規程',
        'link_donate' => '寄付する',
        'link_sponsoring' => 'スポンサー',
        'utc_datetime' => 'UTC日時',
        'local_datetime' => '現地日時',
        'hero_tagline' => 'ON4CRD · つながる、試す、共有する',
        'ham_weather_aria' => 'アマチュア無線天気',
        'quote_aria' => '注目の引用',
        'clock_aria' => 'UTCと現地の時計',
        'link_code_q' => 'Qコード',
        'link_code_cw' => 'CWコード',
        'link_bandplan_on3' => 'ON3バンドプラン',
        'link_bandplan_on2' => 'ON2バンドプラン',
        'link_bandplan_harec' => 'HARECバンドプラン',
        'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode',
    ],
    'ru' => [
        'journalist_title' => 'Вы журналист?',
        'journalist_desc' => 'Откройте наш пресс-кит для подготовки публикаций и репортажей.',
        'journalist_cta' => 'Открыть пресс-кит',
        'teacher_title' => 'Вы преподаватель?',
        'teacher_desc' => 'Посмотрите наши образовательные материалы для школьных занятий и проектов.',
        'teacher_cta' => 'Открыть учебные материалы',
        'join_title' => 'Готовы присоединиться к активному и организованному радиосообществу?',
        'join_desc' => 'Новая главная страница выделяет ключевые модули, чтобы быстрее находить полезную информацию и участвовать в проектах ON4CRD.',
        'contact_title' => 'Связаться с нами',
        'contact_name' => 'Имя',
        'contact_email' => 'Эл. почта',
        'contact_message' => 'Сообщение',
        'contact_send' => 'Отправить',
        'important_info_title' => 'Важная информация',
        'link_terms' => 'Условия использования',
        'link_legal' => 'Юридическая информация',
        'link_internal_rules' => 'Внутренний регламент',
        'link_donate' => 'Сделать пожертвование',
        'link_sponsoring' => 'Спонсорство',
        'utc_datetime' => 'Дата/время UTC',
        'local_datetime' => 'Местная дата/время',
        'hero_tagline' => 'ON4CRD · Связывать, экспериментировать, делиться',
        'ham_weather_aria' => 'Погода для радиолюбителей',
        'quote_aria' => 'Избранная цитата',
        'clock_aria' => 'Часы UTC и местные',
        'link_code_q' => 'Q-код',
        'link_code_cw' => 'CW-код',
        'link_bandplan_on3' => 'План диапазонов ON3',
        'link_bandplan_on2' => 'План диапазонов ON2',
        'link_bandplan_harec' => 'План диапазонов HAREC',
        'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode',
    ],
    'zh' => [
        'journalist_title' => '您是记者吗？',
        'journalist_desc' => '可直接查看我们的媒体资料包，用于准备发布和报道。',
        'journalist_cta' => '查看媒体资料包',
        'teacher_title' => '您是教师吗？',
        'teacher_desc' => '查看我们的教学资源，用于学校活动和教育项目。',
        'teacher_cta' => '查看教学资源',
        'join_title' => '准备加入一个活跃且有组织的无线电社区吗？',
        'join_desc' => '新版首页突出关键模块，帮助您快速找到有用信息并参与 ON4CRD 项目。',
        'contact_title' => '联系我们',
        'contact_name' => '姓名',
        'contact_email' => '电子邮箱',
        'contact_message' => '消息',
        'contact_send' => '发送',
        'important_info_title' => '重要信息',
        'link_terms' => '使用条款',
        'link_legal' => '法律声明',
        'link_internal_rules' => '内部规章',
        'link_donate' => '捐赠',
        'link_sponsoring' => '赞助',
        'utc_datetime' => 'UTC 日期/时间',
        'local_datetime' => '本地日期/时间',
        'hero_tagline' => 'ON4CRD · 连接、实验、分享',
        'ham_weather_aria' => '业余无线电天气',
        'quote_aria' => '精选语录',
        'clock_aria' => 'UTC 与本地时钟',
        'link_code_q' => 'Q 简语',
        'link_code_cw' => 'CW 码',
        'link_bandplan_on3' => 'ON3 频段规划',
        'link_bandplan_on2' => 'ON2 频段规划',
        'link_bandplan_harec' => 'HAREC 频段规划',
        'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode',
    ],
];
foreach ($homeEnglishFallbackLocales as $localeCode) {
    if (!isset($homeExtraMessages[$localeCode])) {
        $homeExtraMessages[$localeCode] = [];
    }
    $homeExtraMessages[$localeCode] = array_replace($homeExtraMessages['en'], $homeExtraMessages[$localeCode]);
}
foreach (array_keys($homeExtraMessages['fr']) as $key) {
    $pool = [];
    foreach ($homeExtraMessages as $lang => $messages) {
        if (isset($messages[$key]) && is_string($messages[$key])) {
            $pool[$lang] = $messages[$key];
        }
    }
    $value = trim(i18n_localized_value($pool, $homeLocale, 'fr'));
    if ($value === '') {
        $value = trim((string) ($homeExtraMessages['fr'][$key] ?? ''));
    }
    $homeI18n[$key] = $value;
}
$homeTodayDate = date('d/m/Y');

$user = current_user();
$isAuthenticated = $user !== null;

$primaryCta = $isAuthenticated
    ? '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('dashboard')) . '">' . e((string) $homeI18n['cta_member_area']) . '</a>'
    : '<a class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700" href="' . e(route_url('membership')) . '">' . e((string) $homeI18n['cta_join_club']) . '</a>';
$newsletterCta = '<a class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-white px-5 py-3 text-sm font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50" href="' . e(route_url('newsletter_public')) . '">' . e((string) $homeI18n['cta_newsletter']) . '</a>';


$moduleCatalog = admin_module_cards_catalog();


$visibilityLabels = [
    'fr' => ['public' => 'Public', 'members' => 'Membres', 'admin' => 'Administrateurs'],
    'en' => ['public' => 'Public', 'members' => 'Members', 'admin' => 'Administrators'],
    'de' => ['public' => 'Öffentlich', 'members' => 'Mitglieder', 'admin' => 'Administratoren'],
    'nl' => ['public' => 'Openbaar', 'members' => 'Leden', 'admin' => 'Beheerders'],
    'es' => ['public' => 'Público', 'members' => 'Socios', 'admin' => 'Administradores'],
    'it' => ['public' => 'Pubblico', 'members' => 'Soci', 'admin' => 'Amministratori'],
    'pt' => ['public' => 'Público', 'members' => 'Membros', 'admin' => 'Administradores'],
    'ar' => ['public' => 'عام', 'members' => 'الأعضاء', 'admin' => 'المسؤولون'],
    'bn' => ['public' => 'সবার জন্য', 'members' => 'সদস্য', 'admin' => 'প্রশাসক'],
    'hi' => ['public' => 'सार्वजनिक', 'members' => 'सदस्य', 'admin' => 'प्रशासक'],
    'id' => ['public' => 'Publik', 'members' => 'Anggota', 'admin' => 'Administrator'],
    'ja' => ['public' => '公開', 'members' => 'メンバー', 'admin' => '管理者'],
    'ru' => ['public' => 'Публичный', 'members' => 'Участники', 'admin' => 'Администраторы'],
    'zh' => ['public' => '公开', 'members' => '会员', 'admin' => '管理员'],
];
$moduleVisibilityLabels = [];
foreach (array_keys($visibilityLabels['fr']) as $key) {
    $moduleVisibilityLabels[$key] = i18n_localized_value($visibilityLabels, $homeLocale, $key);
}
$moduleVisibilityByCode = [];
if (table_exists('modules')) {
    foreach (db()->query('SELECT code, visibility FROM modules')->fetchAll() as $moduleRow) {
        $code = (string) ($moduleRow['code'] ?? '');
        if ($code === '') {
            continue;
        }
        $moduleVisibilityByCode[$code] = (string) ($moduleRow['visibility'] ?? 'members');
    }
}

$activeModules = [];
$moduleCards = '';
foreach ($moduleCatalog as $module) {
    $moduleCode = (string) ($module['code'] ?? $module['module'] ?? '');
    if ($moduleCode !== '' && !module_enabled($moduleCode)) {
        continue;
    }

    $activeModules[] = $module;
    $moduleTitle = is_array($module['title'] ?? null) ? i18n_localized_value((array) $module['title'], $homeLocale, 'fr') : (string) ($module['title'] ?? '');
    $moduleDesc = is_array($module['desc'] ?? null) ? i18n_localized_value((array) $module['desc'], $homeLocale, 'fr') : (string) ($module['desc'] ?? '');
    $moduleAudience = is_array($module['audience'] ?? null) ? i18n_localized_value((array) $module['audience'], $homeLocale, 'fr') : (string) ($module['audience'] ?? '');
    $moduleAudienceCode = (string) ($module['code'] ?? $module['module'] ?? '');
    $configuredVisibility = (string) ($moduleVisibilityByCode[$moduleAudienceCode] ?? '');
    if ($configuredVisibility !== '') {
        $moduleAudience = (string) ($moduleVisibilityLabels[$configuredVisibility] ?? ucfirst($configuredVisibility));
    } elseif ($moduleAudience === '') {
        $moduleAudience = (string) ($moduleVisibilityLabels['members'] ?? 'Membres');
    }
    $moduleIcon = is_array($module['icon'] ?? null) ? i18n_localized_value((array) $module['icon'], $homeLocale, '📦') : (string) ($module['icon'] ?? '📦');

    $moduleCards .= '<a class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2" href="' . e(route_url((string) $module['route'])) . '">'
        . '<div class="flex items-center justify-between gap-3">'
        . '<h3 class="text-lg font-semibold text-slate-900">' . e($moduleTitle) . '</h3>'
        . '<span class="text-xl" aria-hidden="true">' . e($moduleIcon) . '</span>'
        . '</div>'
        . '<p class="mt-2 text-sm text-slate-600">' . e($moduleDesc) . '</p>'
        . '<div class="mt-auto pt-4 flex items-center justify-between gap-3">'
        . '<span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">' . e($moduleAudience) . '</span>'
        . '<span class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-sm font-semibold text-blue-700 transition group-hover:border-blue-300 group-hover:bg-blue-100">' . e((string) $homeI18n['open']) . ' →</span>'
        . '</div>'
        . '</a>';
}

if ($moduleCards === '') {
    $moduleCards = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">' . e((string) $homeI18n['public_updating']) . '</div>';
}


$memberModuleDefinitions = [
    'dashboard' => ['route' => 'dashboard', 'icon' => '🧭', 'title' => ['fr' => 'Tableau de bord', 'en' => 'Dashboard', 'de' => 'Dashboard', 'nl' => 'Dashboard', 'es' => 'Panel', 'it' => 'Dashboard', 'pt' => 'Painel', 'ar' => 'لوحة التحكم', 'hi' => 'डैशबोर्ड', 'ja' => 'ダッシュボード', 'zh' => '仪表板', 'bn' => 'ড্যাশবোর্ড', 'ru' => 'Панель', 'id' => 'Dasbor'], 'desc' => ['fr' => 'Configurez votre espace membre avec des widgets personnalisables.', 'en' => 'Configure your member area with customizable widgets.', 'de' => 'Passen Sie Ihren Mitgliederbereich mit anpassbaren Widgets an.', 'nl' => 'Configureer je ledenruimte met aanpasbare widgets.', 'es' => 'Configura tu espacio de miembro con widgets personalizables.', 'it' => 'Configura la tua area membri con widget personalizzabili.', 'pt' => 'Configure a sua área de membro com widgets personalizáveis.', 'ar' => 'قم بتخصيص مساحة الأعضاء باستخدام عناصر واجهة قابلة للتخصيص.', 'hi' => 'अनुकूलन योग्य विजेट्स के साथ अपना सदस्य क्षेत्र कॉन्फ़िगर करें।', 'ja' => 'カスタマイズ可能なウィジェットで会員エリアを設定します。', 'zh' => '使用可自定义小组件配置您的会员空间。', 'bn' => 'কাস্টমাইজযোগ্য উইজেট দিয়ে আপনার সদস্য এলাকা কনফিগার করুন।', 'ru' => 'Настройте пространство участника с помощью настраиваемых виджетов.', 'id' => 'Konfigurasikan area anggota Anda dengan widget yang dapat disesuaikan.']],
    'articles' => ['route' => 'articles', 'icon' => '🛠️', 'title' => ['fr' => 'Articles techniques', 'en' => 'Technical articles', 'de' => 'Technische Artikel', 'nl' => 'Technische artikels', 'es' => 'Artículos técnicos', 'it' => 'Articoli tecnici', 'pt' => 'Artigos técnicos', 'ar' => 'مقالات تقنية', 'hi' => 'तकनीकी लेख', 'ja' => '技術記事', 'zh' => '技术文章', 'bn' => 'প্রযুক্তিগত নিবন্ধ', 'ru' => 'Технические статьи', 'id' => 'Artikel teknis'], 'desc' => ['fr' => 'Approfondissez vos connaissances avec des contenus pédagogiques.', 'en' => 'Deepen your knowledge with educational content.', 'de' => 'Vertiefen Sie Ihr Wissen mit didaktischen Inhalten.', 'nl' => 'Verdiep je kennis met educatieve inhoud.', 'es' => 'Profundiza tus conocimientos con contenidos educativos.', 'it' => 'Approfondisci le tue conoscenze con contenuti formativi.', 'pt' => 'Aprofunde os seus conhecimentos com conteúdos educativos.', 'ar' => 'عمّق معرفتك من خلال محتوى تعليمي.', 'hi' => 'शैक्षिक सामग्री के साथ अपना ज्ञान बढ़ाएँ।', 'ja' => '教育コンテンツで知識を深めましょう。', 'zh' => '通过教育内容深化您的知识。', 'bn' => 'শিক্ষামূলক কনটেন্ট দিয়ে আপনার জ্ঞান বাড়ান।', 'ru' => 'Углубляйте знания с помощью обучающего контента.', 'id' => 'Perdalam pengetahuan Anda dengan konten edukatif.']],
    'wiki' => ['route' => 'wiki', 'icon' => '📚', 'title' => ['fr' => 'Wiki du club', 'en' => 'Club wiki', 'de' => 'Club-Wiki', 'nl' => 'Clubwiki', 'es' => 'Wiki del club', 'it' => 'Wiki del club', 'pt' => 'Wiki do clube', 'ar' => 'ويكي النادي', 'hi' => 'क्लब विकि', 'ja' => 'クラブWiki', 'zh' => '俱乐部维基', 'bn' => 'ক্লাব উইকি', 'ru' => 'Вики клуба', 'id' => 'Wiki klub'], 'desc' => ['fr' => 'Consultez les procédures et bonnes pratiques radioamateur.', 'en' => 'Browse procedures and amateur radio best practices.', 'de' => 'Lesen Sie Verfahren und bewährte Funkpraktiken.', 'nl' => 'Bekijk procedures en goede radioamateurpraktijken.', 'es' => 'Consulta procedimientos y buenas prácticas de radioafición.', 'it' => 'Consulta procedure e buone pratiche radioamatoriali.', 'pt' => 'Consulte procedimentos e boas práticas de radioamador.', 'ar' => 'تصفح الإجراءات وأفضل ممارسات هواة الراديو.', 'hi' => 'प्रक्रियाएँ और शौकिया रेडियो की श्रेष्ठ प्रथाएँ देखें।', 'ja' => '手順とアマチュア無線のベストプラクティスを確認できます。', 'zh' => '浏览流程与业余无线电最佳实践。', 'bn' => 'পদ্ধতি ও রেডিও অপেশাদারদের সেরা চর্চা দেখুন।', 'ru' => 'Изучайте процедуры и лучшие практики радиолюбителей.', 'id' => 'Jelajahi prosedur dan praktik terbaik radio amatir.']],
    'albums' => ['route' => 'albums', 'icon' => '🖼️', 'title' => ['fr' => 'Galerie photo', 'en' => 'Photo gallery', 'de' => 'Fotogalerie', 'nl' => 'Fotogalerij', 'es' => 'Galería de fotos', 'it' => 'Galleria fotografica', 'pt' => 'Galeria de fotos', 'ar' => 'معرض الصور', 'hi' => 'फ़ोटो गैलरी', 'ja' => 'フォトギャラリー', 'zh' => '照片库', 'bn' => 'ফটো গ্যালারি', 'ru' => 'Фотогалерея', 'id' => 'Galeri foto'], 'desc' => ['fr' => 'Revivez les activités du club à travers les albums.', 'en' => 'Relive club activities through photo albums.', 'de' => 'Erleben Sie Clubaktivitäten in Fotoalben erneut.', 'nl' => 'Herbeleef clubactiviteiten via fotoalbums.', 'es' => 'Revive las actividades del club a través de álbumes de fotos.', 'it' => 'Rivivi le attività del club attraverso gli album fotografici.', 'pt' => 'Reviva as atividades do clube através dos álbuns de fotos.', 'ar' => 'استعد أنشطة النادي من خلال ألبومات الصور.', 'hi' => 'फोटो एलबम के माध्यम से क्लब की गतिविधियों को फिर से देखें।', 'ja' => 'フォトアルバムでクラブ活動を振り返りましょう。', 'zh' => '通过相册重温俱乐部活动。', 'bn' => 'ফটো অ্যালবামের মাধ্যমে ক্লাবের কার্যক্রম আবার দেখুন।', 'ru' => 'Вспоминайте клубные мероприятия через фотоальбомы.', 'id' => 'Hidupkan kembali aktivitas klub melalui album foto.']],
    'qsl' => ['route' => 'qsl', 'icon' => '📮', 'title' => ['fr' => 'Espace QSL', 'en' => 'QSL area', 'de' => 'QSL-Bereich', 'nl' => 'QSL-ruimte', 'es' => 'Espacio QSL', 'it' => 'Area QSL', 'pt' => 'Área QSL', 'ar' => 'مساحة QSL', 'hi' => 'QSL क्षेत्र', 'ja' => 'QSLエリア', 'zh' => 'QSL专区', 'bn' => 'QSL এলাকা', 'ru' => 'Зона QSL', 'id' => 'Area QSL'], 'desc' => ['fr' => 'Préparez et exportez vos cartes QSL depuis un espace dédié.', 'en' => 'Prepare and export your QSL cards from a dedicated area.', 'de' => 'Bereiten Sie Ihre QSL-Karten in einem eigenen Bereich vor und exportieren Sie sie.', 'nl' => 'Bereid en exporteer je QSL-kaarten vanuit een aparte ruimte.', 'es' => 'Prepara y exporta tus tarjetas QSL desde un espacio dedicado.', 'it' => 'Prepara ed esporta le tue cartoline QSL da un’area dedicata.', 'pt' => 'Prepare e exporte os seus cartões QSL a partir de uma área dedicada.', 'ar' => 'حضّر بطاقات QSL وصدّرها من مساحة مخصصة.', 'hi' => 'एक समर्पित क्षेत्र से अपनी QSL कार्ड तैयार और निर्यात करें।', 'ja' => '専用エリアでQSLカードを作成・エクスポートできます。', 'zh' => '在专属区域准备并导出您的QSL卡片。', 'bn' => 'নির্দিষ্ট একটি এলাকা থেকে আপনার QSL কার্ড প্রস্তুত ও এক্সপোর্ট করুন।', 'ru' => 'Готовьте и экспортируйте QSL-карточки в отдельном разделе.', 'id' => 'Siapkan dan ekspor kartu QSL Anda dari area khusus.']],
    'auctions' => ['route' => 'auctions', 'icon' => '🏷️', 'title' => ['fr' => 'Enchères', 'en' => 'Auctions', 'de' => 'Auktionen', 'nl' => 'Veilingen', 'es' => 'Subastas', 'it' => 'Aste', 'pt' => 'Leilões', 'ar' => 'المزادات', 'hi' => 'नीलामी', 'ja' => 'オークション', 'zh' => '拍卖', 'bn' => 'নিলাম', 'ru' => 'Аукционы', 'id' => 'Lelang'], 'desc' => ['fr' => 'Donnez une seconde vie au matériel radio entre membres.', 'en' => 'Give radio gear a second life between members.', 'de' => 'Geben Sie Funkgeräten unter Mitgliedern ein zweites Leben.', 'nl' => 'Geef radioapparatuur een tweede leven tussen leden.', 'es' => 'Da una segunda vida al equipo de radio entre miembros.', 'it' => 'Dai una seconda vita all’attrezzatura radio tra membri.', 'pt' => 'Dê uma segunda vida ao equipamento de rádio entre membros.', 'ar' => 'امنح معدات الراديو حياة ثانية بين الأعضاء.', 'hi' => 'सदस्यों के बीच रेडियो उपकरण को दूसरी ज़िंदगी दें।', 'ja' => '会員間で無線機材に第二の命を与えましょう。', 'zh' => '让无线电设备在会员间焕发新生。', 'bn' => 'সদস্যদের মধ্যে রেডিও সরঞ্জামকে নতুন জীবন দিন।', 'ru' => 'Дайте радиооборудованию вторую жизнь среди участников.', 'id' => 'Beri perlengkapan radio kehidupan kedua antaranggota.']],
    'news' => ['route' => 'news', 'icon' => '📰', 'title' => ['fr' => 'Actualités', 'en' => 'News', 'de' => 'Nachrichten', 'nl' => 'Nieuws', 'es' => 'Noticias', 'it' => 'Notizie', 'pt' => 'Notícias', 'ar' => 'الأخبار', 'hi' => 'समाचार', 'ja' => 'ニュース', 'zh' => '新闻', 'bn' => 'সংবাদ', 'ru' => 'Новости', 'id' => 'Berita'], 'desc' => ['fr' => 'Suivez les annonces et informations du club.', 'en' => 'Follow club announcements and updates.', 'de' => 'Verfolgen Sie Ankündigungen und Informationen des Clubs.', 'nl' => 'Volg clubaankondigingen en updates.', 'es' => 'Sigue los anuncios y novedades del club.', 'it' => 'Segui annunci e aggiornamenti del club.', 'pt' => 'Acompanhe os anúncios e novidades do clube.', 'ar' => 'تابع إعلانات النادي وتحديثاته.', 'hi' => 'क्लब की घोषणाओं और अपडेट्स का अनुसरण करें।', 'ja' => 'クラブのお知らせと最新情報を確認しましょう。', 'zh' => '关注俱乐部公告和更新。', 'bn' => 'ক্লাবের ঘোষণা ও আপডেট অনুসরণ করুন।', 'ru' => 'Следите за объявлениями и обновлениями клуба.', 'id' => 'Ikuti pengumuman dan pembaruan klub.']],
    'events' => ['route' => 'events', 'icon' => '📅', 'title' => ['fr' => 'Événements', 'en' => 'Events', 'de' => 'Veranstaltungen', 'nl' => 'Evenementen', 'es' => 'Eventos', 'it' => 'Eventi', 'pt' => 'Eventos', 'ar' => 'الفعاليات', 'hi' => 'इवेंट्स', 'ja' => 'イベント', 'zh' => '活动', 'bn' => 'ইভেন্ট', 'ru' => 'События', 'id' => 'Acara'], 'desc' => ['fr' => 'Consultez le calendrier des activités et rendez-vous.', 'en' => 'Check the calendar of activities and meetups.', 'de' => 'Sehen Sie den Kalender der Aktivitäten und Treffen ein.', 'nl' => 'Bekijk de kalender met activiteiten en bijeenkomsten.', 'es' => 'Consulta el calendario de actividades y encuentros.', 'it' => 'Consulta il calendario di attività e incontri.', 'pt' => 'Consulte o calendário de atividades e encontros.', 'ar' => 'اطلع على تقويم الأنشطة واللقاءات.', 'hi' => 'गतिविधियों और मुलाक़ातों का कैलेंडर देखें।', 'ja' => '活動やミートアップのカレンダーを確認してください。', 'zh' => '查看活动与聚会日历。', 'bn' => 'কার্যক্রম ও মিটআপের ক্যালেন্ডার দেখুন।', 'ru' => 'Проверьте календарь мероприятий и встреч.', 'id' => 'Lihat kalender aktivitas dan pertemuan.']],
];
$memberModuleCards = '';
if (table_exists('modules')) {
    $memberModules = db()->query("SELECT code FROM modules WHERE is_enabled = 1 AND visibility = 'members' ORDER BY sort_order ASC")->fetchAll() ?: [];
    foreach ($memberModules as $memberModuleRow) {
        $moduleCode = (string) ($memberModuleRow['code'] ?? '');
        if ($moduleCode === '' || !isset($memberModuleDefinitions[$moduleCode])) {
            continue;
        }
        $moduleMeta = $memberModuleDefinitions[$moduleCode];
        $memberModuleCards .= '<a class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2" href="' . e(route_url((string) $moduleMeta['route'])) . '">' 
            . '<div class="flex items-center justify-between gap-3">'
            . '<h3 class="text-lg font-semibold text-slate-900">' . e(i18n_localized_value((array) ($moduleMeta['title'] ?? []), $homeLocale, $moduleCode)) . '</h3>'
            . '<span class="text-xl" aria-hidden="true">' . e((string) ($moduleMeta['icon'] ?? '📦')) . '</span>'
            . '</div>'
            . '<p class="mt-2 text-sm text-slate-600">' . e(i18n_localized_value((array) ($moduleMeta['desc'] ?? []), $homeLocale, '')) . '</p>'
            . '<div class="mt-auto pt-4 flex items-center justify-between gap-3">'
            . '<span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-600">' . e((string) $homeI18n['member_audience']) . '</span>'
            . '<span class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1 text-sm font-semibold text-blue-700 transition group-hover:border-blue-300 group-hover:bg-blue-100">' . e((string) $homeI18n['open']) . ' →</span>'
            . '</div>'
            . '</a>';
    }
}
if ($memberModuleCards === '') {
    $memberModuleCards = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">' . e((string) $homeI18n['member_modules_empty']) . '</div>';
}


$memberModulesSectionHtml = '';
if (!$isAuthenticated) {
    $memberModulesSectionHtml = '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
        . '<header class="mb-4">'
        . '<h2 class="text-2xl font-bold text-slate-900">' . e((string) $homeI18n['member_modules_title']) . '</h2>'
        . '</header>'
        . '<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">' . $memberModuleCards . '</div>'
        . '</section>';
}

$heroTitle = '';

$heroSubtitle = '';

$heroIntro = '';
if ($heroTitle !== '') {
    $heroIntro .= '<h1 class="mt-4 max-w-2xl text-4xl font-extrabold leading-tight text-slate-900 lg:text-5xl">' . e($heroTitle) . '</h1>';
}
if ($heroSubtitle !== '') {
    $heroIntro .= '<p class="mt-4 max-w-2xl text-base text-slate-600">' . e($heroSubtitle) . '</p>';
}

$moduleCount = count($activeModules);
$heroBackgroundUrl = asset_url('assets/img/on4crd_hero.png');
$heroImageCandidates = cache_remember('home_hero_image_candidates_v1', 300, static function (): array {
    return glob(__DIR__ . '/../assets/img/*.{png,jpg,jpeg,webp,gif,avif}', GLOB_BRACE) ?: [];
});
if ($heroImageCandidates !== []) {
    $heroBackgroundUrl = asset_url('assets/img/' . basename((string) $heroImageCandidates[array_rand($heroImageCandidates)]));
}

$latestNews = null;
$nextEvent = null;
$featuredAd = null;

try {
    if (table_exists('news_posts')) {
        $latestNews = cache_remember('home_latest_news_v1', 60, static function () {
            return db()->query('SELECT slug, title, excerpt, published_at, updated_at FROM news_posts WHERE status = "published" ORDER BY COALESCE(published_at, updated_at) DESC LIMIT 1')->fetch();
        });
    }

    if (table_exists('events')) {
        $nextEvent = cache_remember('home_next_event_v1', 60, static function () {
            $stmt = db()->prepare('SELECT slug, title, summary, start_at, location FROM events WHERE status = "published" AND end_at >= NOW() ORDER BY start_at ASC LIMIT 1');
            $stmt->execute();
            return $stmt->fetch();
        });
    }

    if (module_enabled('advertising') && table_exists('ads')) {
        $featuredAd = cache_remember('home_featured_ad_v1', 60, static function () {
            return db()->query('SELECT title, description, image_path, target_url FROM ads WHERE status = "active" ORDER BY updated_at DESC LIMIT 1')->fetch();
        });
    }
} catch (Throwable) {
    // Les blocs "À la une" restent en mode fallback si la base n'est pas disponible.
}

$latestNewsHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">' . e((string) $homeI18n['no_news']) . '</div>';
if (is_array($latestNews) && !empty($latestNews['slug'])) {
    $newsDate = !empty($latestNews['published_at']) ? date('d/m/Y', strtotime((string) $latestNews['published_at'])) : date('d/m/Y', strtotime((string) ($latestNews['updated_at'] ?? 'now')));
    $newsExcerpt = trim((string) ($latestNews['excerpt'] ?? ''));
    if ($newsExcerpt === '') {
        $newsExcerpt = (string) $homeI18n['news_fallback'];
    }

    $latestNewsHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('news_view', ['slug' => (string) $latestNews['slug']])) . '">'
        . '<p class="text-xs font-semibold uppercase tracking-wide text-blue-700">' . e((string) $homeI18n['published_on']) . ' ' . e($newsDate) . '</p>'
        . '<h3 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-blue-700">' . e((string) $latestNews['title']) . '</h3>'
        . '<p class="mt-2 text-sm text-slate-600">' . e($newsExcerpt) . '</p>'
        . '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e((string) $homeI18n['read_news']) . ' →</span>'
        . '</a>';
}

$nextEventHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">' . e((string) $homeI18n['no_event']) . '</div>';
if (is_array($nextEvent) && !empty($nextEvent['slug'])) {
    $eventDate = !empty($nextEvent['start_at']) ? date('d/m/Y H:i', strtotime((string) $nextEvent['start_at'])) : (string) $homeI18n['event_date_tbd'];
    $eventSummary = trim((string) ($nextEvent['summary'] ?? ''));
    if ($eventSummary === '') {
        $eventSummary = (string) $homeI18n['event_fallback'];
    }
    $eventLocation = trim((string) ($nextEvent['location'] ?? ''));

    $nextEventHtml = '<a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="' . e(route_url('event_view', ['slug' => (string) $nextEvent['slug']])) . '">'
        . '<p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">' . e((string) $homeI18n['next_date']) . ' · ' . e($eventDate) . '</p>'
        . '<h3 class="mt-2 text-lg font-bold text-slate-900 group-hover:text-blue-700">' . e((string) $nextEvent['title']) . '</h3>'
        . '<p class="mt-2 text-sm text-slate-600">' . e($eventSummary) . '</p>';

    if ($eventLocation !== '') {
        $nextEventHtml .= '<p class="mt-2 text-xs font-medium text-slate-500">' . e((string) $homeI18n['event_location']) . ' : ' . e($eventLocation) . '</p>';
    }

    $nextEventHtml .= '<span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">' . e((string) $homeI18n['view_event']) . ' →</span>'
        . '</a>';
}

$adSlotHtml = '<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500"><p class="mt-2">' . e((string) $homeI18n['partner_ad_empty']) . '</p></div>';
$localAdCandidates = cache_remember('home_local_ad_candidates_v1', 300, static function (): array {
    return glob(__DIR__ . '/../assets/pub/*.{png,jpg,jpeg,webp,gif,avif}', GLOB_BRACE) ?: [];
});
if ($localAdCandidates !== []) {
    $localAdPath = 'assets/pub/' . basename((string) $localAdCandidates[array_rand($localAdCandidates)]);
    $adSlotHtml = '<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">'
        . '<div class="overflow-hidden rounded-lg aspect-square w-full">'
        . '<img class="h-full w-full object-cover" src="' . e(asset_url($localAdPath)) . '" alt="' . e((string) $homeI18n['alt_partner_ad']) . '" loading="lazy" decoding="async">'
        . '</div>'
        . '</div>';
}
if (is_array($featuredAd) && !empty($featuredAd['title'])) {
    $adTarget = trim((string) ($featuredAd['target_url'] ?? ''));
    $adDescription = trim((string) ($featuredAd['description'] ?? ''));
    $adImage = trim((string) ($featuredAd['image_path'] ?? ''));

    $adInner = '<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">'
        . '<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['partner_ad_title']) . '</p>'
        . '<h3 class="mt-2 text-lg font-bold text-slate-900">' . e((string) $featuredAd['title']) . '</h3>';

    if ($adDescription !== '') {
        $adInner .= '<p class="mt-2 text-sm text-slate-600">' . e($adDescription) . '</p>';
    }

    if ($adImage !== '') {
        $adInner .= '<div class="mt-3 overflow-hidden rounded-lg aspect-square w-full"><img class="h-full w-full object-cover" src="' . e(asset_url($adImage)) . '" alt="' . e((string) $featuredAd['title']) . '" loading="lazy" decoding="async"></div>';
    }

    $adInner .= '</div>';

    $adSlotHtml = $adTarget !== ''
        ? '<a class="block transition hover:-translate-y-0.5" href="' . e($adTarget) . '" target="_blank" rel="noopener noreferrer">' . $adInner . '</a>'
        : $adInner;
}

$ubaLogoPath = 'assets/logo/UBA-Logo-Couleur-MID2.png';
$relaisLogoPath = 'assets/logo/CRD-Echolink.jpg';
$homeWeatherHtml = render_widget('open_meteo');
$homePropagationHtml = render_widget('propagation');
$hasHomePropagation = trim((string) $homePropagationHtml) !== '';
$homeHamAdviceHtml = render_ham_weather_advice(current_user() ?? []);
$hamWeatherRefreshUrl = base_url('index.php?' . http_build_query(['route' => 'home', 'ajax' => 'ham_weather']));
$homeRadioInfoHtml = '<div class="grid gap-4">'
    . '<section>'
    . '<h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['ham_info_title']) . '</h4>'
    . '<ul class="mt-2 list-clean">'
    . '<li><strong>' . e((string) $homeI18n['vhf_voice_label']) . '</strong> ' . e((string) $homeI18n['vhf_voice_value']) . '</li>'
    . '<li><strong>' . e((string) ($homeI18n['cw_qrp_label'] ?? 'QRG CW QRP :')) . '</strong> ' . e((string) ($homeI18n['cw_qrp_value'] ?? '7.030 MHz • 14.060 MHz')) . '</li>'
    . '<li><strong>' . e((string) $homeI18n['good_practice_label']) . '</strong> ' . e((string) $homeI18n['good_practice_value']) . '</li>'
    . '</ul>'
    . '</section>'
    . '</div>';

if (isset($_GET['ajax']) && (string) $_GET['ajax'] === 'ham_weather') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    try {
        echo json_encode([
            'weather' => render_widget('open_meteo'),
            'propagation' => render_widget('propagation'),
            'advice' => render_ham_weather_advice(current_user() ?? []),
            'updated_at' => gmdate('c'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        http_response_code(500);
        echo '{"weather":"","propagation":"","advice":""}';
    }

    return;
}

$homeQuote = random_quote_for_layout();
$homeQuoteText = (string) $homeI18n['quote_fallback'];
$homeQuoteAuthor = '';
if (is_array($homeQuote)) {
    $candidateHomeQuoteText = trim((string) ($homeQuote['quote'] ?? ''));
    $candidateHomeQuoteAuthor = trim((string) ($homeQuote['author'] ?? ''));
    if ($candidateHomeQuoteText !== '') {
        $homeQuoteText = $candidateHomeQuoteText;
    }
    if ($candidateHomeQuoteAuthor !== '') {
        $homeQuoteAuthor = $candidateHomeQuoteAuthor;
    }
}


$spotlightPlaceholderCard = static function (string $title, string $placeholder): string {
    return '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">'
        . e($title)
        . '</h3><div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500">'
        . e($placeholder)
        . '</div></article>';
};
$spotlightToolCard = '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">'
    . e((string) $homeI18n['spotlight_sub_2'])
    . '</h3><a class="group block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md" href="'
    . e(route_url('tools'))
    . '#tool-grid"><p class="text-sm font-semibold text-slate-900">'
    . e((string) $homeI18n['spotlight_tool_day_item'])
    . '</p><span class="mt-3 inline-flex text-sm font-semibold text-blue-600 group-hover:text-blue-700">'
    . e((string) $homeI18n['spotlight_tool_day_cta'])
    . ' →</span></a></article>';
$spotlightPlaceholderOneCard = $spotlightPlaceholderCard((string) $homeI18n['spotlight_sub_1'], (string) $homeI18n['spotlight_sub_placeholder']);
$spotlightPlaceholderThreeCard = $spotlightPlaceholderCard((string) $homeI18n['spotlight_sub_3'], (string) $homeI18n['spotlight_sub_placeholder']);

$content = '<section class="mb-4 grid gap-4 lg:grid-cols-2">'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="' . e((string) $homeI18n['quote_aria']) . '">'
    . '<h2 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['quote_day']) . '</h2>'
    . '<blockquote class="mt-3 border-l-4 border-blue-200 pl-4 text-base italic text-slate-700">“' . e($homeQuoteText) . '”</blockquote>'
    . ($homeQuoteAuthor !== '' ? '<p class="mt-3 text-sm font-semibold text-slate-500">— ' . e($homeQuoteAuthor) . '</p>' : '')
    . '</article>'
    . '<article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="' . e((string) $homeI18n['clock_aria']) . '">'
    . '<div class="grid gap-3 sm:grid-cols-2">'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4">'
    . '<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['utc_datetime']) . '</p>'
    . '<div class="mt-2 flex items-center justify-between gap-3">'
    . '<span class="text-xl font-bold text-slate-900" data-live-date data-timezone="UTC" aria-live="polite">' . e($homeTodayDate) . '</span>'
    . '<time class="text-xl font-bold text-slate-900" data-live-clock data-timezone="UTC" aria-live="polite">--:--:--</time>'
    . '</div>'
    . '</article>'
    . '<article class="rounded-xl border border-slate-200 bg-slate-50 p-4">'
    . '<p class="text-xs font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['local_datetime']) . '</p>'
    . '<div class="mt-2 flex items-center justify-between gap-3">'
    . '<span class="text-xl font-bold text-slate-900" data-live-date data-timezone="local" aria-live="polite">' . e($homeTodayDate) . '</span>'
    . '<time class="text-xl font-bold text-slate-900" data-live-clock data-timezone="local" aria-live="polite">--:--:--</time>'
    . '</div>'
    . '</article>'
    . '</div>'
    . '</article>'
    . '</section>'
    . '<section class="grid gap-4 lg:grid-cols-[1.55fr_.95fr]">'
    . '<article class="relative isolate flex h-full flex-col overflow-hidden rounded-3xl border border-slate-200 p-8 shadow-sm">'
    . '<img class="absolute inset-0 -z-20 h-full w-full object-cover" src="' . e($heroBackgroundUrl) . '" alt="' . e((string) $homeI18n['alt_hero_illustration']) . '" loading="eager" decoding="async">'
    . '<span class="hidden rounded-full bg-blue-600 px-3 py-1 text-[1.1rem] font-semibold uppercase tracking-wide text-white sm:inline-flex">' . e((string) $homeI18n['hero_tagline']) . '</span>'
    . $heroIntro
    . '<div class="mt-auto pt-8 grid max-w-sm gap-2">' . $primaryCta . $newsletterCta . '</div>'
    . '</article>'
    . '<div class="grid gap-4">'
    . '<aside class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" aria-label="' . e((string) $homeI18n['ham_weather_aria']) . '">'
    . '<div class="flex items-start justify-between gap-4">'
    . '<div>'
    . '<h2 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['ham_weather']) . '</h2>'
    . '<p class="mt-1 text-sm text-slate-600">' . e((string) $homeI18n['ham_weather_desc']) . '</p>'
    . '</div>'
    . '<span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">LIVE</span>'
    . '</div>'
    . '<div class="mt-4 rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50 via-white to-slate-50 p-4" data-ham-weather-root data-refresh-ms="900000" data-refresh-url="' . e($hamWeatherRefreshUrl) . '" data-updated-label="' . e((string) $homeI18n['weather_updated']) . '">'
    . '<div class="grid gap-3">'
    . '<section class="rounded-xl border border-slate-200 bg-white p-3" data-ham-weather-weather>' . $homeWeatherHtml . '</section>'
    . ($hasHomePropagation ? '<section class="rounded-xl border border-slate-200 bg-white p-3" data-ham-weather-propagation-wrapper><div data-ham-weather-propagation>' . $homePropagationHtml . '</div></section>' : '')
    . '<section class="rounded-xl border border-slate-200 bg-white p-3" data-ham-weather-advice>' . $homeHamAdviceHtml . '</section>'
    . '<div class="mt-2 flex items-center justify-between gap-3 border-t border-slate-200 pt-3">'
    . '<p class="text-xs font-medium text-slate-500 whitespace-nowrap" data-ham-weather-updated></p>'
    . '<button type="button" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-60" data-ham-weather-refresh aria-label="' . e((string) $homeI18n['weather_refresh']) . '">⟳ ' . e((string) $homeI18n['weather_refresh']) . '</button>'
    . '</div>'
    . '</div>'
    . '</div>'
    . '</aside>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 grid gap-4 rounded-3xl border border-blue-200 bg-gradient-to-r from-blue-50 to-white p-6 shadow-sm lg:grid-cols-[1.8fr_1fr] lg:items-center">'
    . '<div><h2 class="text-2xl font-extrabold text-slate-900">' . e((string) $homeI18n['join_title']) . '</h2><p class="mt-2 text-slate-600">' . e((string) $homeI18n['join_desc']) . '</p></div>'
    . '<div class="grid gap-2">' . $primaryCta . $newsletterCta . '</div>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="grid gap-4 lg:grid-cols-[1.15fr_.85fr]">'
    . '<div class="grid gap-4">'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['uba_title']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['uba_desc']) . '</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.uba.be" target="_blank" rel="noopener noreferrer">' . e((string) $homeI18n['uba_cta']) . '</a></div><img class="h-20 w-auto object-contain" src="' . e(asset_url($ubaLogoPath)) . '" alt="' . e((string) $homeI18n['alt_uba_logo']) . '" loading="lazy" decoding="async"></div></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['ibpt_title']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['ibpt_desc']) . '</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.ibpt.be/consommateurs/frequences-radio/utilisation-privee-de-loisir/radioamateurs" target="_blank" rel="noopener noreferrer">' . e((string) $homeI18n['ibpt_cta']) . '</a></article>'
    . '<article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['repeater_title']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['repeater_desc']) . '</p><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('relais')) . '">' . e((string) $homeI18n['repeater_cta']) . '</a></div><img class="h-20 w-auto object-contain" src="' . e(asset_url($relaisLogoPath)) . '" alt="' . e((string) $homeI18n['alt_repeater_logo']) . '" loading="lazy" decoding="async"></div></article>'
    . '</div>'
    . '<article class="h-full rounded-2xl border border-slate-200 bg-slate-50 p-4"><h3 class="text-lg font-semibold text-slate-900">' . e((string) $homeI18n['useful_info']) . '</h3><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['meetings_info']) . '</p><p class="mt-2 text-sm text-slate-600">' . e((string) $homeI18n['venue_address']) . '</p><div class="mt-3 overflow-hidden rounded-lg border border-slate-200"><iframe class="h-64 w-full" title="' . e((string) $homeI18n['map_title']) . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q=50%C2%B018%2754.1%22N+4%C2%B056%2742.7%22E&output=embed"></iframe></div><a class="mt-3 inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="https://www.google.com/maps?q=50%C2%B018%2754.1%22N+4%C2%B056%2742.7%22E" target="_blank" rel="noopener noreferrer">' . e((string) $homeI18n['maps_route']) . '</a></article>'
    . '</div>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<header class="mb-4">'
    . '<h2 class="text-2xl font-bold text-slate-900">' . e((string) $homeI18n['club_spotlight_title']) . '</h2>'
    . '</header>'
    . '<div class="grid gap-4 lg:grid-cols-3">'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['spotlight_tool_day']) . '</h3>' . $latestNewsHtml . '</article>'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['spotlight_for_sale']) . '</h3>' . $nextEventHtml . '</article>'
    . '<article><h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-500">' . e((string) $homeI18n['spotlight_auction_live']) . '</h3>' . $adSlotHtml . '</article>'
    . $spotlightPlaceholderOneCard
    . $spotlightToolCard
    . $spotlightPlaceholderThreeCard
    . '</div>'
    . '</section>'
    . $memberModulesSectionHtml
    . '<section class="mt-4 grid gap-4 md:grid-cols-2">'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h3 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['journalist_title']) . '</h3>'
    . '<p class="mt-3 text-sm text-slate-600">' . e((string) $homeI18n['journalist_desc']) . '</p>'
    . '<a class="mt-4 inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('press')) . '">' . e((string) $homeI18n['journalist_cta']) . '</a>'
    . '</article>'
    . '<article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<h3 class="text-xl font-bold text-slate-900">' . e((string) $homeI18n['teacher_title']) . '</h3>'
    . '<p class="mt-3 text-sm text-slate-600">' . e((string) $homeI18n['teacher_desc']) . '</p>'
    . '<a class="mt-4 inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50" href="' . e(route_url('schools')) . '">' . e((string) $homeI18n['teacher_cta']) . '</a>'
    . '</article>'
    . '</section>'
    . '<section class="mt-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">'
    . '<div class="grid gap-6 lg:grid-cols-3">'
    . '<article><h3 class="text-lg font-bold text-slate-900">' . e((string) $homeI18n['address_title']) . '</h3><p class="mt-3 text-sm text-slate-700">' . e((string) $homeI18n['club_name']) . '</p><p class="text-sm text-slate-700">' . e((string) $homeI18n['venue_line_1']) . '</p><p class="text-sm text-slate-700">' . e((string) $homeI18n['venue_line_2']) . '</p><p class="text-sm text-slate-700">' . e((string) $homeI18n['venue_line_3']) . '</p><p class="mt-4 text-lg font-bold text-slate-900">' . e((string) $homeI18n['contact_people']) . '</p><p class="text-sm text-slate-700">ON4BEN : +32 496 260 865</p><p class="text-sm text-slate-700">ON4DG : +32 478 789 193</p></article>'
    . '<article><h3 class="text-lg font-bold text-slate-900">' . e((string) $homeI18n['contact_title']) . '</h3><form class="mt-3 grid gap-2" method="post" action="' . e(route_url('footer_contact')) . '"><input type="hidden" name="_csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="return_route" value="home"><label for="home-contact-name" class="sr-only">' . e((string) $homeI18n['contact_name']) . '</label><input id="home-contact-name" type="text" name="name" placeholder="' . e((string) $homeI18n['contact_name']) . '" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><label for="home-contact-email" class="sr-only">' . e((string) $homeI18n['contact_email']) . '</label><input id="home-contact-email" type="email" name="email" placeholder="' . e((string) $homeI18n['contact_email']) . '" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><label for="home-contact-message" class="sr-only">' . e((string) $homeI18n['contact_message']) . '</label><textarea id="home-contact-message" name="message" placeholder="' . e((string) $homeI18n['contact_message']) . '" rows="3" maxlength="2000" data-wysiwyg="off" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea><button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">' . e((string) $homeI18n['contact_send']) . '</button></form></article>'
    . '<article><h3 class="text-lg font-bold text-slate-900">' . e((string) $homeI18n['important_info_title']) . '</h3><ul class="mt-3 list-inside list-disc space-y-1 text-sm text-slate-700"><li><a class="hover:underline" href="' . e(route_url('conditions_utilisation')) . '">' . e((string) $homeI18n['link_terms']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('mentions_legales')) . '">' . e((string) $homeI18n['link_legal']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('reglement_interieur')) . '">' . e((string) $homeI18n['link_internal_rules']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('membership')) . '">' . e((string) $homeI18n['link_donate']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('sponsoring')) . '">' . e((string) $homeI18n['link_sponsoring']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('code_q')) . '">' . e((string) $homeI18n['link_code_q']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('code_cw')) . '">' . e((string) $homeI18n['link_code_cw']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('bandplan_on3')) . '">' . e((string) $homeI18n['link_bandplan_on3']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('bandplan_on2')) . '">' . e((string) $homeI18n['link_bandplan_on2']) . '</a></li><li><a class="hover:underline" href="' . e(route_url('bandplan_harec')) . '">' . e((string) $homeI18n['link_bandplan_harec']) . '</a></li></ul></article>'
    . '</div>'
    . '</section>';


$homeScriptNonce = csp_nonce();
ob_start();
include __DIR__ . '/home_script_ham_weather.js.php';
$homeWeatherScript = trim((string) ob_get_clean());
$content .= '<script nonce="' . e($homeScriptNonce) . '">' . $homeWeatherScript . '</script>';

echo render_layout($content, (string) ($homeI18n['page_title'] ?? 'Accueil'));
