<?php
namespace Commands\Programs;

use Commands\AbstractCommand;
use Database\MySQLWrapper;
use Database\Seeder;

class Seed extends AbstractCommand
{
    protected static ?string $alias = 'seed';

    public static function getArguments(): array { return []; }

    public function execute(): int
    {
        $this->runAllSeeds();
        return 0;
    }

    private function runAllSeeds(): void
    {
        $directoryPath = __DIR__ . '/../../Database/Seeds';
        if (!is_dir($directoryPath)) {
            throw new \RuntimeException("Seeds directory not found: $directoryPath");
        }

        foreach (scandir($directoryPath) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') continue;

            $className = 'Database\\Seeds\\' . pathinfo($file, PATHINFO_FILENAME);
            include_once $directoryPath . '/' . $file;

            if (class_exists($className) && is_subclass_of($className, Seeder::class)) {
                $seeder = new $className(new MySQLWrapper());
                $this->log("Seeding: $className");
                $seeder->seed();
            } else {
                throw new \Exception("Seeder must implement Database\\Seeder: $className");
            }
        }
    }
}
