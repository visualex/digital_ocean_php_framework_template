<?php
namespace JeremyHarris\LazyLoad\TestApp\Model\Entity;

use JeremyHarris\LazyLoad\TestApp\Model\Entity\LazyLoadableEntity;

class TablelessEntity extends LazyLoadableEntity
{

    protected function _repository()
    {
        return false;
    }
}
