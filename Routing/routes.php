<?php
use Helpers\DatabaseHelper;
use Helpers\ValidationHelper;
use Helpers\SnippetHelper;
use Helpers\ImageHelper;
use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Response\Render\JSONRenderer;

return [
    // --- Computer parts ---
    'random/part' => function(): HTTPRenderer {
        $part = DatabaseHelper::getRandomComputerPart();
        return new HTMLRenderer('component/computer-part-card', ['part' => $part]);
    },

    'parts' => function(): HTTPRenderer {
        $id = ValidationHelper::integer($_GET['id'] ?? null);
        $part = DatabaseHelper::getComputerPartById($id);
        return new HTMLRenderer('component/computer-part-card', ['part' => $part]);
    },

    // --- Snippet UI ---
    'paste' => function(): HTTPRenderer {
        return new HTMLRenderer('paste/new');
    },

    'paste/create' => function(): HTTPRenderer {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return new HTMLRenderer('paste/new', ['error' => 'Invalid method']);
        }
        $content  = $_POST['content']  ?? '';
        $language = $_POST['language'] ?? '';
        $expiry   = $_POST['expiry']   ?? 'keep';

        try {
            $slug = SnippetHelper::create(
                $content,
                $language ?: null,
                $expiry ?: null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
            header('Location: /s/' . $slug, true, 302);
            exit;
        } catch (\Throwable $e) {
            return new HTMLRenderer('paste/new', ['error' => $e->getMessage()]);
        }
    },

    's' => function(): HTTPRenderer {
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $parts = explode('/', $path, 3);
        $slug = $parts[1] ?? '';
        $snippet = SnippetHelper::getBySlug($slug);
        return new HTMLRenderer('snippet/show', ['snippet' => $snippet]);
    },

    'raw' => function(): HTTPRenderer {
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $parts = explode('/', $path, 3);
        $slug = $parts[1] ?? '';
        $snippet = SnippetHelper::getBySlug($slug);

        return new class($snippet) implements HTTPRenderer {
            private ?array $s;
            public function __construct($s){ $this->s = $s; }
            public function getFields(): array { return ['Content-Type' => 'text/plain; charset=UTF-8']; }
            public function getContent(): string {
                return $this->s ? (string)$this->s['content'] : "Expired Snippet";
            }
        };
    },

    'api/snippets' => function(): HTTPRenderer {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return new JSONRenderer(['error' => 'Invalid method']);
        }
        $payload = file_get_contents('php://input');
        $json = json_decode($payload, true) ?? [];

        try {
            $slug = SnippetHelper::create(
                (string)($json['content'] ?? ''),
                isset($json['language']) ? (string)$json['language'] : null,
                isset($json['expiry']) ? (string)$json['expiry'] : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            );
            return new JSONRenderer(['slug' => $slug, 'url' => '/s/'.$slug, 'raw' => '/raw/'.$slug]);
        } catch (\Throwable $e) {
            http_response_code(400);
            return new JSONRenderer(['error' => $e->getMessage()]);
        }
    },

    'api/snippets/get' => function(): HTTPRenderer {
        $slug = $_GET['slug'] ?? '';
        try {
            $snippet = SnippetHelper::getBySlug(ValidationHelper::string($slug, 1, 32));
            if (!$snippet) {
                http_response_code(404);
                return new JSONRenderer(['error' => 'Expired Snippet']);
            }
            return new JSONRenderer(['snippet' => $snippet]);
        } catch (\Throwable $e) {
            http_response_code(400);
            return new JSONRenderer(['error' => $e->getMessage()]);
        }
    },

    // --- Image Hosting ---
    'images/upload' => function(): HTTPRenderer {
        // GET = show form
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return new HTMLRenderer('images/upload');
        }

        // POST = handle upload
        try {
            $result = ImageHelper::handleUpload();
            header('Location: /i/' . $result['slug'], true, 302);
            exit;
        } catch (\Throwable $e) {
            return new HTMLRenderer('images/upload', ['error' => $e->getMessage()]);
        }
    },

    'i' => function(): HTTPRenderer {
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $parts = explode('/', $path, 3);
        $slug = $parts[1] ?? '';

        $image = ImageHelper::getBySlug($slug);
        if (!$image) {
            http_response_code(404);
            return new HTMLRenderer('images/deleted', ['message' => 'Not found or expired.']);
        }
        return new HTMLRenderer('images/show', ['image' => $image]);
    },

    'd' => function(): HTTPRenderer {
        $path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $parts = explode('/', $path, 3);
        $token = $parts[1] ?? '';

        try {
            $ok = ImageHelper::deleteByToken($token);
            return new HTMLRenderer('images/deleted', [
                'message' => $ok ? 'Image deleted.' : 'Invalid or already deleted.'
            ]);
        } catch (\Throwable $e) {
            http_response_code(400);
            return new HTMLRenderer('images/deleted', ['message' => $e->getMessage()]);
        }
    },

    'api/images' => function(): HTTPRenderer {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            return new JSONRenderer(['error' => 'Invalid method']);
        }
        try {
            $result = ImageHelper::handleUpload();
            return new JSONRenderer([
                'slug' => $result['slug'],
                'url'  => '/i/' . $result['slug'],
                'delete_url' => '/d/' . $result['delete_token'],
            ]);
        } catch (\Throwable $e) {
            http_response_code(400);
            return new JSONRenderer(['error' => $e->getMessage()]);
        }
    },
];

