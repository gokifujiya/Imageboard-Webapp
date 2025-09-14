<?php
namespace Commands\Programs;

use Commands\AbstractCommand;
use Commands\Argument;
use Database\MySQLWrapper;

class BookSearch extends AbstractCommand
{
    protected static ?string $alias = 'book-search';

    public static function getArguments(): array
    {
        return [
            (new Argument('isbn'))->description('Search by ISBN')->required(false)->allowAsShort(true),
            (new Argument('title'))->description('Search by title')->required(false)->allowAsShort(true),
            (new Argument('force'))->description('Ignore cache and refresh')->required(false)->allowAsShort(true),
        ];
    }

    public function execute(): int
    {
        $isbn  = $this->getArgumentValue('isbn');
        $title = $this->getArgumentValue('title');
        $force = $this->getArgumentValue('force') !== false;

        if (($isbn === false && $title === false) || ($isbn !== false && $title !== false)) {
            $this->log("Provide exactly one of --isbn or --title");
            return 1;
        }

        $mysqli = new MySQLWrapper();
        $this->ensureCacheTable($mysqli);

        if ($isbn !== false) {
            $key = 'book-search-isbn-' . trim((string)$isbn);
            $endpoint = 'https://openlibrary.org/isbn/' . rawurlencode((string)$isbn) . '.json';
            $summary  = $this->fetchWithCache($mysqli, $key, $endpoint, $force);
        } else {
            $q = trim((string)$title);
            $key = 'book-search-title-' . strtolower($q);
            // limit results for readability
            $endpoint = 'https://openlibrary.org/search.json?title=' . rawurlencode($q) . '&limit=5';
            $summary  = $this->fetchWithCache($mysqli, $key, $endpoint, $force);
        }

        $this->log($summary);
        $mysqli->close();
        return 0;
    }

    private function ensureCacheTable(\mysqli $mysqli): void
    {
        $mysqli->query("
            CREATE TABLE IF NOT EXISTS kv_cache (
                cache_key VARCHAR(191) PRIMARY KEY,
                cache_value MEDIUMTEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
    }

    private function fetchWithCache(\mysqli $mysqli, string $key, string $url, bool $force): string
    {
        $fresh = null;
        if (!$force) {
            $res = $mysqli->query("SELECT cache_value, TIMESTAMPDIFF(DAY, updated_at, NOW()) AS age_days FROM kv_cache WHERE cache_key = '".$mysqli->real_escape_string($key)."'");
            if ($res && ($row = $res->fetch_assoc())) {
                if ((int)$row['age_days'] <= 30) {
                    return "[CACHE HIT] key={$key}\n" . $this->summarizeJson($row['cache_value']);
                }
            }
        }

        $this->log("[CACHE MISS] Fetching: $url");
        $opts = [
            'http' => [
                'timeout' => 8,
                'header'  => "User-Agent: php_dynamic_server_v2/1.0\r\n",
            ]
        ];
        $ctx = stream_context_create($opts);
        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) {
            return "Failed to fetch from Open Library (network error).";
        }

        $stmt = $mysqli->prepare("
            INSERT INTO kv_cache (cache_key, cache_value) VALUES (?,?)
            ON DUPLICATE KEY UPDATE cache_value=VALUES(cache_value), updated_at=NOW()
        ");
        $stmt->bind_param('ss', $key, $json);
        $stmt->execute();
        $stmt->close();

        return $this->summarizeJson($json);
    }

    private function summarizeJson(string $json): string
    {
        $data = json_decode($json, true);
        if ($data === null) {
            return $json; // raw
        }

        // ISBN endpoint returns a single work
        if (isset($data['title']) && !isset($data['docs'])) {
            $title = $data['title'] ?? '(no title)';
            $by = $data['by_statement'] ?? '';
            return "ISBN result: {$title}" . ($by ? " — {$by}" : "");
        }

        // Search endpoint
        if (isset($data['docs'])) {
            $lines = ["Search results (top " . count($data['docs']) . "):"];
            foreach ($data['docs'] as $doc) {
                $t = $doc['title'] ?? '(no title)';
                $a = isset($doc['author_name']) ? implode(', ', $doc['author_name']) : '';
                $y = $doc['first_publish_year'] ?? '';
                $lines[] = "- {$t}" . ($a ? " — {$a}" : "") . ($y ? " ({$y})" : "");
            }
            return implode("\n", $lines);
        }

        return json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    }
}
