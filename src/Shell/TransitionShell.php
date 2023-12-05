<?php

/*
 *
 * TransitionShell is used to migrate our app db.
 * Use it to share modifications done in the db with the dev team and apply the changes in the release.
 * Add methods below the run method, the public methods will be executed in the order they apear in this file.
 * If you need extra utility methods, use private methods, these will not be executed.
 *
 *
 *
 * ***Important:***
 *
 * Your code in this shell should not give errors if executed more than once.
 *
 * Do not return results from DataSource::execute(). Rather test that records have been inserted.
 *
 * Test your DB updates with the latest production DB. There is now more data than when you started reading this.
 *
 * Test your transitions locally first, with latest production DB.
 *
 * Return true from all the public methods if they have been executed correctly.
 *
 * Return === false from the public methods to notify the CI environment that there was an error and that it should not continue building, we will get a slack notification.
 *
 * Use the private getDataSource method to select the DataSource, we run this with parameters for different DataSources,
 * so don't select the DataSource yourself, our CI environment will do it for you.
 *
 * Clean up your transition methods.
 *
 *
 */

namespace App\Shell;

use Cake\Console\Shell;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;

class TransitionShell extends Shell
{
    private $datasource;
    /**
     * Use this method to get the DataSource.
     * We need to set the DataSource externally from CI
     * @return \Cake\Database\Connection
     */
    private function getTheDataSource(): Connection
    {
        return ConnectionManager::get($this->datasource);
    }

    private function tableExists($tableName)
    {
        $db = $this->getTheDataSource();
        $query = $db->execute('SHOW TABLES LIKE "' . $tableName . '"');
        return $query->fetchAll('assoc');
    }

    private function columnExists($tableName, $columnName)
    {
        $db = $this->getTheDataSource();
        $query = $db->execute("SHOW COLUMNS FROM `" . $tableName . "` like '" . $columnName . "';");
        return $query->fetchAll('assoc');
    }

    private function valueExists($tableName, $columnName, $value)
    {
        $db = $this->getTheDataSource();
        if (is_null($value)) {
            $query = $db->execute('SELECT * from ' . $tableName . ' where ' . $columnName . ' IS NULL LIMIT 1;');
        } else {
            $query = $db->execute('SELECT * from ' . $tableName . ' where ' . $columnName . ' = "' . $value . '" LIMIT 1;');
        }
        return $query->fetch('assoc');
    }

    /**
     * The main method that gets called during the release and by Jenkins from main()
     * This method runs all the methods in this class in their order
     */
    private function run()
    {
        $class = new \ReflectionClass(get_class($this));
        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class == $class->getName() && $method->name != __FUNCTION__ && $method->name != 'main') {
                echo 'Executing ' . $method->name . "\n";
                if (false === $this->{$method->name}()) {
                    // transitions were not successful, stop the global process
                    debug('Transition unsuccessful in method: ' . $method->name);
                    exit(1);
                }
            }
        }
        exit(0); // transitions were successful, continue
    }

    /**
     * Template for your methods
     * Use this method as a template for your transitions
     */
    //public function template()
    //{
        // $sql = 'SELECT * from users LIMIT 10';
        // $db = $this->getTheDataSource();
        // $result = $db->execute($sql);
        // debug($result);
        // return true; // return true if correctly processed
        //
        // $db->execute(file_get_contents(ROOT . '/src/Shell/Transition.sql'));
        // if (...) { // test that the transition was successful
        //      return false; // false will halt the CI process, send slack message
        // }
    //}

    public function main($datasource = 'default')
    {
        $this->datasource = $datasource;
        $this->run();
    }

    public function addProductsTable() {
        $db = $this->getTheDataSource();

        if (!$this->tableExists("products")) {
            $sql = "
                CREATE TABLE `products` (
                  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                  `name` varchar(64) NOT NULL DEFAULT '',
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ";
            $result = $db->execute($sql);
        }

        return true;
    }
}
