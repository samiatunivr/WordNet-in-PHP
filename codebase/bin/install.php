<?php
declare(strict_types=1);

/*
 * Usage:  php codebase/bin/install.php [--demo]
 * Creates the database schema and (optionally) a few demo products.
 * Create the first admin afterwards with:  php codebase/bin/admin.php create you@example.com
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/bootstrap.php';

use Asl\Db;

$driver = Db::pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
$schema = $driver === 'mysql' ? 'schema.mysql.sql' : 'schema.sqlite.sql';
$sql = (string) file_get_contents(ASL_ROOT . '/database/' . $schema);
foreach (array_filter(array_map('trim', explode(';', preg_replace('/^--.*$/m', '', $sql)))) as $stmt) {
    Db::pdo()->exec($stmt);
}
echo "Schema installed ($driver).\n";

if (in_array('--demo', $argv, true) && (int) Db::value('SELECT COUNT(*) FROM products') === 0) {
    $now = Db::now();
    $demo = [
        ['sidr-hadramout', 'عسل سدر حضرمي', 'Hadramout Sidr honey', 'Sidr-honing uit Hadramaut', 18900, null, 'g,kg', 1,
            'أفخر أنواع العسل اليمني من وادي دوعن.', 'The most prized Yemeni honey, from the Do\'an valley.', 'De meest geliefde Jemenitische honing, uit de Do\'an-vallei.'],
        ['samr-mountain', 'عسل سمر جبلي', 'Mountain Samr honey', 'Samr-berghoning', 9900, 13900, 'g,kg,l', 0,
            'عسل داكن قوي النكهة من أشجار السمر.', 'Dark, bold honey from the Samr (acacia) trees.', 'Donkere, krachtige honing van de Samr-boom (acacia).'],
        ['maraei-spring', 'عسل مراعي ربيعي', 'Spring Maraei honey', 'Voorjaars Maraei-honing', 5900, 8200, 'mg,g,kg,ml,l', 0,
            'عسل زهور برية متعددة بطعم لطيف.', 'Multi-flower wild honey with a gentle taste.', 'Wilde bloemenhoning met een zachte smaak.'],
    ];
    foreach ($demo as $i => [$slug, $ar, $en, $nl, $kg, $l, $units, $featured, $sar, $sen, $snl]) {
        Db::insert('products', [
            'slug' => $slug, 'name_ar' => $ar, 'name_en' => $en, 'name_nl' => $nl,
            'summary_ar' => $sar, 'summary_en' => $sen, 'summary_nl' => $snl,
            'description_ar' => $sar, 'description_en' => $sen, 'description_nl' => $snl,
            'price_per_kg_cents' => $kg, 'price_per_l_cents' => $l, 'units' => $units,
            'is_active' => 1, 'is_featured' => $featured, 'sort_order' => $i,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }
    echo "Demo products added.\n";
}
