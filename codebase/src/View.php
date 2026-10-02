<?php
declare(strict_types=1);

namespace Asl;

final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        $content = self::capture($template, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture($layout, $data + ['content' => $content]);
    }

    public static function capture(string $template, array $data = []): string
    {
        if (!preg_match('#^[a-z0-9_/]+$#', $template)) {
            throw new \InvalidArgumentException('Bad template name');
        }
        $file = ASL_ROOT . '/views/' . $template . '.php';
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }

    public static function error(int $code, string $message = ''): void
    {
        $key = in_array($code, [403, 404, 405, 419, 429, 500], true) ? 'error.' . $code : 'error.500';
        try {
            self::render('shop/error', ['title' => t($key), 'code' => $code, 'message' => $message], 'layout');
        } catch (\Throwable) {
            echo 'Error ' . $code;
        }
    }
}
