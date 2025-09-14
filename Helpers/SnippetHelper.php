<?php
namespace Helpers;

use Database\MySQLWrapper;
use Exception;

class SnippetHelper
{
    // Generate a short unique slug. Uses hash() per requirement.
    private static function makeSlug(string $content): string {
        $base = bin2hex(random_bytes(8)) . microtime(true) . $content;
        return substr(hash('sha256', $base), 0, 16);
    }

    // Map friendly expiry choices to minutes
    public static function resolveExpiry(?string $choice): ?string {
        $now = new \DateTimeImmutable('now');
        switch (strtolower((string)$choice)) {
            case '10m':  return $now->modify('+10 minutes')->format('Y-m-d H:i:s');
            case '1h':   return $now->modify('+1 hour')->format('Y-m-d H:i:s');
            case '1d':   return $now->modify('+1 day')->format('Y-m-d H:i:s');
            case 'keep':
            case '':     return null; // persistent
            default:     return null; // fallback
        }
    }

    public static function create(string $content, ?string $language, ?string $expiryChoice, ?string $ip, ?string $ua): string {
        $db = new MySQLWrapper();

        // validations
        $content = ValidationHelper::string($content, 1, 1000000); // 1MB cap
        $language = $language ? ValidationHelper::string($language, 0, 64) : null;

        $slug = self::makeSlug($content);
        $expiresAt = self::resolveExpiry($expiryChoice);

        $ipHash = $ip ? hash('sha256', $ip) : null;
        $ua     = $ua ? ValidationHelper::string($ua, 0, 255) : null;

        $stmt = $db->prepare("INSERT INTO snippets (slug, language, content, expires_at, ip_hash, user_agent) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('ssssss', $slug, $language, $content, $expiresAt, $ipHash, $ua);
        $stmt->execute();

        return $slug;
    }

    public static function getBySlug(string $slug): ?array {
        $db = new MySQLWrapper();
        $slug = ValidationHelper::string($slug, 1, 32);

        // Drop (or mark) expired snippets automatically
        $db->query("DELETE FROM snippets WHERE expires_at IS NOT NULL AND expires_at < NOW()");

        $stmt = $db->prepare("SELECT * FROM snippets WHERE slug = ? LIMIT 1");
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();

        if (!$row) return null;
        if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
            return null; // treat as expired
        }
        return $row;
    }

    public static function getNewest(int $page, int $perpage): array {
        $db = new MySQLWrapper();
        $offset = ($page - 1) * $perpage;
        $stmt = $db->prepare("SELECT slug, language, created_at, expires_at FROM snippets WHERE (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $perpage, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
