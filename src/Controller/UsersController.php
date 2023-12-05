<?php

namespace App\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\ORM\Entity;
use Cake\Network\Exception\NotFoundException;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\MethodNotAllowedException;
use Cake\ORM\TableRegistry;
use GuzzleHttp\Exception\ServerException;
use Exception;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function beforeFilter(\Cake\Event\Event $event)
    {
        parent::beforeFilter($event);
        $this->Auth->allow(['test']);
        $this->Security->setConfig('unlockedActions', ['test']);
    }

    public function test()
    {
        $table = TableRegistry::get('Users');
        $saved = $table->save(new Entity([
            'group_id' => 1,
            'login' => 'me@example.org',
            'password' => '123456',
        ]));
    }
}
