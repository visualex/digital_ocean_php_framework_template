<?php
namespace App\Test\TestCase;

use App\Model\Entity\Product;
use Cake\ORM\TableRegistry;

class ProductsTableTest extends ApplicationTest
{
   public function setUp()
   {
      parent::setUp();
      $this->Products = TableRegistry::get('Products');
   }

   public function tearDown()
   {
      unset($this->Products);
      parent::tearDown();
   }

   public function testProductInFixtures()
   {
      $product = $this->Products->get(1);
      $this->assertTrue($product->name == 'test subscription');
   }

}
