<?php
use Helpers\ValidationHelper;
use Response\HTTPRenderer;
use Response\Render\HTMLRenderer;
use Response\Render\JSONRenderer;

use Database\DataAccess\Implementations\ComputerPartDAOImpl; // NEW
use Models\ComputerPart;                                     // NEW
use Types\ValueType;                                         // NEW

return [
    // --- Computer parts (DAO) ---
    'random/part' => function(): HTTPRenderer {
        $dao  = new ComputerPartDAOImpl();
        $part = $dao->getRandom();
        if (!$part) { throw new \Exception('No parts are available!'); }
        return new HTMLRenderer('component/computer-part-card', ['part' => $part]);
    },

    'parts' => function(): HTTPRenderer {
        $id   = ValidationHelper::integer($_GET['id'] ?? null);
        $dao  = new ComputerPartDAOImpl();
        $part = $dao->getById($id);
        if (!$part) { throw new \Exception('Specified part was not found!'); }
        return new HTMLRenderer('component/computer-part-card', ['part' => $part]);
    },

    // Form page for create/update
    'update/part' => function(): HTMLRenderer {
        $part = null;
        if (isset($_GET['id'])) {
            $id   = ValidationHelper::integer($_GET['id']);
            $dao  = new ComputerPartDAOImpl();
            $part = $dao->getById($id);
        }
        return new HTMLRenderer('component/update-computer-part', ['part' => $part]);
    },

    // AJAX target: create or update
    'form/update/part' => function(): HTTPRenderer {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                throw new \Exception('Invalid request method!');
            }

            $required_fields = [
                'name'              => ValueType::STRING,
                'type'              => ValueType::STRING,
                'brand'             => ValueType::STRING,
                'modelNumber'       => ValueType::STRING,
                'releaseDate'       => ValueType::DATE,
                'description'       => ValueType::STRING,
                'performanceScore'  => ValueType::INT,
                'marketPrice'       => ValueType::FLOAT,
                'rsm'               => ValueType::FLOAT,
                'powerConsumptionW' => ValueType::FLOAT,
                'lengthM'           => ValueType::FLOAT,
                'widthM'            => ValueType::FLOAT,
                'heightM'           => ValueType::FLOAT,
                'lifespan'          => ValueType::INT,
            ];

            $validated = ValidationHelper::validateFields($required_fields, $_POST);
            if (isset($_POST['id']) && $_POST['id'] !== '') {
                $validated['id'] = ValidationHelper::integer($_POST['id']);
            }

            // Optional: domain constraints (examples)
            if (!in_array($validated['type'], ['CPU','GPU','Motherboard','RAM','SSD'], true)) {
                throw new \InvalidArgumentException('Invalid type.');
            }
            if ($validated['powerConsumptionW'] < 0 || $validated['powerConsumptionW'] > 400) {
                throw new \InvalidArgumentException('Power consumption out of range.');
            }

            $part = new ComputerPart(...$validated);
            $dao  = new ComputerPartDAOImpl();

            $ok = isset($validated['id'])
                ? $dao->update($part)
                : $dao->create($part);

            if (!$ok) throw new \Exception('Database update failed!');

            return new JSONRenderer(['status' => 'success', 'message' => 'Part updated successfully']);
        } catch (\InvalidArgumentException $e) {
            error_log($e->getMessage());
            return new JSONRenderer(['status' => 'error', 'message' => 'Invalid data.']);
        } catch (\Throwable $e) {
            error_log($e->getMessage());
            return new JSONRenderer(['status' => 'error', 'message' => 'An error occurred.']);
        }
    },

    // --- keep your other snippet / image routes below unchanged ---
    // ... (paste your existing 'paste', 'api/snippets', 'images/upload', etc. here) ...
];

