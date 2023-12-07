<?php
namespace App\Test\Fixture;

use App\Test\TestCase\ApplicationTestTableCacheMonitor;
use Cake\Datasource\ConnectionInterface;
use Cake\TestSuite\Fixture\TestFixture;

// adds caching to TestFixture so we don't throw away and reload test data unless it's dirty

class ApplicationTestFixture extends TestFixture
{
    public function truncate(ConnectionInterface $db)
    {
        // only truncate if data has been modified
        if (ApplicationTestTableCacheMonitor::isDirty($this->table))
        {
            ApplicationTestTableCacheMonitor::markEmpty($this->table);
            return parent::truncate($db);
        }
        return false;
    }

    public function drop(ConnectionInterface $db)
    {
        // only drop if data has been modified (or table is not yet created?)
        if (!ApplicationTestTableCacheMonitor::isCreated($this->table) || ApplicationTestTableCacheMonitor::isDirty($this->table))
        {
            ApplicationTestTableCacheMonitor::markNotCreated($this->table);
            return parent::drop($db);
        }
        return false;
    }

    public function insert(ConnectionInterface $db)
    {
        // insert only if table empty
        if (ApplicationTestTableCacheMonitor::isEmpty($this->table))
        {
            ApplicationTestTableCacheMonitor::markClean($this->table);
            return parent::insert($db);
        }
        return false;
    }

    public function create(ConnectionInterface $db)
    {
        // create only if not created
        if (!ApplicationTestTableCacheMonitor::isCreated($this->table)) {
            ApplicationTestTableCacheMonitor::markEmpty($this->table);
            return parent::create($db);
        }
        return false;
    }
}