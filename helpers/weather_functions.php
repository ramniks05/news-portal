<?php
/**
 * Weather helper — Open-Meteo (no API key).
 */
function get_weather_data()
{
    $city = get_config('weather_city', 'Mumbai');
    $lat = get_config('weather_lat', '19.0760');
    $lon = get_config('weather_lon', '72.8777');

    $cache_file = __DIR__ . '/../uploads/static/weather_cache.json';
    $cache_ttl = 1800; // 30 min

    if (is_file($cache_file) && (time() - filemtime($cache_file)) < $cache_ttl) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if (is_array($cached)) {
            if (empty($cached['tone'])) {
                $meta = weather_code_meta((int)($cached['code'] ?? 0));
                $cached['tone'] = $meta['tone'];
                $cached['label'] = $cached['label'] ?? $meta['label'];
                $cached['icon'] = $cached['icon'] ?? $meta['icon'];
            }
            return $cached;
        }
    }

    $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
        'latitude' => $lat,
        'longitude' => $lon,
        'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
        'timezone' => 'Asia/Kolkata',
    ]);

    $json = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $json = curl_exec($ch);
        curl_close($ch);
    } else {
        $json = @file_get_contents($url);
    }

    $data = $json ? json_decode($json, true) : null;
    if (!$data || empty($data['current'])) {
        return [
            'city' => $city,
            'temp' => null,
            'humidity' => null,
            'wind' => null,
            'code' => 0,
            'label' => 'Unavailable',
            'icon' => 'fa-cloud',
            'tone' => 'cloud',
        ];
    }

    $code = (int)($data['current']['weather_code'] ?? 0);
    $map = weather_code_meta($code);
    $out = [
        'city' => $city,
        'temp' => round((float)$data['current']['temperature_2m']),
        'humidity' => (int)($data['current']['relative_humidity_2m'] ?? 0),
        'wind' => round((float)($data['current']['wind_speed_10m'] ?? 0)),
        'code' => $code,
        'label' => $map['label'],
        'icon' => $map['icon'],
        'tone' => $map['tone'],
    ];

    @file_put_contents($cache_file, json_encode($out));
    return $out;
}

function weather_code_meta($code)
{
    $code = (int)$code;
    if ($code === 0) return ['label' => 'Sunny', 'icon' => 'fa-sun', 'tone' => 'sun'];
    if (in_array($code, [1, 2], true)) return ['label' => 'Partly cloudy', 'icon' => 'fa-cloud-sun', 'tone' => 'partly'];
    if ($code === 3) return ['label' => 'Cloudy', 'icon' => 'fa-cloud', 'tone' => 'cloud'];
    if (in_array($code, [45, 48], true)) return ['label' => 'Fog', 'icon' => 'fa-smog', 'tone' => 'fog'];
    if ($code >= 51 && $code <= 67) return ['label' => 'Rain', 'icon' => 'fa-cloud-rain', 'tone' => 'rain'];
    if ($code >= 71 && $code <= 77) return ['label' => 'Snow', 'icon' => 'fa-snowflake', 'tone' => 'snow'];
    if ($code >= 80 && $code <= 82) return ['label' => 'Showers', 'icon' => 'fa-cloud-showers-heavy', 'tone' => 'rain'];
    if ($code >= 95) return ['label' => 'Storm', 'icon' => 'fa-cloud-bolt', 'tone' => 'storm'];
    return ['label' => 'Cloudy', 'icon' => 'fa-cloud', 'tone' => 'cloud'];
}

function get_latest_e_news($limit = 5)
{
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT * FROM e_news_editions WHERE status = 'published' ORDER BY edition_date DESC, id DESC LIMIT :lim");
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}
