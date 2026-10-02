<?php
declare(strict_types=1);

namespace Asl;

/**
 * Minimal, dependency-free e-mail sender.
 *
 * Transports: 'smtp' (STARTTLS or implicit TLS, AUTH LOGIN, certificate
 * verification on), 'mail' (PHP mail()), 'log' (writes .eml files to
 * storage/mail for development). Addresses are validated and header values
 * stripped of line breaks, so user data can never inject extra headers.
 */
final class Mailer
{
    /**
     * @param array{to:string,to_name?:string,bcc?:string[],subject:string,html:string,text:string,
     *              attachments?:array<int,array{name:string,type:string,data:string}>} $msg
     */
    public static function send(array $msg): void
    {
        $from = (string) Config::get('mail_from', '');
        $to = (string) $msg['to'];
        if (!self::validEmail($from) || !self::validEmail($to)) {
            throw new \RuntimeException('Invalid sender or recipient address.');
        }
        $bcc = array_values(array_filter($msg['bcc'] ?? [], [self::class, 'validEmail']));
        [$headers, $body] = self::build($msg, $from);

        $transport = (string) Config::get('mail_transport', 'smtp');
        match ($transport) {
            'log' => self::toLog($to, $headers, $body),
            'mail' => self::viaMail($to, $msg['subject'], $headers, $body, $bcc),
            default => self::viaSmtp($from, array_merge([$to], $bcc), $headers, $body),
        };
    }

    public static function validEmail(mixed $e): bool
    {
        return is_string($e) && strlen($e) <= 190 && filter_var($e, FILTER_VALIDATE_EMAIL) !== false
            && !preg_match('/[\r\n]/', $e);
    }

    private static function clean(string $s): string
    {
        return trim(preg_replace('/[\r\n\t]+/', ' ', $s) ?? '');
    }

    private static function encodeHeader(string $s): string
    {
        $s = self::clean($s);
        return preg_match('/[^\x20-\x7e]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    private static function address(string $email, string $name = ''): string
    {
        $name = self::clean($name);
        if ($name === '') {
            return '<' . $email . '>';
        }
        $display = preg_match('/[^\x20-\x7e]/', $name) ? self::encodeHeader($name) : '"' . addcslashes($name, '"\\') . '"';
        return $display . ' <' . $email . '>';
    }

    /** @return array{0: array<string,string>, 1: string} */
    private static function build(array $msg, string $from): array
    {
        $domain = substr(strrchr($from, '@') ?: '@localhost', 1);
        $mixed = 'mix_' . bin2hex(random_bytes(12));
        $alt = 'alt_' . bin2hex(random_bytes(12));

        $headers = [
            'Date' => date(DATE_RFC2822),
            'From' => self::address($from, (string) Config::get('mail_from_name', 'Asl')),
            'To' => self::address($msg['to'], (string) ($msg['to_name'] ?? '')),
            'Subject' => self::encodeHeader($msg['subject']),
            'Message-ID' => '<' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/mixed; boundary="' . $mixed . '"',
            'Auto-Submitted' => 'auto-generated',
        ];

        $part = static fn (string $type, string $data) => "Content-Type: $type\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . rtrim(chunk_split(base64_encode($data), 76, "\r\n")) . "\r\n";

        $body = "--$mixed\r\nContent-Type: multipart/alternative; boundary=\"$alt\"\r\n\r\n"
            . "--$alt\r\n" . $part('text/plain; charset=UTF-8', $msg['text'])
            . "--$alt\r\n" . $part('text/html; charset=UTF-8', $msg['html'])
            . "--$alt--\r\n";
        foreach ($msg['attachments'] ?? [] as $a) {
            $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $a['name']) ?? 'attachment';
            $body .= "--$mixed\r\nContent-Disposition: attachment; filename=\"$name\"\r\n"
                . $part(self::clean($a['type']) . '; name="' . $name . '"', $a['data']);
        }
        $body .= "--$mixed--\r\n";
        return [$headers, $body];
    }

    private static function headerBlock(array $headers): string
    {
        $out = '';
        foreach ($headers as $k => $v) {
            $out .= $k . ': ' . $v . "\r\n";
        }
        return $out;
    }

    private static function toLog(string $to, array $headers, string $body): void
    {
        $dir = ASL_ROOT . '/storage/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $file = $dir . '/' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.eml';
        file_put_contents($file, self::headerBlock($headers) . "\r\n" . $body);
    }

    private static function viaMail(string $to, string $subject, array $headers, string $body, array $bcc): void
    {
        unset($headers['To'], $headers['Subject']);
        if ($bcc) {
            $headers['Bcc'] = implode(', ', $bcc);
        }
        $from = (string) Config::get('mail_from');
        if (!mail($to, self::encodeHeader($subject), $body, $headers, '-f' . $from)) {
            throw new \RuntimeException('mail() failed');
        }
    }

    /** @param string[] $recipients */
    private static function viaSmtp(string $from, array $recipients, array $headers, string $body): void
    {
        $host = (string) Config::get('smtp_host', '');
        $port = (int) Config::get('smtp_port', 587);
        $enc = (string) Config::get('smtp_encryption', 'tls');
        if ($host === '' || !preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
            throw new \RuntimeException('SMTP host not configured.');
        }
        $ctx = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'peer_name' => $host,
            'SNI_enabled' => true,
        ]]);
        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");
        }
        stream_set_timeout($fp, 20);
        try {
            $ehlo = 'EHLO ' . (parse_url(Config::appUrl(), PHP_URL_HOST) ?: 'localhost');
            self::expect($fp, 220);
            self::cmd($fp, $ehlo, 250);
            if ($enc === 'tls') {
                self::cmd($fp, 'STARTTLS', 220);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new \RuntimeException('STARTTLS negotiation failed.');
                }
                self::cmd($fp, $ehlo, 250);
            }
            $user = (string) Config::get('smtp_user', '');
            if ($user !== '') {
                self::cmd($fp, 'AUTH LOGIN', 334);
                self::cmd($fp, base64_encode($user), 334);
                self::cmd($fp, base64_encode((string) Config::get('smtp_pass', '')), 235);
            }
            self::cmd($fp, 'MAIL FROM:<' . $from . '>', 250);
            foreach ($recipients as $r) {
                self::cmd($fp, 'RCPT TO:<' . $r . '>', [250, 251]);
            }
            self::cmd($fp, 'DATA', 354);
            // Dot-stuffing (RFC 5321 §4.5.2)
            $data = preg_replace('/^\./m', '..', self::headerBlock($headers) . "\r\n" . $body);
            fwrite($fp, $data . "\r\n.\r\n");
            self::expect($fp, 250);
            self::cmd($fp, 'QUIT', 221);
        } finally {
            fclose($fp);
        }
    }

    private static function cmd($fp, string $line, int|array $expect): void
    {
        fwrite($fp, $line . "\r\n");
        self::expect($fp, $expect);
    }

    private static function expect($fp, int|array $codes): void
    {
        $codes = (array) $codes;
        $response = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] !== '-') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new \RuntimeException('SMTP error: ' . trim(substr($response, 0, 200)));
        }
    }
}
