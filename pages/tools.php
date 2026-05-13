<?php
declare(strict_types=1);

$locale = current_locale();
$i18n = [
    'fr' => ['title'=>'Boite à outils','tool_index'=>'Classement des outils','category_locators'=>'Locators & géolocalisation','category_conversions'=>'Conversions radio','category_antenna'=>'Antennes & propagation','category_power'=>'Alimentation & énergie','category_propagation_adv'=>'Propagation avancée','category_rf_measures'=>'Mesures RF','category_radio_math'=>'Mathématiques radio','open_category'=>'Voir les outils','choose_tool'=>'Choisir un outil','grid_title'=>'Calcul du grid depuis une adresse postale','address'=>'Adresse postale','calc_grid'=>'Calculer le grid','found_address'=>'Adresse trouvée','coords'=>'Coordonnées','locator'=>'Locator Maidenhead','conv'=>'Conversions utiles','freq_wave'=>'Fréquence → longueur d’onde','freq_mhz'=>'Fréquence (MHz)','wavelength'=>'Longueur d’onde','power'=>'Puissance (W ↔ dBm)','watts'=>'Watts (W)','distance'=>'Distance entre 2 locators','distance_calc'=>'Calcul de distance entre 2 locators','locator_a'=>'Locator A','locator_b'=>'Locator B','estimated_distance'=>'Distance estimée','filter_calc'=>'Calcul filtre LC','cutoff_freq'=>'Fréquence de coupure (MHz)','impedance'=>'Impédance (Ω)','inductance'=>'Inductance (µH)','capacitance'=>'Capacité (pF)','balun_calc'=>'Calcul balun','source_imp'=>'Impédance source (Ω)','load_imp'=>'Impédance charge (Ω)','turns_ratio'=>'Rapport de spires','swr_calc'=>'Calcul ROS / Return Loss','swr'=>'ROS','return_loss'=>'Return loss (dB)','coax_calc'=>'Perte coaxiale','length_m'=>'Longueur (m)','atten_100m'=>'Atténuation câble (dB/100m)','coax_loss'=>'Perte estimée (dB)','erp_calc'=>'Puissance ERP approximative','tx_power_w'=>'Puissance TX (W)','feedline_loss_db'=>'Perte ligne (dB)','antenna_gain_dbd'=>'Gain antenne (dBd)','erp_result'=>'ERP estimée (W)','quarter_wave_calc'=>'Longueur quart d’onde','velocity_factor'=>'Facteur de vélocité (0-1)','quarter_wave_result'=>'Longueur estimée','err_enter_address'=>'Veuillez saisir une adresse postale.','err_geocode_unavailable'=>'Service de géocodage indisponible.','err_address_not_found'=>'Adresse introuvable. Essayez avec ville et code postal.','err_invalid_coords'=>'Coordonnées invalides reçues.','err_grid_calc'=>'Erreur lors du calcul du grid.','addr_ph'=>'Ex: Rue des Écoles 1, 5530 Purnode, Belgique','freq_ph'=>'Ex: 145.500','watts_ph'=>'Ex: 10','dbm_ph'=>'Ex: 40','locator_a_ph'=>'Ex: JO20LI','locator_b_ph'=>'Ex: JN18EU','dbm_label'=>'dBm','watts_out_label'=>'Watts','meters_unit'=>'m','km_unit'=>'km','fspl_calc'=>'Calcul perte en espace libre (FSPL)','distance_km'=>'Distance (km)','frequency_mhz'=>'Fréquence (MHz)','fspl_result'=>'Perte estimée','runtime_calc'=>'Autonomie batterie','capacity_mah'=>'Capacité batterie (mAh)','current_ma'=>'Courant (mA)','runtime_result'=>'Autonomie estimée','bandwidth_calc'=>'Calcul largeur de bande','mode_rate'=>'Débit mode (bauds)','rolloff_factor'=>'Facteur roll-off','bandwidth_result'=>'Largeur de bande estimée','dipole_calc'=>'Longueur dipôle demi-onde','dipole_total_length'=>'Longueur totale estimée','duty_cycle_calc'=>'Cycle de service','tx_time_sec'=>'Temps émission (s)','period_sec'=>'Période totale (s)','duty_cycle_result'=>'Cycle de service','divider_calc'=>'Diviseur de tension','vin_volts'=>'Tension entrée (V)','r1_ohm'=>'R1 (Ω)','r2_ohm'=>'R2 (Ω)','vout_volts'=>'Tension sortie estimée (V)','mismatch_loss_calc'=>'Perte de désadaptation','reflection_coeff'=>'Coefficient de réflexion','mismatch_loss_result'=>'Perte estimée (dB)','solar_calc'=>'Autonomie solaire simplifiée','panel_watts'=>'Puissance panneau (W)','sun_hours'=>'Heures solaires utiles','daily_energy_wh'=>'Énergie/jour estimée (Wh)','battery_current_calc'=>'Courant batterie estimé','battery_voltage_v'=>'Tension batterie (V)','load_power_w'=>'Puissance charge (W)','battery_current_a'=>'Courant estimé (A)','muftool_calc'=>'MUF simplifiée','critical_freq_mhz'=>'Fréquence critique foF2 (MHz)','incidence_deg'=>'Angle incidence (°)','muf_result'=>'MUF estimée (MHz)','skip_calc'=>'Distance de saut simplifiée','virtual_height_km'=>'Hauteur virtuelle (km)','skip_result_km'=>'Distance de saut estimée (km)','eirp_calc'=>'Conversion ERP → EIRP','erp_input_w'=>'ERP entrée (W)','eirp_result_w'=>'EIRP estimée (W)','db_sum_calc'=>'Somme de puissances (dBm)','dbm_a'=>'Niveau A (dBm)','dbm_b'=>'Niveau B (dBm)','dbm_sum_result'=>'Puissance totale (dBm)','gain_conv_calc'=>'Conversion dBd ↔ dBi','dbd_label'=>'dBd','dbi_label'=>'dBi','dbi_result'=>'Gain en dBi','dbuv_calc'=>'Conversion dBm ↔ dBµV (50Ω)','dbuv_label'=>'dBµV','dbm_to_dbuv_result'=>'dBµV estimé' ],
    'en' => ['title'=>'Toolbox','tool_index'=>'Tool index','category_locators'=>'Locators & geolocation','category_conversions'=>'Radio conversions','category_antenna'=>'Antenna & propagation','category_power'=>'Power & energy','category_propagation_adv'=>'Advanced propagation','category_rf_measures'=>'RF measurements','category_radio_math'=>'Radio math','open_category'=>'View tools','choose_tool'=>'Choose a tool','grid_title'=>'Grid calculation from a postal address','address'=>'Postal address','calc_grid'=>'Calculate grid','found_address'=>'Found address','coords'=>'Coordinates','locator'=>'Maidenhead locator','conv'=>'Useful conversions','freq_wave'=>'Frequency → wavelength','freq_mhz'=>'Frequency (MHz)','wavelength'=>'Wavelength','power'=>'Power (W ↔ dBm)','watts'=>'Watts (W)','distance'=>'Distance between 2 locators','distance_calc'=>'Distance calculator between 2 locators','locator_a'=>'Locator A','locator_b'=>'Locator B','estimated_distance'=>'Estimated distance','filter_calc'=>'LC filter calculator','cutoff_freq'=>'Cutoff frequency (MHz)','impedance'=>'Impedance (Ω)','inductance'=>'Inductance (µH)','capacitance'=>'Capacitance (pF)','balun_calc'=>'Balun calculator','source_imp'=>'Source impedance (Ω)','load_imp'=>'Load impedance (Ω)','turns_ratio'=>'Turns ratio','swr_calc'=>'SWR / Return loss calculator','swr'=>'SWR','return_loss'=>'Return loss (dB)','coax_calc'=>'Coax loss','length_m'=>'Length (m)','atten_100m'=>'Cable attenuation (dB/100m)','coax_loss'=>'Estimated loss (dB)','erp_calc'=>'Estimated ERP','tx_power_w'=>'TX power (W)','feedline_loss_db'=>'Feedline loss (dB)','antenna_gain_dbd'=>'Antenna gain (dBd)','erp_result'=>'Estimated ERP (W)','quarter_wave_calc'=>'Quarter-wave length','velocity_factor'=>'Velocity factor (0-1)','quarter_wave_result'=>'Estimated length','err_enter_address'=>'Please enter a postal address.','err_geocode_unavailable'=>'Geocoding service unavailable.','err_address_not_found'=>'Address not found. Try with city and postal code.','err_invalid_coords'=>'Received invalid coordinates.','err_grid_calc'=>'Error while calculating grid.','addr_ph'=>'Example: Baker Street 221B, London, UK','freq_ph'=>'E.g.: 145.500','watts_ph'=>'E.g.: 10','dbm_ph'=>'E.g.: 40','locator_a_ph'=>'E.g.: JO20LI','locator_b_ph'=>'E.g.: JN18EU','dbm_label'=>'dBm','watts_out_label'=>'Watts','meters_unit'=>'m','km_unit'=>'km','fspl_calc'=>'Free-space path loss (FSPL)','distance_km'=>'Distance (km)','frequency_mhz'=>'Frequency (MHz)','fspl_result'=>'Estimated loss','runtime_calc'=>'Battery runtime','capacity_mah'=>'Battery capacity (mAh)','current_ma'=>'Current draw (mA)','runtime_result'=>'Estimated runtime','bandwidth_calc'=>'Bandwidth calculator','mode_rate'=>'Mode rate (baud)','rolloff_factor'=>'Roll-off factor','bandwidth_result'=>'Estimated bandwidth','dipole_calc'=>'Half-wave dipole length','dipole_total_length'=>'Estimated total length','duty_cycle_calc'=>'Duty cycle','tx_time_sec'=>'TX time (s)','period_sec'=>'Total period (s)','duty_cycle_result'=>'Duty cycle','divider_calc'=>'Voltage divider','vin_volts'=>'Input voltage (V)','r1_ohm'=>'R1 (Ω)','r2_ohm'=>'R2 (Ω)','vout_volts'=>'Estimated output voltage (V)','mismatch_loss_calc'=>'Mismatch loss','reflection_coeff'=>'Reflection coefficient','mismatch_loss_result'=>'Estimated loss (dB)','solar_calc'=>'Simple solar autonomy','panel_watts'=>'Panel power (W)','sun_hours'=>'Useful sun hours','daily_energy_wh'=>'Estimated energy/day (Wh)','battery_current_calc'=>'Estimated battery current','battery_voltage_v'=>'Battery voltage (V)','load_power_w'=>'Load power (W)','battery_current_a'=>'Estimated current (A)','muftool_calc'=>'Simplified MUF','critical_freq_mhz'=>'Critical frequency foF2 (MHz)','incidence_deg'=>'Incidence angle (°)','muf_result'=>'Estimated MUF (MHz)','skip_calc'=>'Simplified skip distance','virtual_height_km'=>'Virtual height (km)','skip_result_km'=>'Estimated skip distance (km)','eirp_calc'=>'ERP → EIRP conversion','erp_input_w'=>'Input ERP (W)','eirp_result_w'=>'Estimated EIRP (W)','db_sum_calc'=>'Power sum (dBm)','dbm_a'=>'Level A (dBm)','dbm_b'=>'Level B (dBm)','dbm_sum_result'=>'Total power (dBm)','gain_conv_calc'=>'dBd ↔ dBi conversion','dbd_label'=>'dBd','dbi_label'=>'dBi','dbi_result'=>'Gain in dBi','dbuv_calc'=>'dBm ↔ dBµV conversion (50Ω)','dbuv_label'=>'dBµV','dbm_to_dbuv_result'=>'Estimated dBµV'],
    'de' => ['title'=>'Werkzeuge','tool_index'=>'Werkzeugübersicht','category_locators'=>'Locatoren & Geolokalisierung','category_conversions'=>'Funk-Umrechnungen','category_antenna'=>'Antennen & Ausbreitung','category_power'=>'Stromversorgung & Energie','category_propagation_adv'=>'Erweiterte Ausbreitung','category_rf_measures'=>'HF-Messungen','category_radio_math'=>'Funkmathematik','open_category'=>'Werkzeuge anzeigen','choose_tool'=>'Werkzeug wählen','grid_title'=>'Grid-Berechnung aus einer Postadresse','address'=>'Postadresse','calc_grid'=>'Grid berechnen','found_address'=>'Gefundene Adresse','coords'=>'Koordinaten','locator'=>'Maidenhead-Locator','conv'=>'Nützliche Umrechnungen','freq_wave'=>'Frequenz → Wellenlänge','freq_mhz'=>'Frequenz (MHz)','wavelength'=>'Wellenlänge','power'=>'Leistung (W ↔ dBm)','watts'=>'Watt (W)','distance'=>'Entfernung zwischen 2 Locatoren','locator_a'=>'Locator A','locator_b'=>'Locator B','estimated_distance'=>'Geschätzte Entfernung','filter_calc'=>'LC-Filterrechner','cutoff_freq'=>'Grenzfrequenz (MHz)','impedance'=>'Impedanz (Ω)','inductance'=>'Induktivität (µH)','capacitance'=>'Kapazität (pF)','balun_calc'=>'Balun-Rechner','source_imp'=>'Quellimpedanz (Ω)','load_imp'=>'Lastimpedanz (Ω)','turns_ratio'=>'Windungsverhältnis','swr_calc'=>'SWR-/Return-Loss-Rechner','swr'=>'SWR','return_loss'=>'Return Loss (dB)','coax_calc'=>'Koax-Dämpfung','length_m'=>'Länge (m)','atten_100m'=>'Kabeldämpfung (dB/100m)','coax_loss'=>'Geschätzte Dämpfung (dB)','erp_calc'=>'Geschätzte ERP','tx_power_w'=>'Sendeleistung (W)','feedline_loss_db'=>'Leitungsverlust (dB)','antenna_gain_dbd'=>'Antennengewinn (dBd)','erp_result'=>'Geschätzte ERP (W)','quarter_wave_calc'=>'Viertelwellenlänge','velocity_factor'=>'Verkürzungsfaktor (0-1)','quarter_wave_result'=>'Geschätzte Länge','err_enter_address'=>'Bitte geben Sie eine Postadresse ein.','err_geocode_unavailable'=>'Geokodierungsdienst nicht verfügbar.','err_address_not_found'=>'Adresse nicht gefunden. Versuchen Sie es mit Stadt und Postleitzahl.','err_invalid_coords'=>'Ungültige Koordinaten empfangen.','err_grid_calc'=>'Fehler bei der Grid-Berechnung.','addr_ph'=>'Beispiel: Hauptstraße 1, 10115 Berlin, Deutschland','freq_ph'=>'Bsp.: 145.500','watts_ph'=>'Bsp.: 10','dbm_ph'=>'Bsp.: 40','locator_a_ph'=>'Bsp.: JO20LI','locator_b_ph'=>'Bsp.: JN18EU','dbm_label'=>'dBm','watts_out_label'=>'Watt','meters_unit'=>'m','km_unit'=>'km','fspl_calc'=>'Freiraumdämpfung (FSPL)','distance_km'=>'Entfernung (km)','frequency_mhz'=>'Frequenz (MHz)','fspl_result'=>'Geschätzte Dämpfung','runtime_calc'=>'Batterielaufzeit','capacity_mah'=>'Batteriekapazität (mAh)','current_ma'=>'Stromaufnahme (mA)','runtime_result'=>'Geschätzte Laufzeit','bandwidth_calc'=>'Bandbreitenrechner','mode_rate'=>'Modulationsrate (Baud)','rolloff_factor'=>'Roll-off-Faktor','bandwidth_result'=>'Geschätzte Bandbreite','dipole_calc'=>'Halbwellen-Dipollänge','dipole_total_length'=>'Geschätzte Gesamtlänge','duty_cycle_calc'=>'Tastverhältnis','tx_time_sec'=>'Sendezeit (s)','period_sec'=>'Gesamtperiode (s)','duty_cycle_result'=>'Tastverhältnis','divider_calc'=>'Spannungsteiler','vin_volts'=>'Eingangsspannung (V)','r1_ohm'=>'R1 (Ω)','r2_ohm'=>'R2 (Ω)','vout_volts'=>'Geschätzte Ausgangsspannung (V)','mismatch_loss_calc'=>'Fehlanpassungsverlust','reflection_coeff'=>'Reflexionskoeffizient','mismatch_loss_result'=>'Geschätzter Verlust (dB)','solar_calc'=>'Vereinfachte Solarautonomie','panel_watts'=>'Panelleistung (W)','sun_hours'=>'Nutzbare Sonnenstunden','daily_energy_wh'=>'Geschätzte Energie/Tag (Wh)','battery_current_calc'=>'Geschätzter Batteriestrom','battery_voltage_v'=>'Batteriespannung (V)','load_power_w'=>'Lastleistung (W)','battery_current_a'=>'Geschätzter Strom (A)','muftool_calc'=>'Vereinfachte MUF','critical_freq_mhz'=>'Kritische Frequenz foF2 (MHz)','incidence_deg'=>'Einfallswinkel (°)','muf_result'=>'Geschätzte MUF (MHz)','skip_calc'=>'Vereinfachte Sprungdistanz','virtual_height_km'=>'Virtuelle Höhe (km)','skip_result_km'=>'Geschätzte Sprungdistanz (km)','eirp_calc'=>'ERP → EIRP Umrechnung','erp_input_w'=>'ERP Eingabe (W)','eirp_result_w'=>'Geschätzte EIRP (W)','db_sum_calc'=>'Leistungssumme (dBm)','dbm_a'=>'Pegel A (dBm)','dbm_b'=>'Pegel B (dBm)','dbm_sum_result'=>'Gesamtleistung (dBm)','gain_conv_calc'=>'dBd ↔ dBi Umrechnung','dbd_label'=>'dBd','dbi_label'=>'dBi','dbi_result'=>'Gewinn in dBi','dbuv_calc'=>'dBm ↔ dBµV Umrechnung (50Ω)','dbuv_label'=>'dBµV','dbm_to_dbuv_result'=>'Geschätztes dBµV'],
    'nl' => ['title'=>'Tools','tool_index'=>'Tools overzicht','category_locators'=>'Locators & geolocatie','category_conversions'=>'Radio conversies','category_antenna'=>'Antennes & propagatie','category_power'=>'Voeding & energie','category_propagation_adv'=>'Geavanceerde propagatie','category_rf_measures'=>'RF-metingen','category_radio_math'=>'Radiowiskunde','open_category'=>'Bekijk tools','choose_tool'=>'Kies een tool','grid_title'=>'Grid berekenen vanaf een postadres','address'=>'Postadres','calc_grid'=>'Grid berekenen','found_address'=>'Gevonden adres','coords'=>'Coördinaten','locator'=>'Maidenhead-locator','conv'=>'Nuttige conversies','freq_wave'=>'Frequentie → golflengte','freq_mhz'=>'Frequentie (MHz)','wavelength'=>'Golflengte','power'=>'Vermogen (W ↔ dBm)','watts'=>'Watt (W)','distance'=>'Afstand tussen 2 locators','locator_a'=>'Locator A','locator_b'=>'Locator B','estimated_distance'=>'Geschatte afstand','filter_calc'=>'LC-filtercalculator','cutoff_freq'=>'Afsnijfrequentie (MHz)','impedance'=>'Impedantie (Ω)','inductance'=>'Inductantie (µH)','capacitance'=>'Capaciteit (pF)','balun_calc'=>'Baluncalculator','source_imp'=>'Bronimpedantie (Ω)','load_imp'=>'Belastingsimpedantie (Ω)','turns_ratio'=>'Wikkelverhouding','swr_calc'=>'SWR/return-loss-calculator','swr'=>'SWR','return_loss'=>'Return loss (dB)','coax_calc'=>'Coaxverlies','length_m'=>'Lengte (m)','atten_100m'=>'Kabeldemping (dB/100m)','coax_loss'=>'Geschat verlies (dB)','erp_calc'=>'Geschatte ERP','tx_power_w'=>'Zendvermogen (W)','feedline_loss_db'=>'Lijnverlies (dB)','antenna_gain_dbd'=>'Antenneversterking (dBd)','erp_result'=>'Geschatte ERP (W)','quarter_wave_calc'=>'Kwartgolflengte','velocity_factor'=>'Snelheidsfactor (0-1)','quarter_wave_result'=>'Geschatte lengte','err_enter_address'=>'Voer een postadres in.','err_geocode_unavailable'=>'Geocodingservice niet beschikbaar.','err_address_not_found'=>'Adres niet gevonden. Probeer met stad en postcode.','err_invalid_coords'=>'Ongeldige coördinaten ontvangen.','err_grid_calc'=>'Fout bij het berekenen van het grid.','addr_ph'=>'Voorbeeld: Kerkstraat 1, 1000 Brussel, België','freq_ph'=>'Bijv.: 145.500','watts_ph'=>'Bijv.: 10','dbm_ph'=>'Bijv.: 40','locator_a_ph'=>'Bijv.: JO20LI','locator_b_ph'=>'Bijv.: JN18EU','dbm_label'=>'dBm','watts_out_label'=>'Watt','meters_unit'=>'m','km_unit'=>'km','fspl_calc'=>'Vrije-ruimte verzwakking (FSPL)','distance_km'=>'Afstand (km)','frequency_mhz'=>'Frequentie (MHz)','fspl_result'=>'Geschat verlies','runtime_calc'=>'Batterijduur','capacity_mah'=>'Batterijcapaciteit (mAh)','current_ma'=>'Stroomverbruik (mA)','runtime_result'=>'Geschatte duur','bandwidth_calc'=>'Bandbreedtecalculator','mode_rate'=>'Modus-snelheid (baud)','rolloff_factor'=>'Roll-off-factor','bandwidth_result'=>'Geschatte bandbreedte','dipole_calc'=>'Halvegolf dipoollengte','dipole_total_length'=>'Geschatte totale lengte','duty_cycle_calc'=>'Dutycycle','tx_time_sec'=>'Zendtijd (s)','period_sec'=>'Totale periode (s)','duty_cycle_result'=>'Dutycycle','divider_calc'=>'Spanningsdeler','vin_volts'=>'Ingangsspanning (V)','r1_ohm'=>'R1 (Ω)','r2_ohm'=>'R2 (Ω)','vout_volts'=>'Geschatte uitgangsspanning (V)','mismatch_loss_calc'=>'Mismatch-verlies','reflection_coeff'=>'Reflectiecoëfficiënt','mismatch_loss_result'=>'Geschat verlies (dB)','solar_calc'=>'Vereenvoudigde zonne-autonomie','panel_watts'=>'Paneelvermogen (W)','sun_hours'=>'Nuttige zonuren','daily_energy_wh'=>'Geschatte energie/dag (Wh)','battery_current_calc'=>'Geschatte accustroom','battery_voltage_v'=>'Accuspanning (V)','load_power_w'=>'Belastingsvermogen (W)','battery_current_a'=>'Geschatte stroom (A)','muftool_calc'=>'Vereenvoudigde MUF','critical_freq_mhz'=>'Kritische frequentie foF2 (MHz)','incidence_deg'=>'Invalshoek (°)','muf_result'=>'Geschatte MUF (MHz)','skip_calc'=>'Vereenvoudigde skipafstand','virtual_height_km'=>'Virtuele hoogte (km)','skip_result_km'=>'Geschatte skipafstand (km)','eirp_calc'=>'ERP → EIRP conversie','erp_input_w'=>'Invoer ERP (W)','eirp_result_w'=>'Geschatte EIRP (W)','db_sum_calc'=>'Vermogenssom (dBm)','dbm_a'=>'Niveau A (dBm)','dbm_b'=>'Niveau B (dBm)','dbm_sum_result'=>'Totaal vermogen (dBm)','gain_conv_calc'=>'dBd ↔ dBi conversie','dbd_label'=>'dBd','dbi_label'=>'dBi','dbi_result'=>'Versterking in dBi','dbuv_calc'=>'dBm ↔ dBµV conversie (50Ω)','dbuv_label'=>'dBµV','dbm_to_dbuv_result'=>'Geschatte dBµV'],
];
$t = $i18n[$locale] ?? $i18n['fr'];
$labelCategoryAntenna = (string) ($t['category_antenna'] ?? 'Antenna & propagation');
$labelQuarterWaveCalc = (string) ($t['quarter_wave_calc'] ?? 'Quarter-wave length');
$labelErpCalc = (string) ($t['erp_calc'] ?? 'Estimated ERP');
$labelTxPowerW = (string) ($t['tx_power_w'] ?? 'TX power (W)');
$labelFeedlineLossDb = (string) ($t['feedline_loss_db'] ?? 'Feedline loss (dB)');
$labelAntennaGainDbd = (string) ($t['antenna_gain_dbd'] ?? 'Antenna gain (dBd)');
$labelErpResult = (string) ($t['erp_result'] ?? 'Estimated ERP');
$labelQuarterWaveResult = (string) ($t['quarter_wave_result'] ?? 'Estimated length');
$labelVelocityFactor = (string) ($t['velocity_factor'] ?? 'Velocity factor (0-1)');

$conversionTools = [
    ['id' => 'tool-freq-wave', 'title' => ((string) $t['conv']) . ' · ' . ((string) $t['freq_wave'])],
    ['id' => 'tool-power', 'title' => ((string) $t['conv']) . ' · ' . ((string) $t['power'])],
    ['id' => 'tool-filter', 'title' => (string) $t['filter_calc']],
    ['id' => 'tool-balun', 'title' => (string) $t['balun_calc']],
    ['id' => 'tool-swr', 'title' => (string) $t['swr_calc']],
    ['id' => 'tool-fspl', 'title' => (string) $t['fspl_calc']],
    ['id' => 'tool-runtime', 'title' => (string) $t['runtime_calc']],
    ['id' => 'tool-coax', 'title' => (string) $t['coax_calc']],
    ['id' => 'tool-bandwidth', 'title' => (string) $t['bandwidth_calc']],
    ['id' => 'tool-duty', 'title' => (string) $t['duty_cycle_calc']],
    ['id' => 'tool-divider', 'title' => (string) $t['divider_calc']],
    ['id' => 'tool-mismatch', 'title' => (string) $t['mismatch_loss_calc']],
];
$antennaTools = [
    ['id' => 'tool-quarter-wave', 'title' => $labelQuarterWaveCalc],
    ['id' => 'tool-erp', 'title' => $labelErpCalc],
    ['id' => 'tool-dipole', 'title' => (string) $t['dipole_calc']],
];
$powerTools = [
    ['id' => 'tool-solar', 'title' => (string) $t['solar_calc']],
    ['id' => 'tool-battery-current', 'title' => (string) $t['battery_current_calc']],
];
$advancedPropagationTools = [
    ['id' => 'tool-muf', 'title' => (string) $t['muftool_calc']],
    ['id' => 'tool-skip', 'title' => (string) $t['skip_calc']],
];
$rfMeasureTools = [
    ['id' => 'tool-eirp', 'title' => (string) $t['eirp_calc']],
];
$radioMathTools = [
    ['id' => 'tool-db-sum', 'title' => (string) $t['db_sum_calc']],
    ['id' => 'tool-dbuv', 'title' => (string) $t['dbuv_calc']],
    ['id' => 'tool-gain-conv', 'title' => (string) $t['gain_conv_calc']],
];
set_page_meta([
    'title' => (string) ($t['title'] ?? $i18n['fr']['title']),
    'description' => (string) ($t['grid_title'] ?? $i18n['fr']['grid_title']),
    'schema_type' => 'WebPage',
]);
$jsI18n = [
    'err_enter_address' => (string) ($t['err_enter_address'] ?? $i18n['fr']['err_enter_address']),
    'err_geocode_unavailable' => (string) ($t['err_geocode_unavailable'] ?? $i18n['fr']['err_geocode_unavailable']),
    'err_address_not_found' => (string) ($t['err_address_not_found'] ?? $i18n['fr']['err_address_not_found']),
    'err_invalid_coords' => (string) ($t['err_invalid_coords'] ?? $i18n['fr']['err_invalid_coords']),
    'err_grid_calc' => (string) ($t['err_grid_calc'] ?? $i18n['fr']['err_grid_calc']),
    'meters_unit' => (string) ($t['meters_unit'] ?? $i18n['fr']['meters_unit']),
    'km_unit' => (string) ($t['km_unit'] ?? $i18n['fr']['km_unit']),
    'watts_out_label' => (string) ($t['watts_out_label'] ?? $i18n['fr']['watts_out_label']),
    'dbuv_label' => (string) ($t['dbuv_label'] ?? ($i18n['fr']['dbuv_label'] ?? 'dBµV')),
];

ob_start();
?>
<section class="card">
    <h1 class="tools-page-title"><?= e((string) $t['title']) ?></h1>
    <div class="tools-layout">
    <aside class="tools-index card">
        <h2><?= e((string) $t['tool_index']) ?></h2>
        <p class="help"><?= e((string) $t['choose_tool']) ?></p>
        <details class="tools-index-group">
            <summary><?= e((string) $t['category_locators']) ?></summary>
            <ul>
                <li><a href="#tool-grid" data-tool-target="tool-grid"><?= e((string) $t['grid_title']) ?></a></li>
                <li><a href="#tool-distance" data-tool-target="tool-distance"><?= e((string) $t['distance']) ?></a></li>
            </ul>
        </details>
        <details class="tools-index-group">
            <summary><?= e((string) $t['category_conversions']) ?></summary>
            <ul>
                <?php foreach ($conversionTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <details class="tools-index-group">
            <summary><?= e($labelCategoryAntenna) ?></summary>
            <ul>
                <?php foreach ($antennaTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details class="tools-index-group">
            <summary><?= e((string) $t['category_power']) ?></summary>
            <ul>
                <?php foreach ($powerTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
        <details class="tools-index-group">
            <summary><?= e((string) $t['category_propagation_adv']) ?></summary>
            <ul>
                <?php foreach ($advancedPropagationTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details class="tools-index-group">
            <summary><?= e((string) $t['category_rf_measures']) ?></summary>
            <ul>
                <?php foreach ($rfMeasureTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>

        <details class="tools-index-group">
            <summary><?= e((string) $t['category_radio_math']) ?></summary>
            <ul>
                <?php foreach ($radioMathTools as $tool): ?>
                    <li><a href="#<?= e((string) $tool['id']) ?>" data-tool-target="<?= e((string) $tool['id']) ?>"><?= e((string) $tool['title']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </details>
    </aside>
    <div class="tools-content">
        <article class="card tool-panel" id="tool-grid" data-tool-panel>
            <h2><?= e((string) $t['grid_title']) ?></h2>
            <form id="grid-tool-form" class="stack">
                <label><?= e((string) $t['address']) ?>
                    <input type="text" id="grid-address" placeholder="<?= e((string) $t['addr_ph']) ?>" required>
                </label>
                <div class="actions">
                    <button type="submit" class="button"><?= e((string) $t['calc_grid']) ?></button>
                </div>
            </form>
            <div id="grid-tool-result" class="card is-hidden" style="margin-top:1rem;">
                <p><strong><?= e((string) $t['found_address']) ?> :</strong> <span id="grid-found-address">—</span></p>
                <p><strong><?= e((string) $t['coords']) ?> :</strong> <span id="grid-found-coords">—</span></p>
                <p><strong><?= e((string) $t['locator']) ?> :</strong> <span id="grid-found-locator">—</span></p>
            </div>
        </article>
        <article class="card tool-panel" id="tool-freq-wave" data-tool-panel>
            <h2><?= e((string) $t['conv']) ?> · <?= e((string) $t['freq_wave']) ?></h2>
            <label><?= e((string) $t['freq_mhz']) ?>
                <input type="number" id="freq-mhz" min="0" step="0.001" placeholder="<?= e((string) $t['freq_ph']) ?>">
            </label>
            <p class="help"><?= e((string) $t['wavelength']) ?>: <strong id="freq-wavelength">—</strong></p>
        </article>
        <article class="card tool-panel" id="tool-power" data-tool-panel>
            <h2><?= e((string) $t['conv']) ?> · <?= e((string) $t['power']) ?></h2>
            <label><?= e((string) $t['watts']) ?>
                <input type="number" id="power-watts" min="0" step="0.001" placeholder="<?= e((string) $t['watts_ph']) ?>">
            </label>
            <p class="help"><?= e((string) $t['dbm_label']) ?>: <strong id="power-dbm">—</strong></p>
            <label><?= e((string) $t['dbm_label']) ?>
                <input type="number" id="power-dbm-input" step="0.1" placeholder="<?= e((string) $t['dbm_ph']) ?>">
            </label>
            <p class="help"><?= e((string) $t['watts_out_label']) ?>: <strong id="power-watts-out">—</strong></p>
        </article>
        <article class="card tool-panel" id="tool-distance" data-tool-panel>
            <h2><?= e((string) ($t['distance_calc'] ?? $t['distance'])) ?></h2>
            <label><?= e((string) $t['locator_a']) ?>
                <input type="text" id="locator-a" maxlength="6" placeholder="<?= e((string) $t['locator_a_ph']) ?>">
            </label>
            <label><?= e((string) $t['locator_b']) ?>
                <input type="text" id="locator-b" maxlength="6" placeholder="<?= e((string) $t['locator_b_ph']) ?>">
            </label>
            <p class="help"><?= e((string) $t['estimated_distance']) ?>: <strong id="locator-distance">—</strong></p>
        </article>
        <article class="card tool-panel" id="tool-filter" data-tool-panel>
            <h2><?= e((string) $t['filter_calc']) ?></h2>
            <label><?= e((string) $t['cutoff_freq']) ?>
                <input type="number" id="filter-freq" min="0" step="0.001" placeholder="<?= e((string) $t['freq_ph']) ?>">
            </label>
            <label><?= e((string) $t['impedance']) ?>
                <input type="number" id="filter-impedance" min="1" step="0.1" value="50">
            </label>
            <p class="help"><?= e((string) $t['inductance']) ?>: <strong id="filter-l">—</strong></p>
            <p class="help"><?= e((string) $t['capacitance']) ?>: <strong id="filter-c">—</strong></p>
        </article>
        <article class="card tool-panel" id="tool-balun" data-tool-panel>
            <h2><?= e((string) $t['balun_calc']) ?></h2>
            <label><?= e((string) $t['source_imp']) ?>
                <input type="number" id="balun-source" min="1" step="0.1" value="50">
            </label>
            <label><?= e((string) $t['load_imp']) ?>
                <input type="number" id="balun-load" min="1" step="0.1" value="200">
            </label>
            <p class="help"><?= e((string) $t['turns_ratio']) ?>: <strong id="balun-ratio">—</strong></p>
        </article>
        <?php require __DIR__ . '/tools_panels/tool_swr.php'; ?>
        <?php require __DIR__ . '/tools_panels/tool_fspl.php'; ?>
        <?php require __DIR__ . '/tools_panels/tool_runtime.php'; ?>
        <?php require __DIR__ . '/tools_panels/tool_coax.php'; ?>

        <?php require __DIR__ . '/tools_panels/tool_bandwidth.php'; ?>

        <?php require __DIR__ . '/tools_panels/tool_duty.php'; ?>
        <?php require __DIR__ . '/tools_panels/tool_divider.php'; ?>


        <?php require __DIR__ . '/tools_panels/tool_mismatch.php'; ?>


        <?php require __DIR__ . '/tools_panels/tool_solar.php'; ?>

        <?php require __DIR__ . '/tools_panels/tool_battery_current.php'; ?>

        <?php require __DIR__ . '/tools_panels/tool_muf.php'; ?>


        <?php require __DIR__ . '/tools_panels/tool_eirp.php'; ?>


        <?php require __DIR__ . '/tools_panels/tool_skip.php'; ?>


        <?php require __DIR__ . '/tools_panels/tool_db_sum.php'; ?>


        <?php require __DIR__ . '/tools_panels/tool_dbuv.php'; ?>


        <?php require __DIR__ . '/tools_panels/tool_gain_conv.php'; ?>

        <?php require __DIR__ . '/tools_panels/tool_quarter_wave.php'; ?>
        <?php require __DIR__ . '/tools_panels/tool_erp.php'; ?>
        <?php require __DIR__ . '/tools_panels/tool_dipole.php'; ?>
    </div>
    </div>
    <p id="grid-tool-error" class="flash flash-error is-hidden" style="margin-top:1rem;"></p>
</section>

<script nonce="<?= e(csp_nonce()) ?>">
(() => {
    const i18n = <?= json_encode($jsI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const form = document.getElementById('grid-tool-form');
    const addressInput = document.getElementById('grid-address');
    const result = document.getElementById('grid-tool-result');
    const foundAddress = document.getElementById('grid-found-address');
    const foundCoords = document.getElementById('grid-found-coords');
    const foundLocator = document.getElementById('grid-found-locator');
    const errorBox = document.getElementById('grid-tool-error');
    const freqInput = document.getElementById('freq-mhz');
    const freqOut = document.getElementById('freq-wavelength');
    const wattsInput = document.getElementById('power-watts');
    const dbmInput = document.getElementById('power-dbm-input');
    const dbmOut = document.getElementById('power-dbm');
    const wattsOut = document.getElementById('power-watts-out');
    const locatorA = document.getElementById('locator-a');
    const locatorB = document.getElementById('locator-b');
    const locatorDistance = document.getElementById('locator-distance');
    const filterFreq = document.getElementById('filter-freq');
    const filterImpedance = document.getElementById('filter-impedance');
    const filterL = document.getElementById('filter-l');
    const filterC = document.getElementById('filter-c');
    const balunSource = document.getElementById('balun-source');
    const balunLoad = document.getElementById('balun-load');
    const balunRatio = document.getElementById('balun-ratio');
    const swrInput = document.getElementById('swr-input');
    const swrRl = document.getElementById('swr-rl');
    const fsplDistance = document.getElementById('fspl-distance');
    const fsplFrequency = document.getElementById('fspl-frequency');
    const fsplLoss = document.getElementById('fspl-loss');
    const runtimeCapacity = document.getElementById('runtime-capacity');
    const runtimeCurrent = document.getElementById('runtime-current');
    const runtimeHours = document.getElementById('runtime-hours');
    const coaxLength = document.getElementById('coax-length');
    const coaxAtten = document.getElementById('coax-atten');
    const coaxLoss = document.getElementById('coax-loss');
    const quarterWaveFrequency = document.getElementById('quarter-wave-frequency');
    const quarterWaveVf = document.getElementById('quarter-wave-vf');
    const quarterWaveLength = document.getElementById('quarter-wave-length');
    const erpPower = document.getElementById('erp-power');
    const erpLoss = document.getElementById('erp-loss');
    const erpGain = document.getElementById('erp-gain');
    const erpResult = document.getElementById('erp-result');
    const bandwidthRate = document.getElementById('bandwidth-rate');
    const bandwidthRolloff = document.getElementById('bandwidth-rolloff');
    const bandwidthResult = document.getElementById('bandwidth-result');
    const dipoleFrequency = document.getElementById('dipole-frequency');
    const dipoleLength = document.getElementById('dipole-length');
    const dutyTx = document.getElementById('duty-tx');
    const dutyPeriod = document.getElementById('duty-period');
    const dutyResult = document.getElementById('duty-result');
    const dividerVin = document.getElementById('divider-vin');
    const dividerR1 = document.getElementById('divider-r1');
    const dividerR2 = document.getElementById('divider-r2');
    const dividerVout = document.getElementById('divider-vout');
    const mismatchSwr = document.getElementById('mismatch-swr');
    const mismatchGamma = document.getElementById('mismatch-gamma');
    const mismatchLoss = document.getElementById('mismatch-loss');
    const solarWatts = document.getElementById('solar-watts');
    const solarHours = document.getElementById('solar-hours');
    const solarEnergy = document.getElementById('solar-energy');
    const mufFof2 = document.getElementById('muf-fof2');
    const mufAngle = document.getElementById('muf-angle');
    const mufResult = document.getElementById('muf-result');
    const batteryVoltage = document.getElementById('battery-voltage');
    const batteryLoad = document.getElementById('battery-load');
    const batteryCurrent = document.getElementById('battery-current');
    const eirpErp = document.getElementById('eirp-erp');
    const eirpResult = document.getElementById('eirp-result');
    const skipHeight = document.getElementById('skip-height');
    const skipAngle = document.getElementById('skip-angle');
    const skipResult = document.getElementById('skip-result');
    const dbsumA = document.getElementById('dbsum-a');
    const dbsumB = document.getElementById('dbsum-b');
    const dbsumResult = document.getElementById('dbsum-result');
    const dbuvDbm = document.getElementById('dbuv-dbm');
    const dbuvResult = document.getElementById('dbuv-result');
    const gainDbd = document.getElementById('gain-dbd');
    const gainDbi = document.getElementById('gain-dbi');
    if (!(form instanceof HTMLFormElement) || !(addressInput instanceof HTMLInputElement)) {
        return;
    }

    const toMaidenhead = (latitude, longitude, precision = 6) => {
        const safeLat = Math.max(-89.999999, Math.min(89.999999, latitude));
        const safeLon = Math.max(-179.999999, Math.min(179.999999, longitude));
        let lon = safeLon + 180;
        let lat = safeLat + 90;
        const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        let locator = '';

        locator += letters[Math.floor(lon / 20)];
        locator += letters[Math.floor(lat / 10)];
        lon %= 20; lat %= 10;
        locator += Math.floor(lon / 2).toString();
        locator += Math.floor(lat).toString();
        lon = (lon % 2) * 12;
        lat = (lat % 1) * 24;
        locator += letters[Math.floor(lon)];
        locator += letters[Math.floor(lat)];

        return locator.slice(0, Math.max(4, Math.min(precision, 6))).toUpperCase();
    };

    const setError = (message) => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove('is-hidden');
    };
    const clearError = () => errorBox?.classList.add('is-hidden');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();
        result?.classList.add('is-hidden');
        const query = addressInput.value.trim();
        if (query === '') {
            setError(i18n.err_enter_address);
            return;
        }
        try {
            const url = `index.php?route=tools_geocode&q=${encodeURIComponent(query)}`;
            const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error(i18n.err_geocode_unavailable);
            }
            const payload = await response.json();
            if (!payload?.ok) {
                throw new Error(payload?.error || i18n.err_address_not_found);
            }
            const lat = Number(payload.lat);
            const lon = Number(payload.lon);
            if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
                throw new Error(i18n.err_invalid_coords);
            }
            const locator = toMaidenhead(lat, lon, 6);
            if (foundAddress) foundAddress.textContent = payload.display_name || query;
            if (foundCoords) foundCoords.textContent = `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
            if (foundLocator) foundLocator.textContent = locator;
            result?.classList.remove('is-hidden');
        } catch (error) {
            setError(error instanceof Error ? error.message : i18n.err_grid_calc);
        }
    });

    freqInput?.addEventListener('input', () => {
        if (!(freqInput instanceof HTMLInputElement) || !freqOut) return;
        const mhz = Number(freqInput.value);
        if (!Number.isFinite(mhz) || mhz <= 0) {
            freqOut.textContent = '—';
            return;
        }
        const meters = 299.792458 / mhz;
        freqOut.textContent = `${meters.toFixed(3)} ${i18n.meters_unit}`;
    });

    wattsInput?.addEventListener('input', () => {
        if (!(wattsInput instanceof HTMLInputElement) || !dbmOut) return;
        const watts = Number(wattsInput.value);
        if (!Number.isFinite(watts) || watts <= 0) {
            dbmOut.textContent = '—';
            return;
        }
        const dbm = 10 * Math.log10(watts * 1000);
        dbmOut.textContent = dbm.toFixed(2);
    });

    dbmInput?.addEventListener('input', () => {
        if (!(dbmInput instanceof HTMLInputElement) || !wattsOut) return;
        const dbm = Number(dbmInput.value);
        if (!Number.isFinite(dbm)) {
            wattsOut.textContent = '—';
            return;
        }
        const watts = Math.pow(10, dbm / 10) / 1000;
        wattsOut.textContent = `${watts.toFixed(4)} ${i18n.watts_out_label || 'W'}`;
    });

    const computeFilter = () => {
        if (!(filterFreq instanceof HTMLInputElement) || !(filterImpedance instanceof HTMLInputElement) || !filterL || !filterC) return;
        const fMHz = Number(filterFreq.value);
        const z = Number(filterImpedance.value);
        if (!Number.isFinite(fMHz) || fMHz <= 0 || !Number.isFinite(z) || z <= 0) {
            filterL.textContent = '—';
            filterC.textContent = '—';
            return;
        }
        const f = fMHz * 1e6;
        const lHenrys = z / (2 * Math.PI * f);
        const cFarads = 1 / (2 * Math.PI * f * z);
        filterL.textContent = `${(lHenrys * 1e6).toFixed(3)} µH`;
        filterC.textContent = `${(cFarads * 1e12).toFixed(2)} pF`;
    };
    filterFreq?.addEventListener('input', computeFilter);
    filterImpedance?.addEventListener('input', computeFilter);
    computeFilter();

    const computeBalun = () => {
        if (!(balunSource instanceof HTMLInputElement) || !(balunLoad instanceof HTMLInputElement) || !balunRatio) return;
        const zin = Number(balunSource.value);
        const zout = Number(balunLoad.value);
        if (!Number.isFinite(zin) || zin <= 0 || !Number.isFinite(zout) || zout <= 0) {
            balunRatio.textContent = '—';
            return;
        }
        const ratio = Math.sqrt(zout / zin);
        const powerRatio = zout / zin;
        balunRatio.textContent = `1:${ratio.toFixed(2)} (Z ${zin}:${zout} ≈ ${powerRatio.toFixed(2)}:1)`;
    };
    balunSource?.addEventListener('input', computeBalun);
    balunLoad?.addEventListener('input', computeBalun);
    computeBalun();

    const computeSWR = () => {
        if (!(swrInput instanceof HTMLInputElement) || !swrRl) return;
        const swr = Number(swrInput.value);
        if (!Number.isFinite(swr) || swr < 1) {
            swrRl.textContent = '—';
            return;
        }
        const gamma = (swr - 1) / (swr + 1);
        const rl = -20 * Math.log10(Math.max(gamma, 1e-12));
        swrRl.textContent = `${rl.toFixed(2)} dB`;
    };
    swrInput?.addEventListener('input', computeSWR);
    computeSWR();

    const computeCoaxLoss = () => {
        if (!(coaxLength instanceof HTMLInputElement) || !(coaxAtten instanceof HTMLInputElement) || !coaxLoss) return;
        const len = Number(coaxLength.value);
        const att = Number(coaxAtten.value);
        if (!Number.isFinite(len) || len < 0 || !Number.isFinite(att) || att < 0) {
            coaxLoss.textContent = '—';
            return;
        }
        const loss = (len / 100) * att;
        coaxLoss.textContent = `${loss.toFixed(2)} dB`;
    };
    coaxLength?.addEventListener('input', computeCoaxLoss);
    coaxAtten?.addEventListener('input', computeCoaxLoss);
    computeCoaxLoss();


    const computeFspl = () => {
        if (!(fsplDistance instanceof HTMLInputElement) || !(fsplFrequency instanceof HTMLInputElement) || !fsplLoss) return;
        const d = Number(fsplDistance.value);
        const f = Number(fsplFrequency.value);
        if (!Number.isFinite(d) || d <= 0 || !Number.isFinite(f) || f <= 0) {
            fsplLoss.textContent = '—';
            return;
        }
        const loss = 32.44 + (20 * Math.log10(d)) + (20 * Math.log10(f));
        fsplLoss.textContent = `${loss.toFixed(2)} dB`;
    };
    fsplDistance?.addEventListener('input', computeFspl);
    fsplFrequency?.addEventListener('input', computeFspl);
    computeFspl();

    const computeRuntime = () => {
        if (!(runtimeCapacity instanceof HTMLInputElement) || !(runtimeCurrent instanceof HTMLInputElement) || !runtimeHours) return;
        const capacity = Number(runtimeCapacity.value);
        const current = Number(runtimeCurrent.value);
        if (!Number.isFinite(capacity) || capacity <= 0 || !Number.isFinite(current) || current <= 0) {
            runtimeHours.textContent = '—';
            return;
        }
        const hours = capacity / current;
        runtimeHours.textContent = `${hours.toFixed(2)} h`;
    };
    runtimeCapacity?.addEventListener('input', computeRuntime);
    runtimeCurrent?.addEventListener('input', computeRuntime);
    computeRuntime();


    const computeBandwidth = () => {
        if (!(bandwidthRate instanceof HTMLInputElement) || !(bandwidthRolloff instanceof HTMLInputElement) || !bandwidthResult) return;
        const rate = Number(bandwidthRate.value);
        const rolloff = Number(bandwidthRolloff.value);
        if (!Number.isFinite(rate) || rate <= 0 || !Number.isFinite(rolloff) || rolloff < 0) {
            bandwidthResult.textContent = '—';
            return;
        }
        const bw = rate * (1 + rolloff);
        bandwidthResult.textContent = `${bw.toFixed(1)} Hz`;
    };
    bandwidthRate?.addEventListener('input', computeBandwidth);
    bandwidthRolloff?.addEventListener('input', computeBandwidth);
    computeBandwidth();

    const computeQuarterWave = () => {
        if (!(quarterWaveFrequency instanceof HTMLInputElement) || !(quarterWaveVf instanceof HTMLInputElement) || !quarterWaveLength) return;
        const f = Number(quarterWaveFrequency.value);
        const vf = Number(quarterWaveVf.value);
        if (!Number.isFinite(f) || f <= 0 || !Number.isFinite(vf) || vf <= 0 || vf > 1) {
            quarterWaveLength.textContent = '—';
            return;
        }
        const meters = (71.25 / f) * vf;
        quarterWaveLength.textContent = `${meters.toFixed(2)} ${i18n.meters_unit}`;
    };
    quarterWaveFrequency?.addEventListener('input', computeQuarterWave);
    quarterWaveVf?.addEventListener('input', computeQuarterWave);
    computeQuarterWave();

    const computeErp = () => {
        if (!(erpPower instanceof HTMLInputElement) || !(erpLoss instanceof HTMLInputElement) || !(erpGain instanceof HTMLInputElement) || !erpResult) return;
        const pwr = Number(erpPower.value);
        const loss = Number(erpLoss.value);
        const gain = Number(erpGain.value);
        if (!Number.isFinite(pwr) || pwr <= 0 || !Number.isFinite(loss) || !Number.isFinite(gain)) {
            erpResult.textContent = '—';
            return;
        }
        const netDb = gain - loss;
        const erp = pwr * (10 ** (netDb / 10));
        erpResult.textContent = `${erp.toFixed(2)} W`;
    };
    erpPower?.addEventListener('input', computeErp);
    erpLoss?.addEventListener('input', computeErp);
    erpGain?.addEventListener('input', computeErp);
    computeErp();


    const computeDipole = () => {
        if (!(dipoleFrequency instanceof HTMLInputElement) || !dipoleLength) return;
        const f = Number(dipoleFrequency.value);
        if (!Number.isFinite(f) || f <= 0) {
            dipoleLength.textContent = '—';
            return;
        }
        const lengthMeters = 143 / f;
        dipoleLength.textContent = `${lengthMeters.toFixed(2)} ${i18n.meters_unit}`;
    };
    dipoleFrequency?.addEventListener('input', computeDipole);
    computeDipole();


    const computeDutyCycle = () => {
        if (!(dutyTx instanceof HTMLInputElement) || !(dutyPeriod instanceof HTMLInputElement) || !dutyResult) return;
        const tx = Number(dutyTx.value);
        const period = Number(dutyPeriod.value);
        if (!Number.isFinite(tx) || tx < 0 || !Number.isFinite(period) || period <= 0 || tx > period) {
            dutyResult.textContent = '—';
            return;
        }
        dutyResult.textContent = `${((tx / period) * 100).toFixed(1)} %`;
    };
    dutyTx?.addEventListener('input', computeDutyCycle);
    dutyPeriod?.addEventListener('input', computeDutyCycle);
    computeDutyCycle();

    const computeDivider = () => {
        if (!(dividerVin instanceof HTMLInputElement) || !(dividerR1 instanceof HTMLInputElement) || !(dividerR2 instanceof HTMLInputElement) || !dividerVout) return;
        const vin = Number(dividerVin.value);
        const r1 = Number(dividerR1.value);
        const r2 = Number(dividerR2.value);
        if (!Number.isFinite(vin) || vin < 0 || !Number.isFinite(r1) || r1 <= 0 || !Number.isFinite(r2) || r2 <= 0) {
            dividerVout.textContent = '—';
            return;
        }
        const vout = vin * (r2 / (r1 + r2));
        dividerVout.textContent = `${vout.toFixed(3)} V`;
    };
    dividerVin?.addEventListener('input', computeDivider);
    dividerR1?.addEventListener('input', computeDivider);
    dividerR2?.addEventListener('input', computeDivider);
    computeDivider();


    const computeMismatchLoss = () => {
        if (!(mismatchSwr instanceof HTMLInputElement) || !mismatchGamma || !mismatchLoss) return;
        const swr = Number(mismatchSwr.value);
        if (!Number.isFinite(swr) || swr < 1) {
            mismatchGamma.textContent = '—';
            mismatchLoss.textContent = '—';
            return;
        }
        const gamma = (swr - 1) / (swr + 1);
        const loss = -10 * Math.log10(Math.max(1 - (gamma * gamma), 1e-12));
        mismatchGamma.textContent = gamma.toFixed(4);
        mismatchLoss.textContent = `${loss.toFixed(3)} dB`;
    };
    mismatchSwr?.addEventListener('input', computeMismatchLoss);
    computeMismatchLoss();


    const computeSolarEnergy = () => {
        if (!(solarWatts instanceof HTMLInputElement) || !(solarHours instanceof HTMLInputElement) || !solarEnergy) return;
        const watts = Number(solarWatts.value);
        const hours = Number(solarHours.value);
        if (!Number.isFinite(watts) || watts < 0 || !Number.isFinite(hours) || hours < 0) {
            solarEnergy.textContent = '—';
            return;
        }
        solarEnergy.textContent = `${(watts * hours).toFixed(1)} Wh`;
    };
    solarWatts?.addEventListener('input', computeSolarEnergy);
    solarHours?.addEventListener('input', computeSolarEnergy);
    computeSolarEnergy();


    const computeBatteryCurrent = () => {
        if (!(batteryVoltage instanceof HTMLInputElement) || !(batteryLoad instanceof HTMLInputElement) || !batteryCurrent) return;
        const voltage = Number(batteryVoltage.value);
        const load = Number(batteryLoad.value);
        if (!Number.isFinite(voltage) || voltage <= 0 || !Number.isFinite(load) || load < 0) {
            batteryCurrent.textContent = '—';
            return;
        }
        batteryCurrent.textContent = `${(load / voltage).toFixed(2)} A`;
    };
    batteryVoltage?.addEventListener('input', computeBatteryCurrent);
    batteryLoad?.addEventListener('input', computeBatteryCurrent);
    computeBatteryCurrent();

    const computeMuf = () => {
        if (!(mufFof2 instanceof HTMLInputElement) || !(mufAngle instanceof HTMLInputElement) || !mufResult) return;
        const fof2 = Number(mufFof2.value);
        const angle = Number(mufAngle.value);
        if (!Number.isFinite(fof2) || fof2 <= 0 || !Number.isFinite(angle) || angle <= 0 || angle >= 90) {
            mufResult.textContent = '—';
            return;
        }
        const radians = angle * Math.PI / 180;
        const muf = fof2 / Math.cos(radians);
        mufResult.textContent = `${muf.toFixed(2)} MHz`;
    };
    mufFof2?.addEventListener('input', computeMuf);
    mufAngle?.addEventListener('input', computeMuf);
    computeMuf();


    const computeEirp = () => {
        if (!(eirpErp instanceof HTMLInputElement) || !eirpResult) return;
        const erp = Number(eirpErp.value);
        if (!Number.isFinite(erp) || erp < 0) {
            eirpResult.textContent = '—';
            return;
        }
        const eirp = erp * 1.64;
        eirpResult.textContent = `${eirp.toFixed(2)} W`;
    };
    eirpErp?.addEventListener('input', computeEirp);
    computeEirp();


    const computeSkipDistance = () => {
        if (!(skipHeight instanceof HTMLInputElement) || !(skipAngle instanceof HTMLInputElement) || !skipResult) return;
        const h = Number(skipHeight.value);
        const angle = Number(skipAngle.value);
        if (!Number.isFinite(h) || h <= 0 || !Number.isFinite(angle) || angle <= 0 || angle >= 90) {
            skipResult.textContent = '—';
            return;
        }
        const radians = angle * Math.PI / 180;
        const distance = 2 * h * Math.tan(radians);
        skipResult.textContent = `${distance.toFixed(1)} ${i18n.km_unit}`;
    };
    skipHeight?.addEventListener('input', computeSkipDistance);
    skipAngle?.addEventListener('input', computeSkipDistance);
    computeSkipDistance();


    const computeDbSum = () => {
        if (!(dbsumA instanceof HTMLInputElement) || !(dbsumB instanceof HTMLInputElement) || !dbsumResult) return;
        const a = Number(dbsumA.value);
        const b = Number(dbsumB.value);
        if (!Number.isFinite(a) || !Number.isFinite(b)) {
            dbsumResult.textContent = '—';
            return;
        }
        const mw = (10 ** (a / 10)) + (10 ** (b / 10));
        const dbm = 10 * Math.log10(mw);
        dbsumResult.textContent = `${dbm.toFixed(2)} dBm`;
    };
    dbsumA?.addEventListener('input', computeDbSum);
    dbsumB?.addEventListener('input', computeDbSum);
    computeDbSum();


    const computeDbuv = () => {
        if (!(dbuvDbm instanceof HTMLInputElement) || !dbuvResult) return;
        const dbm = Number(dbuvDbm.value);
        if (!Number.isFinite(dbm)) {
            dbuvResult.textContent = '—';
            return;
        }
        const dbuv = dbm + 107;
        dbuvResult.textContent = `${dbuv.toFixed(2)} ${i18n.dbuv_label || 'dBµV'}`;
    };
    dbuvDbm?.addEventListener('input', computeDbuv);
    computeDbuv();


    const computeGainConversion = () => {
        if (!(gainDbd instanceof HTMLInputElement) || !gainDbi) return;
        const dbd = Number(gainDbd.value);
        if (!Number.isFinite(dbd)) {
            gainDbi.textContent = '—';
            return;
        }
        gainDbi.textContent = `${(dbd + 2.15).toFixed(2)} dBi`;
    };
    gainDbd?.addEventListener('input', computeGainConversion);
    computeGainConversion();

    const locatorToLatLon = (locator) => {
        const normalized = locator.toUpperCase().trim();
        if (!/^[A-R]{2}[0-9]{2}([A-X]{2})?$/.test(normalized)) {
            return null;
        }
        let lon = -180 + (normalized.charCodeAt(0) - 65) * 20 + Number(normalized[2]) * 2 + 1;
        let lat = -90 + (normalized.charCodeAt(1) - 65) * 10 + Number(normalized[3]) + 0.5;
        if (normalized.length === 6) {
            lon += (normalized.charCodeAt(4) - 65) * (5 / 60) + (2.5 / 60);
            lat += (normalized.charCodeAt(5) - 65) * (2.5 / 60) + (1.25 / 60);
        }
        return { lat, lon };
    };
    const haversineKm = (a, b) => {
        const r = 6371;
        const toRad = (v) => v * Math.PI / 180;
        const dLat = toRad(b.lat - a.lat);
        const dLon = toRad(b.lon - a.lon);
        const x = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(a.lat)) * Math.cos(toRad(b.lat)) * Math.sin(dLon / 2) ** 2;
        return 2 * r * Math.asin(Math.sqrt(x));
    };
    const syncDistance = () => {
        if (!(locatorA instanceof HTMLInputElement) || !(locatorB instanceof HTMLInputElement) || !locatorDistance) return;
        const p1 = locatorToLatLon(locatorA.value);
        const p2 = locatorToLatLon(locatorB.value);
        if (!p1 || !p2) {
            locatorDistance.textContent = '—';
            return;
        }
        locatorDistance.textContent = `${haversineKm(p1, p2).toFixed(1)} ${i18n.km_unit}`;
    };
    locatorA?.addEventListener('input', syncDistance);
    locatorB?.addEventListener('input', syncDistance);
    const toolLinks = document.querySelectorAll('[data-tool-target]');
    const toolPanels = document.querySelectorAll('[data-tool-panel]');
    const setActiveTool = (id) => {
        if (!id) return;
        toolPanels.forEach((panel) => {
            panel.classList.toggle('is-hidden', panel.id !== id);
        });
        toolLinks.forEach((link) => {
            const isActive = link.getAttribute('data-tool-target') === id;
            link.classList.toggle('is-active', isActive);
        });
    };
    const initialTool = window.location.hash ? window.location.hash.slice(1) : 'tool-grid';
    setActiveTool(initialTool);
    toolLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const targetId = link.getAttribute('data-tool-target') || '';
            if (targetId === '') return;
            event.preventDefault();
            window.history.replaceState(null, '', `#${targetId}`);
            setActiveTool(targetId);
        });
    });

})();
</script>
<?php
echo render_layout((string) ob_get_clean(), (string) ($t['title'] ?? 'Outils'));
