<?php
namespace Commands\Programs;

use Commands\AbstractCommand;
use Commands\Argument;

class HelloWorld extends AbstractCommand
{
    protected static ?string $alias = 'hello';

    public static function getArguments(): array
    {
        return [];
    }

    public function execute(): int
    {
        $this->log("Hello from HelloWorld!");
        return 0;
    }
}
