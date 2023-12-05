<?php
namespace App\Test\Fixture;

use App\PressCable\Session\Roles;

class UsersFixture extends ApplicationTestFixture
{

    public $import = ['model' => 'Users'];

    public $records = [
        [
            'id' => 123,
            'group_id' => 1,
            'login' => 'testuser1',
            'password' => '123456',
        ],
    ];
}
