<?php
namespace App\Test\TestCase;

// helps us decide when to add/delete table date during test setup. speeds up tests by not requiring frequent drop/re-import

class ApplicationTestTableCacheMonitor
{
    public static $tableStatuses = [];
    const TABLE_EMPTY = 0;
    const TABLE_CLEAN = 1;
    const TABLE_DIRTY = 2;

    static public function isCreated(string $tableName) : bool
    {
        return isset(self::$tableStatuses[$tableName]);
    }

    static public function isEmpty(string $tableName) : bool
    {
        return self::isCreated($tableName) && self::$tableStatuses[$tableName] == self::TABLE_EMPTY;
    }

    static public function isClean(string $tableName) : bool
    {
        return self::isCreated($tableName) && self::$tableStatuses[$tableName] == self::TABLE_CLEAN;
    }

    static public function isDirty(string $tableName) : bool
    {
        return self::isCreated($tableName) && self::$tableStatuses[$tableName] == self::TABLE_DIRTY;
    }

    static public function markNotCreated(string $tableName) : void
    {
        unset(self::$tableStatuses[$tableName]);
    }

    static public function markDirty(string $tableName) : void
    {
        self::$tableStatuses[$tableName] = self::TABLE_DIRTY;
    }

    static public function markClean(string $tableName) : void
    {
        self::$tableStatuses[$tableName] = self::TABLE_CLEAN;
    }

    static public function markEmpty(string $tableName) : void
    {
        self::$tableStatuses[$tableName] = self::TABLE_EMPTY;
    }
}