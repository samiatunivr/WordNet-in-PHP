<?php
declare(strict_types=1);

namespace Asl;

/**
 * Safe image uploads. Files are checked by size, real MIME type (magic
 * bytes), decoded dimensions, then fully re-encoded with GD. Re-encoding
 * strips metadata and any embedded payload (polyglot files). The stored
 * name is random; the original name is never used.
 */
final class ImageUpload
{
    private const ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_PIXELS = 40_000_000;
    private const MAX_EDGE = 1600;

    public static function dir(): string
    {
        return ASL_PUBLIC . '/uploads/products';
    }

    /** Normalise $_FILES['x'] (single or multiple) into a list of files. */
    public static function files(string $field): array
    {
        $f = $_FILES[$field] ?? null;
        if (!is_array($f) || !isset($f['tmp_name'])) {
            return [];
        }
        if (!is_array($f['tmp_name'])) {
            return [$f];
        }
        $out = [];
        foreach ($f['tmp_name'] as $i => $tmp) {
            $out[] = [
                'tmp_name' => $tmp,
                'error' => $f['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $f['size'][$i] ?? 0,
            ];
        }
        return $out;
    }

    /** @return string stored filename; throws \RuntimeException on rejection */
    public static function store(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('upload_failed');
        }
        $tmp = (string) $file['tmp_name'];
        if (!is_uploaded_file($tmp) && PHP_SAPI !== 'cli') {
            throw new \RuntimeException('upload_failed');
        }
        $size = filesize($tmp);
        if ($size === false || $size < 1 || $size > (int) Config::get('max_upload_bytes', 5 * 1024 * 1024)) {
            throw new \RuntimeException('upload_too_large');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!in_array($mime, self::ALLOWED, true)) {
            throw new \RuntimeException('upload_bad_type');
        }
        $info = @getimagesize($tmp);
        if (!$info || !in_array($info['mime'] ?? '', self::ALLOWED, true)) {
            throw new \RuntimeException('upload_bad_type');
        }
        [$w, $h] = $info;
        if ($w < 1 || $h < 1 || $w * $h > self::MAX_PIXELS) {
            throw new \RuntimeException('upload_bad_type');
        }
        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png' => @imagecreatefrompng($tmp),
            'image/webp' => @imagecreatefromwebp($tmp),
        };
        if (!$src) {
            throw new \RuntimeException('upload_bad_type');
        }
        $scale = min(1, self::MAX_EDGE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        $useWebp = function_exists('imagewebp');
        $name = Security::randomId(16) . ($useWebp ? '.webp' : '.jpg');
        $path = self::dir() . '/' . $name;
        $ok = $useWebp ? imagewebp($dst, $path, 85) : imagejpeg($dst, $path, 85);
        imagedestroy($dst);
        if (!$ok) {
            throw new \RuntimeException('upload_failed');
        }
        @chmod($path, 0644);
        return $name;
    }

    public static function delete(string $filename): void
    {
        if (preg_match('/^[a-f0-9]{32}\.(webp|jpg)$/', $filename)) {
            $path = self::dir() . '/' . $filename;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
