<?php
namespace App\Test\TestCase;

use App\Model\Entity\Credit;
use App\Model\Entity\Product;
use App\Model\Entity\Sale;
use App\PressCable\Sale\SaleHandler;
use Cake\Datasource\ConnectionManager;
use Cake\Event\Event;
use Cake\TestSuite\IntegrationTestCase;
use Cake\ORM\TableRegistry;
use App\PressCable\Session\CurrentSession;

/**
 * ApplicationTest class, all test cases should extend this class to
 * maintain the fixtures only here
 */
class ApplicationTest extends IntegrationTestCase
{
    // add all the fixtures for the tests here:
    public $fixtures = [
        'app.products',
        'app.users',
    ];

    public function setup()
    {
         parent::setup();
         \Cake\Event\EventManager::instance()->on('Model.afterSave', function (Event $event, $entity) {
              ApplicationTestTableCacheMonitor::markDirty($event->subject->table());
         });
         \Cake\Event\EventManager::instance()->on('Model.afterDelete', function (Event $event, $entity) {
              ApplicationTestTableCacheMonitor::markDirty($event->subject->table());
         });
    }

    public function invoke(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    public function privateProperty(&$object, $property)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    public function arrayPluck($array, $key) {
        return array_map(function($v) use ($key)	{
            return is_object($v) ? $v->$key : $v[$key];
        }, $array);
    }

    public function getResponseBody()
    {
        return json_decode($this->_response->getBody(), true);
    }

    public function setAjaxRequest()
    {
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);
    }
}
