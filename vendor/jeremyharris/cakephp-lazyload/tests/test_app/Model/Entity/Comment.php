<?php
namespace JeremyHarris\LazyLoad\TestApp\Model\Entity;

use JeremyHarris\LazyLoad\TestApp\Model\Entity\LazyLoadableEntity;

class Comment extends LazyLoadableEntity
{

    protected function _getAccessor()
    {
        return 'accessor';
    }
}
