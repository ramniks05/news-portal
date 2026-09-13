<?php
/**
 * Seed demo ads into empty slots (image banners → /advertise).
 * No paid ad network API — these are placeholders for sales demos.
 *
 * CLI: php admin/seed_demo_ads.php
 * Or use Ads Manager → Fill demo ads
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

function seed_demo_ads(PDO $conn)
{
    $dest = rtrim(BASE_URL, '/') . '/advertise';

    $demos = [
        ['Header Leaderboard', 'header_top', 'https://placehold.co/970x90/0f172a/ffffff?text=Your+Brand+Here+-+Advertise', $dest],
        ['Sidebar Top Promo', 'sidebar_top', 'https://placehold.co/300x250/4f46e5/ffffff?text=Sidebar+Ad+300x250', $dest],
        ['Sidebar Bottom Promo', 'sidebar_bottom', 'https://placehold.co/300x250/059669/ffffff?text=Promote+Local+Business', $dest],
        ['Article Bottom Banner', 'article_bottom', 'https://placehold.co/728x90/e11d48/ffffff?text=Sponsored+-+Advertise+With+Us', $dest],
        ['Footer Wide Banner', 'footer_top', 'https://placehold.co/970x90/1e293b/94a3b8?text=Footer+Sponsorship+Available', $dest],
    ];

    $check = $conn->prepare('SELECT id FROM ads WHERE location = :loc AND name = :name LIMIT 1');
    $ins = $conn->prepare("INSERT INTO ads (name, location, type, image_path, destination_url, code, status)
        VALUES (:name, :loc, 'image', :img, :url, NULL, 1)");

    $added = 0;
    foreach ($demos as [$name, $loc, $img, $url]) {
        $check->execute([':loc' => $loc, ':name' => $name]);
        if ($check->fetch()) {
            continue;
        }
        // Keep image URL under 255 chars
        if (strlen($img) > 255) {
            $img = substr($img, 0, 255);
        }
        $ins->execute([
            ':name' => $name,
            ':loc' => $loc,
            ':img' => $img,
            ':url' => $url,
        ]);
        $added++;
    }

    return $added;
}

// CLI run
if (php_sapi_name() === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $n = seed_demo_ads($conn);
    echo "Demo ads added: $n\n";
}
