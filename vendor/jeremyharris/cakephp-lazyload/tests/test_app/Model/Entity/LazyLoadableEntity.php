<?php
namespace JeremyHarris\LazyLoad\TestApp\Model\Entity;

use Cake\ORM\Entity;
use JeremyHarris\LazyLoad\ORM\LazyLoadEntityTrait;

class LazyLoadableEntity extends Entity
{
    use LazyLoadEntityTrait;
}
