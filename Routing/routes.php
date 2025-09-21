<?php
use Helpers\ValidationHelper;
use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Response\Render\JSONRenderer;

use Database\DataAccess\Implementations\ComputerPartDAOImpl;
use Database\DataAccess\Implementations\PostDAOImpl;
use Models\ComputerPart;
use Models\Post;
use Types\ValueType;

return [
    // ------------------------
    // Computer Parts
    // ------------------------
    'parts/all' => function (): HTMLRenderer {
        $dao = new ComputerPartDAOImpl();
        $parts = $dao->getAll(0, 15);
        return new HTMLRenderer('parts/list', ['parts' => $parts]);
    },

    'parts/type' => function (): HTMLRenderer {
        $dao  = new ComputerPartDAOImpl();
        $type = $_GET['type'] ?? '';
        $parts = $dao->getAllByType($type, 0, 15);
        return new HTMLRenderer('parts/list', ['parts' => $parts, 'type' => $type]);
    },

    // ------------------------
    // Threads (Imageboard)
    // ------------------------
    'threads' => function (): HTTPRenderer {
        $dao = new PostDAOImpl();
        $threads = $dao->getAllThreads(0, 20);
        return new HTMLRenderer('threads/list', ['threads' => $threads]);
    },

    'thread' => function (): HTTPRenderer {
        $id = (int)($_GET['id'] ?? 0);
        $dao = new PostDAOImpl();
        $thread = $dao->getById($id);
        $replies = $dao->getReplies($thread, 0, 100);
        return new HTMLRenderer('threads/detail', [
            'thread'  => $thread,
            'replies' => $replies
        ]);
    },
];

