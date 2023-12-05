<?php
namespace App\Test\Fixture;

use App\Model\Entity\Product;

class ProductsFixture extends ApplicationTestFixture
{

    public $import = ['model' => 'Products'];

    public $records = [
        [
            'id' => 1,
            'name' => 'test subscription',
        ],
        [
            'id' => 2,
            'name' => 'test product',
        ],
    ];
}
