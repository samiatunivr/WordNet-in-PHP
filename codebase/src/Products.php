<?php
declare(strict_types=1);

namespace Asl;

final class Products
{
    public static function active(): array
    {
        $rows = Db::all('SELECT * FROM products WHERE is_active = 1 ORDER BY is_featured DESC, sort_order ASC, id DESC');
        return self::withCoverImages($rows);
    }

    public static function findActiveBySlug(string $slug): ?array
    {
        return Db::one('SELECT * FROM products WHERE slug = ? AND is_active = 1', [$slug]);
    }

    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM products WHERE id = ?', [$id]);
    }

    public static function images(int $productId): array
    {
        return Db::all('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC', [$productId]);
    }

    public static function withCoverImages(array $rows): array
    {
        if (!$rows) {
            return [];
        }
        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $covers = [];
        foreach (Db::all("SELECT product_id, filename FROM product_images WHERE product_id IN ($in) ORDER BY sort_order ASC, id ASC", $ids) as $img) {
            $covers[$img['product_id']] ??= $img['filename'];
        }
        foreach ($rows as &$r) {
            $r['cover'] = $covers[$r['id']] ?? null;
        }
        return $rows;
    }

    public static function imageUrl(?string $filename): string
    {
        return $filename ? '/uploads/products/' . rawurlencode($filename) : '/assets/img/placeholder.svg';
    }

    /** Lowest "from" price for listing cards. */
    public static function fromPrice(array $p): ?array
    {
        if ($p['price_per_kg_cents'] !== null) {
            return ['cents' => (int) $p['price_per_kg_cents'], 'per' => 'kg'];
        }
        if ($p['price_per_l_cents'] !== null) {
            return ['cents' => (int) $p['price_per_l_cents'], 'per' => 'l'];
        }
        return null;
    }
}
