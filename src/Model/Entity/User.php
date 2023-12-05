<?php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Core\Configure;
use Cake\Auth\DefaultPasswordHasher;
use Cake\Routing\Router;

class User extends Entity
{
    use \Cake\Log\LogTrait;


    protected $_accessible = [
        '*' => true,
    ];

}
