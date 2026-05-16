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
        'spotlight_for_sale' => 'Boutique / Enchères',
        'spotlight_auction_live' => 'Rechercher',
        'spotlight_sub_1' => 'Boutique / Enchères',
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
        'read_news' => 'Read news',
        'news_fallback' => 'Check out the club’s latest news update.',
        'no_event' => 'No event is currently scheduled.',
        'event_date_tbd' => 'Date to be confirmed',
        'event_fallback' => 'Discover details about the club’s next meetup.',
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
        'spotlight_for_sale' => 'Shop / Auctions',
        'spotlight_auction_live' => 'Search',
        'spotlight_sub_1' => 'Shop / Auctions',
        'spotlight_sub_2' => 'Classifieds',
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
        'read_news' => 'Nachricht lesen',
        'news_fallback' => 'Lesen Sie die neueste Neuigkeit des Clubs.',
        'no_event' => 'Derzeit ist keine Veranstaltung geplant.',
        'event_date_tbd' => 'Datum wird bestätigt',
        'event_fallback' => 'Entdecken Sie die Details zum nächsten Clubtreffen.',
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
        'spotlight_for_sale' => 'Shop / Auktionen',
        'spotlight_auction_live' => 'Suchen',
        'spotlight_sub_1' => 'Shop / Auktionen',
        'spotlight_sub_2' => 'Kleinanzeigen',
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
        'read_news' => 'Nieuws lezen',
        'news_fallback' => 'Bekijk het nieuwste nieuws van de club.',
        'no_event' => 'Er staat momenteel geen evenement gepland.',
        'event_date_tbd' => 'Datum te bevestigen',
        'event_fallback' => 'Ontdek de details van de volgende clubbijeenkomst.',
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
        'spotlight_for_sale' => 'Winkel / Veilingen',
        'spotlight_auction_live' => 'Zoeken',
        'spotlight_sub_1' => 'Winkel / Veilingen',
        'spotlight_sub_2' => 'Kleine annonces',
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
        'read_news' => 'Leer la noticia',
        'news_fallback' => 'Consulte la última noticia del club.',
        'no_event' => 'No hay ningún evento programado por ahora.',
        'event_date_tbd' => 'Fecha por confirmar',
        'event_fallback' => 'Descubra los detalles del próximo encuentro del club.',
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
        'spotlight_sub_2' => 'Clasificados',
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
        'read_news' => 'Leggi la notizia',
        'news_fallback' => 'Consulta l’ultima notizia del club.',
        'no_event' => 'Nessun evento programmato al momento.',
        'event_date_tbd' => 'Data da confermare',
        'event_fallback' => 'Scopri i dettagli del prossimo incontro del club.',
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
        'spotlight_sub_2' => 'Annunci',
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
        'read_news' => 'Ler notícia',
        'news_fallback' => 'Consulte a notícia mais recente do clube.',
        'no_event' => 'Não há evento agendado de momento.',
        'event_date_tbd' => 'Data por confirmar',
        'event_fallback' => 'Descubra os detalhes do próximo encontro do clube.',
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
        'map_title' => 'Google Map - Radio Club Durnal',
        'address_title' => 'Endereço',
        'contact_people' => 'Pessoas de contacto',
        'club_spotlight_title' => 'Destaques do clube',
        'latest_news_title' => 'Última notícia',
        'next_event_title' => 'Próximo evento',
        'ad_title' => 'Publicidade',
        'spotlight_tool_day' => 'Ferramenta do dia',
        'spotlight_for_sale' => 'Loja / Leilões',
        'spotlight_auction_live' => 'Pesquisar',
        'spotlight_sub_1' => 'Loja / Leilões',
        'spotlight_sub_2' => 'Classificados',
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

];
$homeI18n = $homeMessages[$homeLocale] ?? $homeMessages['fr'];
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
        'contact_email' => 'Email',
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
        'link_sponsoring' => 'Sponsoring',
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
        'journalist_title' => 'Sei giornalista?', 'journalist_desc' => 'Accedi direttamente al nostro dossier stampa per preparare articoli e servizi.', 'journalist_cta' => 'Consulta il dossier stampa', 'teacher_title' => 'Sei insegnante?', 'teacher_desc' => 'Trova i nostri materiali didattici per attività scolastiche e progetti educativi.', 'teacher_cta' => 'Vedi materiali didattici', 'join_title' => 'Pronto a unirti a una comunità radio attiva e strutturata?', 'join_desc' => 'La nuova home evidenzia i moduli chiave per trovare rapidamente informazioni utili e partecipare ai progetti ON4CRD.', 'contact_title' => 'Contattaci', 'contact_name' => 'Nome', 'contact_email' => 'Email', 'contact_message' => 'Messaggio', 'contact_send' => 'Invia', 'important_info_title' => 'Informazioni importanti', 'link_terms' => 'Condizioni generali d’uso', 'link_legal' => 'Note legali', 'link_internal_rules' => 'Regolamento interno', 'link_donate' => 'Fai una donazione', 'link_sponsoring' => 'Sponsorizzazione', 'link_code_q' => 'Codice Q', 'link_code_cw' => 'Codice CW', 'link_bandplan_on3' => 'Band plan ON3', 'link_bandplan_on2' => 'Band plan ON2', 'link_bandplan_harec' => 'Band plan HAREC', 'utc_datetime' => 'Data/ora UTC', 'local_datetime' => 'Data/ora locale', 'hero_tagline' => 'ON4CRD · Connettere, sperimentare, condividere', 'ham_weather_aria' => 'Meteo radioamatoriale', 'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode', 'quote_aria' => 'Citazione in evidenza', 'clock_aria' => 'Orologi UTC e locale',
    ],
    'pt' => [
        'journalist_title' => 'É jornalista?', 'journalist_desc' => 'Aceda diretamente ao nosso dossier de imprensa para preparar publicações e reportagens.', 'journalist_cta' => 'Ver dossier de imprensa', 'teacher_title' => 'É professor?', 'teacher_desc' => 'Encontre os nossos recursos pedagógicos para atividades escolares e projetos educativos.', 'teacher_cta' => 'Ver recursos pedagógicos', 'join_title' => 'Pronto para se juntar a uma comunidade de rádio ativa e estruturada?', 'join_desc' => 'A nova página inicial destaca os módulos principais para encontrar rapidamente informação útil e participar nos projetos ON4CRD.', 'contact_title' => 'Contacte-nos', 'contact_name' => 'Nome', 'contact_email' => 'Email', 'contact_message' => 'Mensagem', 'contact_send' => 'Enviar', 'important_info_title' => 'Informações importantes', 'link_terms' => 'Condições gerais de utilização', 'link_legal' => 'Aviso legal', 'link_internal_rules' => 'Regulamento interno', 'link_donate' => 'Fazer um donativo', 'link_sponsoring' => 'Patrocínio', 'link_code_q' => 'Código Q', 'link_code_cw' => 'Código CW', 'link_bandplan_on3' => 'Plano de bandas ON3', 'link_bandplan_on2' => 'Plano de bandas ON2', 'link_bandplan_harec' => 'Plano de bandas HAREC', 'utc_datetime' => 'Data/hora UTC', 'local_datetime' => 'Data/hora local', 'hero_tagline' => 'ON4CRD · Ligar, experimentar, partilhar', 'ham_weather_aria' => 'Meteo radioamador', 'venue_address' => 'Bocq Arena, Rue des Écoles, 5530 Purnode', 'quote_aria' => 'Citação em destaque', 'clock_aria' => 'Relógios UTC e local',
    ],
];
$homeI18n = array_merge($homeI18n, $homeExtraMessages[$homeLocale] ?? $homeExtraMessages['fr']);
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
];
$moduleVisibilityLabels = $visibilityLabels[$homeLocale] ?? $visibilityLabels['fr'];
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
    $moduleTitle = is_array($module['title'] ?? null) ? (string) (($module['title'][$homeLocale] ?? $module['title']['fr'] ?? '')) : (string) ($module['title'] ?? '');
    $moduleDesc = is_array($module['desc'] ?? null) ? (string) (($module['desc'][$homeLocale] ?? $module['desc']['fr'] ?? '')) : (string) ($module['desc'] ?? '');
    $moduleAudience = is_array($module['audience'] ?? null) ? (string) (($module['audience'][$homeLocale] ?? $module['audience']['fr'] ?? '')) : (string) ($module['audience'] ?? '');
    $moduleAudienceCode = (string) ($module['code'] ?? $module['module'] ?? '');
    $configuredVisibility = (string) ($moduleVisibilityByCode[$moduleAudienceCode] ?? '');
    if ($configuredVisibility !== '') {
        $moduleAudience = (string) ($moduleVisibilityLabels[$configuredVisibility] ?? ucfirst($configuredVisibility));
    } elseif ($moduleAudience === '') {
        $moduleAudience = (string) ($moduleVisibilityLabels['members'] ?? 'Membres');
    }
    $moduleIcon = is_array($module['icon'] ?? null) ? (string) (($module['icon'][$homeLocale] ?? $module['icon']['fr'] ?? '📦')) : (string) ($module['icon'] ?? '📦');

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
            . '<h3 class="text-lg font-semibold text-slate-900">' . e((string) (($moduleMeta['title'][$homeLocale] ?? $moduleMeta['title']['fr'] ?? $moduleCode))) . '</h3>'
            . '<span class="text-xl" aria-hidden="true">' . e((string) ($moduleMeta['icon'] ?? '📦')) . '</span>'
            . '</div>'
            . '<p class="mt-2 text-sm text-slate-600">' . e((string) (($moduleMeta['desc'][$homeLocale] ?? $moduleMeta['desc']['fr'] ?? ''))) . '</p>'
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
