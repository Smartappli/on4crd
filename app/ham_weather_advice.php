<?php
declare(strict_types=1);

require_once __DIR__ . '/widget_radio_helpers.php';

if (!function_exists('ham_agromet_hourly_url')) {
function ham_agromet_hourly_url(?DateTimeImmutable $now = null): string
{
    $baseUrl = rtrim((string) env('AGROMET_API_BASE_URL', 'https://agromet.be/fr/agromet/api/v3/get_pameseb_hourly'), '/');
    $sensors = preg_replace('/[^a-z0-9_,]/i', '', (string) env('AGROMET_SENSORS', 'tsa,plu,hra,vvt'));
    $stations = preg_replace('/[^a-z0-9_,]/i', '', (string) env('AGROMET_STATION_SIDS', '1,26'));
    $sensors = $sensors !== '' ? $sensors : 'tsa,plu,hra,vvt';
    $stations = $stations !== '' ? $stations : '1,26';
    $now = $now ?? new DateTimeImmutable('now', new DateTimeZone('Europe/Brussels'));
    $to = $now->format('Y-m-d');
    $from = $now->sub(new DateInterval('P1D'))->format('Y-m-d');

    return $baseUrl . '/' . $sensors . '/' . $stations . '/' . $from . '/' . $to . '/';
}
}

if (!function_exists('ham_agromet_api_json')) {
function ham_agromet_api_json(string $url, string $token): ?array
{
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        sprintf('Authorization: Token %s', $token),
        'User-Agent: ON4CRD-Agromet/1.0',
    ];

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        if ($curl === false) {
            return null;
        }
        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if (!is_string($raw) || trim($raw) === '' || $status >= 400) {
            return null;
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => implode("\r\n", $headers) . "\r\n",
            ],
        ]);
        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : null;
}
}

if (!function_exists('ham_agromet_row_timestamp')) {
function ham_agromet_row_timestamp(array $row): string
{
    foreach (['timestamp', 'time', 'datetime', 'date_time', 'date', 'dt', 'moment'] as $key) {
        $value = $row[$key] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }
    }

    foreach ($row as $value) {
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            return trim($value);
        }
    }

    return '';
}
}

if (!function_exists('ham_agromet_row_station')) {
function ham_agromet_row_station(array $row): string
{
    foreach (['station_sid', 'station_id', 'sid', 'station', 'point'] as $key) {
        $value = $row[$key] ?? null;
        if (is_scalar($value) && trim((string) $value) !== '') {
            return trim((string) $value);
        }
        if (is_array($value)) {
            foreach (['sid', 'id', 'code', 'name'] as $nestedKey) {
                $nestedValue = $value[$nestedKey] ?? null;
                if (is_scalar($nestedValue) && trim((string) $nestedValue) !== '') {
                    return trim((string) $nestedValue);
                }
            }
        }
    }

    return 'default';
}
}

if (!function_exists('ham_agromet_numeric_value')) {
function ham_agromet_numeric_value(array $row, string $sensor): ?float
{
    $sensor = strtolower($sensor);
    foreach ([$sensor, strtoupper($sensor), $sensor . '_value', 'value_' . $sensor] as $key) {
        $value = $row[$key] ?? null;
        if (is_numeric($value)) {
            return (float) $value;
        }
    }

    foreach (['data', 'values', 'measurements'] as $containerKey) {
        $container = $row[$containerKey] ?? null;
        if (is_array($container)) {
            if (array_is_list($container)) {
                foreach ($container as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $value = ham_agromet_numeric_value($item, $sensor);
                    if ($value !== null) {
                        return $value;
                    }
                }
            } else {
                $value = ham_agromet_numeric_value($container, $sensor);
                if ($value !== null) {
                    return $value;
                }
            }
        }
    }

    $rowSensorRaw = $row['sensor'] ?? $row['code'] ?? $row['parameter'] ?? $row['variable'] ?? '';
    if (is_array($rowSensorRaw)) {
        $rowSensorRaw = $rowSensorRaw['code'] ?? $rowSensorRaw['name'] ?? $rowSensorRaw['id'] ?? '';
    }
    $rowSensor = is_scalar($rowSensorRaw) ? strtolower(trim((string) $rowSensorRaw)) : '';
    if ($rowSensor === $sensor) {
        foreach (['value', 'valeur', 'measurement', 'mesure'] as $valueKey) {
            $value = $row[$valueKey] ?? null;
            if (is_numeric($value)) {
                return (float) $value;
            }
        }
    }

    return null;
}
}

if (!function_exists('ham_agromet_current_weather')) {
function ham_agromet_current_weather(array $payload): ?array
{
    $rows = $payload['results'] ?? $payload;
    if (!is_array($rows)) {
        return null;
    }

    $buckets = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $timestamp = ham_agromet_row_timestamp($row);
        $bucketKey = $timestamp . '|' . ham_agromet_row_station($row);
        $buckets[$bucketKey] ??= ['timestamp' => $timestamp, 'values' => []];
        foreach (['tsa', 'hra', 'plu', 'vvt'] as $sensor) {
            $value = ham_agromet_numeric_value($row, $sensor);
            if ($value !== null) {
                $buckets[$bucketKey]['values'][$sensor] = $value;
            }
        }
    }

    $bestBucket = null;
    foreach ($buckets as $bucket) {
        if (($bucket['values'] ?? []) === []) {
            continue;
        }
        if ($bestBucket === null) {
            $bestBucket = $bucket;
            continue;
        }
        $bucketTime = strtotime((string) ($bucket['timestamp'] ?? '')) ?: 0;
        $bestTime = strtotime((string) ($bestBucket['timestamp'] ?? '')) ?: 0;
        $bucketCount = count((array) ($bucket['values'] ?? []));
        $bestCount = count((array) ($bestBucket['values'] ?? []));
        if ($bucketTime > $bestTime || ($bucketTime === $bestTime && $bucketCount > $bestCount)) {
            $bestBucket = $bucket;
        }
    }

    if (!is_array($bestBucket)) {
        return null;
    }

    $values = (array) ($bestBucket['values'] ?? []);
    $current = [];
    if (isset($values['tsa'])) {
        $current['temperature_2m'] = (float) $values['tsa'];
    }
    if (isset($values['hra'])) {
        $current['relative_humidity_2m'] = (int) round((float) $values['hra']);
    }
    if (isset($values['plu'])) {
        $current['precipitation'] = (float) $values['plu'];
    }
    if (isset($values['vvt'])) {
        $current['wind_speed_10m'] = (float) $values['vvt'] * 3.6;
    }
    if ((string) ($bestBucket['timestamp'] ?? '') !== '') {
        $current['time'] = (string) $bestBucket['timestamp'];
    }

    return $current !== [] ? $current : null;
}
}

if (!function_exists('render_ham_weather_advice')) {
function render_ham_weather_advice(array $user = []): string
{
    $locale = strtolower((string) ($_SESSION['locale'] ?? 'fr'));
    $messages = [
        'fr' => [
            'score_excellent' => 'Excellentes conditions',
            'score_good' => 'Bonnes conditions',
            'score_variable' => 'Conditions variables',
            'score_difficult' => 'Conditions difficiles',
            'window_day' => '08h–15h',
            'window_evening' => '16h–21h',
            'window_night' => 'soirée / nuit',
            'radio_info' => 'Informations radioamateur',
            'for_qso' => 'pour les QSO',
            'bands' => 'Bandes conseillées :',
            'modes' => 'Modes conseillés :',
            'window' => 'Créneau recommandé :',
            'input_info' => 'Informations utilisées pour le calcul',
            'location' => 'Localisation :',
            'local_hour' => 'Heure locale :',
            'updated_at' => 'Dernière mise à jour :',
            'local_weather' => 'Météo locale :',
            'geomagnetic' => 'Indice géomagnétique :',
            'kp_unavailable' => 'indisponible',
        ],
        'en' => [
            'score_excellent' => 'Excellent conditions',
            'score_good' => 'Good conditions',
            'score_variable' => 'Variable conditions',
            'score_difficult' => 'Difficult conditions',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'evening / night',
            'radio_info' => 'Ham radio information',
            'for_qso' => 'for QSOs',
            'bands' => 'Recommended bands:',
            'modes' => 'Recommended modes:',
            'window' => 'Recommended time window:',
            'input_info' => 'Data used for calculation',
            'location' => 'Location:',
            'local_hour' => 'Local time:',
            'updated_at' => 'Last update:',
            'local_weather' => 'Local weather:',
            'geomagnetic' => 'Geomagnetic index:',
            'kp_unavailable' => 'unavailable',
        ],
        'de' => [
            'score_excellent' => 'Ausgezeichnete Bedingungen',
            'score_good' => 'Gute Bedingungen',
            'score_variable' => 'Wechselhafte Bedingungen',
            'score_difficult' => 'Schwierige Bedingungen',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'Abend / Nacht',
            'radio_info' => 'Funkinformationen',
            'for_qso' => 'für QSOs',
            'bands' => 'Empfohlene Bänder:',
            'modes' => 'Empfohlene Betriebsarten:',
            'window' => 'Empfohlenes Zeitfenster:',
            'input_info' => 'Für die Berechnung verwendete Daten',
            'location' => 'Standort:',
            'local_hour' => 'Ortszeit:',
            'updated_at' => 'Letzte Aktualisierung:',
            'local_weather' => 'Lokales Wetter:',
            'geomagnetic' => 'Geomagnetischer Index:',
            'kp_unavailable' => 'nicht verfügbar',
        ],
        'nl' => [
            'score_excellent' => 'Uitstekende condities',
            'score_good' => 'Goede condities',
            'score_variable' => 'Wisselende condities',
            'score_difficult' => 'Moeilijke condities',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'avond / nacht',
            'radio_info' => 'Radioamateurinformatie',
            'for_qso' => 'voor QSO’s',
            'bands' => 'Aanbevolen banden:',
            'modes' => 'Aanbevolen modes:',
            'window' => 'Aanbevolen tijdsvenster:',
            'input_info' => 'Gegevens gebruikt voor de berekening',
            'location' => 'Locatie:',
            'local_hour' => 'Lokale tijd:',
            'updated_at' => 'Laatste update:',
            'local_weather' => 'Lokaal weer:',
            'geomagnetic' => 'Geomagnetische index:',
            'kp_unavailable' => 'niet beschikbaar',
        ],
        'es' => [
            'score_excellent' => 'Condiciones excelentes',
            'score_good' => 'Buenas condiciones',
            'score_variable' => 'Condiciones variables',
            'score_difficult' => 'Condiciones difíciles',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'tarde / noche',
            'radio_info' => 'Información de radioafición',
            'for_qso' => 'para QSOs',
            'bands' => 'Bandas recomendadas:',
            'modes' => 'Modos recomendados:',
            'window' => 'Franja horaria recomendada:',
            'input_info' => 'Datos usados para el cálculo',
            'location' => 'Ubicación:',
            'local_hour' => 'Hora local:',
            'updated_at' => 'Última actualización:',
            'local_weather' => 'Tiempo local:',
            'geomagnetic' => 'Índice geomagnético:',
            'kp_unavailable' => 'no disponible',
        ],
        'it' => [
            'score_excellent' => 'Condizioni eccellenti',
            'score_good' => 'Buone condizioni',
            'score_variable' => 'Condizioni variabili',
            'score_difficult' => 'Condizioni difficili',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'sera / notte',
            'radio_info' => 'Informazioni radioamatoriali',
            'for_qso' => 'per i QSO',
            'bands' => 'Bande consigliate:',
            'modes' => 'Modi consigliati:',
            'window' => 'Fascia oraria consigliata:',
            'input_info' => 'Dati usati per il calcolo',
            'location' => 'Posizione:',
            'local_hour' => 'Ora locale:',
            'updated_at' => 'Ultimo aggiornamento:',
            'local_weather' => 'Météo locale:',
            'geomagnetic' => 'Indice geomagnetico:',
            'kp_unavailable' => 'non disponibile',
        ],
        'pt' => [
            'score_excellent' => 'Condições excelentes',
            'score_good' => 'Boas condições',
            'score_variable' => 'Condições variáveis',
            'score_difficult' => 'Condições difíceis',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'fim de tarde / noite',
            'radio_info' => 'Informações de radioamador',
            'for_qso' => 'para QSOs',
            'bands' => 'Bandas recomendadas:',
            'modes' => 'Modos recomendados:',
            'window' => 'Janela horária recomendada:',
            'input_info' => 'Dados usados no cálculo',
            'location' => 'Localização:',
            'local_hour' => 'Hora local:',
            'updated_at' => 'Última atualização:',
            'local_weather' => 'Tempo local:',
            'geomagnetic' => 'Índice geomagnético:',
            'kp_unavailable' => 'indisponível',
        ],
        'ar' => [
            'score_excellent' => 'ظروف ممتازة',
            'score_good' => 'ظروف جيدة',
            'score_variable' => 'ظروف متغيرة',
            'score_difficult' => 'ظروف صعبة',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'المساء / الليل',
            'radio_info' => 'معلومات هواة الراديو',
            'for_qso' => 'لاتصالات QSO',
            'bands' => 'النطاقات الموصى بها:',
            'modes' => 'الأنماط الموصى بها:',
            'window' => 'الفترة الزمنية الموصى بها:',
            'input_info' => 'البيانات المستخدمة للحساب',
            'location' => 'الموقع:',
            'local_hour' => 'الوقت المحلي:',
            'updated_at' => 'آخر تحديث:',
            'local_weather' => 'الطقس المحلي:',
            'geomagnetic' => 'المؤشر الجيومغناطيسي:',
            'kp_unavailable' => 'غير متوفر',
        ],
        'hi' => [
            'score_excellent' => 'उत्कृष्ट परिस्थितियाँ',
            'score_good' => 'अच्छी परिस्थितियाँ',
            'score_variable' => 'परिवर्ती परिस्थितियाँ',
            'score_difficult' => 'कठिन परिस्थितियाँ',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'शाम / रात',
            'radio_info' => 'हैम रेडियो जानकारी',
            'for_qso' => 'QSO के लिए',
            'bands' => 'अनुशंसित बैंड:',
            'modes' => 'अनुशंसित मोड:',
            'window' => 'अनुशंसित समय खिड़की:',
            'input_info' => 'गणना के लिए उपयोग किया गया डेटा',
            'location' => 'स्थान:',
            'local_hour' => 'स्थानीय समय:',
            'updated_at' => 'अंतिम अपडेट:',
            'local_weather' => 'स्थानीय मौसम:',
            'geomagnetic' => 'भू-चुंबकीय सूचकांक:',
            'kp_unavailable' => 'उपलब्ध नहीं',
        ],
        'ja' => [
            'score_excellent' => '非常に良好なコンディション',
            'score_good' => '良好なコンディション',
            'score_variable' => '変わりやすいコンディション',
            'score_difficult' => '難しいコンディション',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => '夕方 / 夜間',
            'radio_info' => 'アマチュア無線情報',
            'for_qso' => 'QSO向け',
            'bands' => '推奨バンド:',
            'modes' => '推奨モード:',
            'window' => '推奨時間帯:',
            'input_info' => '計算に使用したデータ',
            'location' => '場所:',
            'local_hour' => '現地時刻:',
            'updated_at' => '最終更新:',
            'local_weather' => '現地の天気:',
            'geomagnetic' => '地磁気指数:',
            'kp_unavailable' => '利用不可',
        ],
        'zh' => [
            'score_excellent' => '条件极佳',
            'score_good' => '条件良好',
            'score_variable' => '条件多变',
            'score_difficult' => '条件较差',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => '傍晚 / 夜间',
            'radio_info' => '业余无线电信息',
            'for_qso' => '适用于 QSO',
            'bands' => '推荐频段：',
            'modes' => '推荐模式：',
            'window' => '推荐时段：',
            'input_info' => '用于计算的数据',
            'location' => '位置：',
            'local_hour' => '当地时间：',
            'updated_at' => '最后更新：',
            'local_weather' => '当地天气：',
            'geomagnetic' => '地磁指数：',
            'kp_unavailable' => '不可用',
        ],
        'bn' => [
            'score_excellent' => 'চমৎকার অবস্থা',
            'score_good' => 'ভাল অবস্থা',
            'score_variable' => 'পরিবর্তনশীল অবস্থা',
            'score_difficult' => 'কঠিন অবস্থা',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'সন্ধ্যা / রাত',
            'radio_info' => 'হ্যাম রেডিও তথ্য',
            'for_qso' => 'QSO-এর জন্য',
            'bands' => 'প্রস্তাবিত ব্যান্ড:',
            'modes' => 'প্রস্তাবিত মোড:',
            'window' => 'প্রস্তাবিত সময়সীমা:',
            'input_info' => 'গণনার জন্য ব্যবহৃত তথ্য',
            'location' => 'অবস্থান:',
            'local_hour' => 'স্থানীয় সময়:',
            'updated_at' => 'সর্বশেষ আপডেট:',
            'local_weather' => 'স্থানীয় আবহাওয়া:',
            'geomagnetic' => 'ভূচৌম্বক সূচক:',
            'kp_unavailable' => 'উপলব্ধ নয়',
        ],
        'ru' => [
            'score_excellent' => 'Отличные условия',
            'score_good' => 'Хорошие условия',
            'score_variable' => 'Переменные условия',
            'score_difficult' => 'Сложные условия',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'вечер / ночь',
            'radio_info' => 'Информация для радиолюбителей',
            'for_qso' => 'для QSO',
            'bands' => 'Рекомендуемые диапазоны:',
            'modes' => 'Рекомендуемые режимы:',
            'window' => 'Рекомендуемое время:',
            'input_info' => 'Данные, использованные для расчёта',
            'location' => 'Местоположение:',
            'local_hour' => 'Местное время:',
            'updated_at' => 'Последнее обновление:',
            'local_weather' => 'Местная погода:',
            'geomagnetic' => 'Геомагнитный индекс:',
            'kp_unavailable' => 'недоступно',
        ],
        'id' => [
            'score_excellent' => 'Kondisi sangat baik',
            'score_good' => 'Kondisi baik',
            'score_variable' => 'Kondisi berubah-ubah',
            'score_difficult' => 'Kondisi sulit',
            'window_day' => '08:00–15:00',
            'window_evening' => '16:00–21:00',
            'window_night' => 'sore / malam',
            'radio_info' => 'Informasi radio amatir',
            'for_qso' => 'untuk QSO',
            'bands' => 'Band yang direkomendasikan:',
            'modes' => 'Mode yang direkomendasikan:',
            'window' => 'Rentang waktu yang direkomendasikan:',
            'input_info' => 'Data yang digunakan untuk perhitungan',
            'location' => 'Lokasi:',
            'local_hour' => 'Waktu lokal:',
            'updated_at' => 'Pembaruan terakhir:',
            'local_weather' => 'Cuaca lokal:',
            'geomagnetic' => 'Indeks geomagnetik:',
            'kp_unavailable' => 'tidak tersedia',
        ],
    ];
    $i18n = $messages[$locale] ?? $messages['fr'];
    $propagationLabel = match ($locale) {
        'en' => 'HF propagation:',
        'de' => 'HF-Ausbreitung:',
        'nl' => 'HF-propagatie:',
        'es' => 'Propagacion HF:',
        'it' => 'Propagazione HF:',
        'pt' => 'Propagacao HF:',
        default => 'Propagation HF :',
    };
    $propagationStates = match ($locale) {
        'en' => ['quiet' => 'quiet', 'usable' => 'usable', 'disturbed' => 'disturbed', 'stormy' => 'stormy', 'unavailable' => 'unavailable'],
        'de' => ['quiet' => 'ruhig', 'usable' => 'nutzbar', 'disturbed' => 'gestoert', 'stormy' => 'stuermisch', 'unavailable' => 'nicht verfuegbar'],
        'nl' => ['quiet' => 'rustig', 'usable' => 'bruikbaar', 'disturbed' => 'verstoord', 'stormy' => 'stormachtig', 'unavailable' => 'niet beschikbaar'],
        'es' => ['quiet' => 'tranquila', 'usable' => 'utilizable', 'disturbed' => 'perturbada', 'stormy' => 'tormentosa', 'unavailable' => 'no disponible'],
        'it' => ['quiet' => 'calma', 'usable' => 'utilizzabile', 'disturbed' => 'disturbata', 'stormy' => 'tempestosa', 'unavailable' => 'non disponibile'],
        'pt' => ['quiet' => 'calma', 'usable' => 'utilizavel', 'disturbed' => 'perturbada', 'stormy' => 'tempestuosa', 'unavailable' => 'indisponivel'],
        default => ['quiet' => 'calme', 'usable' => 'exploitable', 'disturbed' => 'perturbee', 'stormy' => 'orage magnetique', 'unavailable' => 'indisponible'],
    };
    $defaultLocator = 'JO20LI';
    $memberLocator = strtoupper(trim((string) ($user['locator'] ?? '')));
    if ($memberLocator === '' && isset($user['id']) && is_numeric($user['id']) && table_exists('members')) {
        try {
            $stmt = db()->prepare('SELECT locator, qth FROM members WHERE id = ? LIMIT 1');
            $stmt->execute([(int) $user['id']]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                $candidateLocator = strtoupper(trim((string) ($row['locator'] ?? '')));
                if ($candidateLocator === '') {
                    $candidateLocator = strtoupper(trim((string) ($row['qth'] ?? '')));
                }
                if ($candidateLocator !== '' && preg_match('/^[A-R]{2}[0-9]{2}(?:[A-X]{2})?$/', $candidateLocator) === 1) {
                    $memberLocator = $candidateLocator;
                }
            }
        } catch (Throwable) {
            // Keep fallback behavior when member profile location cannot be read.
        }
    }
    $locator = $memberLocator !== '' ? $memberLocator : $defaultLocator;
    $coordinates = maidenhead_to_coordinates($locator) ?? ['latitude' => 50.3150, 'longitude' => 4.9452];

    $currentWeather = null;
    $agrometToken = trim((string) env('AGROMET_API_TOKEN', ''));
    if ($agrometToken !== '') {
        $agrometUrl = ham_agromet_hourly_url();
        $agrometPayload = cache_remember('ham:advice:weather:agromet:' . hash('sha256', $agrometUrl), 300, static function () use ($agrometUrl, $agrometToken): ?array {
            return ham_agromet_api_json($agrometUrl, $agrometToken);
        });
        $currentWeather = is_array($agrometPayload) ? ham_agromet_current_weather($agrometPayload) : null;
    }

    if (!is_array($currentWeather)) {
        $weatherUrl = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
            'latitude' => number_format((float) $coordinates['latitude'], 4, '.', ''),
            'longitude' => number_format((float) $coordinates['longitude'], 4, '.', ''),
            'current' => 'temperature_2m,relative_humidity_2m,wind_speed_10m,weather_code,cloud_cover,precipitation',
            'timezone' => 'auto',
        ]);
        $weatherPayload = cache_remember('ham:advice:weather:' . hash('sha256', $weatherUrl), 300, static function () use ($weatherUrl): ?array {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => 6,
                    'header' => "Accept: application/json\r\nUser-Agent: ON4CRD-Propagation/1.0\r\n",
                ],
            ]);
            $raw = @file_get_contents($weatherUrl, false, $context);
            if (!is_string($raw) || trim($raw) === '') {
                return null;
            }
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : null;
        });
        $currentWeather = is_array($weatherPayload) && is_array($weatherPayload['current'] ?? null) ? $weatherPayload['current'] : [];
    }

    $kpPayload = cache_remember('ham:advice:kp', 300, static function (): ?array {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 6,
                'header' => "Accept: application/json\r\nUser-Agent: ON4CRD-Propagation/1.0\r\n",
            ],
        ]);
        $raw = @file_get_contents('https://services.swpc.noaa.gov/products/noaa-planetary-k-index.json', false, $context);
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    });

    $temperature = is_numeric($currentWeather['temperature_2m'] ?? null) ? (float) $currentWeather['temperature_2m'] : 15.0;
    $wind = is_numeric($currentWeather['wind_speed_10m'] ?? null) ? (float) $currentWeather['wind_speed_10m'] : 10.0;
    $weatherCode = (int) ($currentWeather['weather_code'] ?? -1);
    $localTime = trim((string) ($currentWeather['time'] ?? ''));
    $hour = (int) gmdate('G');
    if ($localTime !== '') {
        try {
            $dtLocal = new DateTimeImmutable($localTime);
            $hour = (int) $dtLocal->format('G');
        } catch (Throwable $throwable) {
            $hour = (int) gmdate('G');
        }
    }

    $humidity = is_numeric($currentWeather['relative_humidity_2m'] ?? null) ? (int) $currentWeather['relative_humidity_2m'] : 60;
    $cloudCover = is_numeric($currentWeather['cloud_cover'] ?? null) ? (int) $currentWeather['cloud_cover'] : 45;
    $precipitation = is_numeric($currentWeather['precipitation'] ?? null) ? (float) $currentWeather['precipitation'] : 0.0;
    $measurement = is_array($kpPayload) ? extract_latest_kp_measurement($kpPayload) : null;
    $kp = is_array($measurement) ? (float) ($measurement['kp'] ?? 3.0) : null;
    $kpTrend = is_array($kpPayload) ? extract_kp_trend($kpPayload) : null;
    $kpTrendForScoring = is_numeric($kpTrend) ? (float) $kpTrend : 0.0;
    $kpForScoring = is_numeric($kp) ? (float) $kp : 3.0;
    $propagationStateKey = match (true) {
        !is_numeric($kp) => 'unavailable',
        $kpForScoring <= 2.0 && $kpTrendForScoring < 0.8 => 'quiet',
        $kpForScoring <= 4.0 => 'usable',
        $kpForScoring <= 5.0 => 'disturbed',
        default => 'stormy',
    };
    $propagationSummary = (string) ($propagationStates[$propagationStateKey] ?? $propagationStates['unavailable']);
    if (is_numeric($kp)) {
        $propagationSummary .= ' (Kp ' . number_format((float) $kp, 1, ',', '') . ')';
    }

    $month = (int) gmdate('n');
    if ($localTime !== '') {
        try {
            $month = (int) (new DateTimeImmutable($localTime))->format('n');
        } catch (Throwable $throwable) {
            $month = (int) gmdate('n');
        }
    }
    $isSummer = $month >= 4 && $month <= 9;
    $isDaytime = $hour >= 7 && $hour <= 16;
    $isLateEvening = $hour >= 20 || $hour <= 5;

    $hfScore = 65.0;
    $hfScore += $kpForScoring <= 1.5 ? 20.0 : ($kpForScoring <= 3.0 ? 10.0 : ($kpForScoring <= 4.5 ? 1.0 : -20.0));
    $hfScore += $kpTrendForScoring <= -0.8 ? 6.0 : ($kpTrendForScoring >= 0.8 ? -8.0 : 0.0);
    $hfScore += $isDaytime ? 10.0 : -4.0;
    $hfScore += ($wind <= 18.0 ? 8.0 : ($wind <= 30.0 ? 2.0 : -10.0));
    $hfScore += ($humidity >= 35 && $humidity <= 85) ? 3.0 : -5.0;
    $hfScore += ($cloudCover <= 45 ? 2.0 : ($cloudCover >= 90 ? -4.0 : 0.0));
    $hfScore += ($precipitation <= 0.1 ? 2.0 : ($precipitation >= 2.5 ? -8.0 : -3.0));
    $hfScore += in_array($weatherCode, [95, 96, 99], true) ? -16.0 : 0.0;
    $hfScore += $isSummer && $isDaytime ? 4.0 : 0.0;
    $hfScore += !$isSummer && $isLateEvening ? 4.0 : 0.0;

    $bands = ['40m', '20m', '15m'];
    if ($hour >= 8 && $hour <= 15 && $kpForScoring <= 3.5 && $isSummer) {
        $bands = ['20m', '17m', '15m'];
    } elseif ($hour >= 10 && $hour <= 17 && $kpForScoring <= 2.5 && $isSummer) {
        $bands = ['15m', '12m', '10m'];
    } elseif ($hour >= 18 || $hour <= 6) {
        $bands = ['40m', '80m', '30m'];
        if (!$isSummer && $kpForScoring <= 4.0) {
            $bands = ['80m', '40m', '30m'];
        }
    } elseif ($kpForScoring >= 5.0) {
        $bands = ['40m', '30m', '20m'];
    }

    $modes = ['SSB', 'CW'];
    if ($kpForScoring >= 4.5 || $wind >= 35.0 || $precipitation >= 2.0 || in_array($weatherCode, [95, 96, 99], true)) {
        $modes = ['FT8', 'CW', 'RTTY'];
    } elseif ($temperature < 5.0 || $humidity > 90) {
        $modes = ['FT8', 'SSB', 'CW'];
    }

    $scoreLabel = $hfScore >= 80 ? (string) $i18n['score_excellent'] : ($hfScore >= 60 ? (string) $i18n['score_good'] : ($hfScore >= 45 ? (string) $i18n['score_variable'] : (string) $i18n['score_difficult']));
    $timeWindow = $hour >= 8 && $hour <= 15 ? (string) $i18n['window_day'] : ($hour >= 16 && $hour <= 21 ? (string) $i18n['window_evening'] : (string) $i18n['window_night']);

    return '<div class="grid gap-4">'
        . '<section>'
        . '<ul class="mt-2 list-clean">'
        . '<li><strong>' . e($scoreLabel) . '</strong> ' . e((string) $i18n['for_qso']) . ' (score ' . e((string) max(0, min(100, (int) round($hfScore)))) . '/100)</li>'
        . '<li><strong>' . e((string) $i18n['bands']) . '</strong> ' . e(implode(' • ', $bands)) . '</li>'
        . '<li><strong>' . e((string) $i18n['modes']) . '</strong> ' . e(implode(' • ', $modes)) . '</li>'
        . '<li><strong>' . e((string) $i18n['window']) . '</strong> ' . e($timeWindow) . '</li>'
        . '<li><strong>' . e($propagationLabel) . '</strong> ' . e($propagationSummary) . '</li>'
        . '</ul>'
        . '</section>'
        . '</div>';
}
}
