<?php
namespace App\Model\Table;

use App\Model\Entity\User;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Utility\Inflector;

class UsersTable extends Table
{
    use \Cake\Log\LogTrait;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->setTable('users');
    }
}
