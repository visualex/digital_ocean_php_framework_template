<?php

namespace App\Test\TestCase;

use App\Model\Entity\Group;
use App\PressCable\Application\Site;
use App\PressCable\Email\EmailConfig;
use Cake\ORM\TableRegistry;
use App\PressCable\Session\CurrentSession;
use App\PressCable\Session\Roles;

class UsersTableTest extends ApplicationTest
{
    public function setUp()
    {
        parent::setUp();
        $this->Users = TableRegistry::get('Users');
    }

    public function tearDown()
    {
        unset($this->Users);
        parent::tearDown();
    }

    public function testUser()
    {
        $user = $this->Users->get(123);
        $this->assertTrue($user->login == 'testuser1');
    }

}
