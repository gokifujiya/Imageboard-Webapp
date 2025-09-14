<?php
namespace Database;

interface Seeder
{
    /** Run the seeder (inserts rows) */
    public function seed(): void;

    /**
     * Return an array of rows to insert.
     * Each row is an ordered array whose values match $this->tableColumns.
     */
    public function createRowData(): array;
}
