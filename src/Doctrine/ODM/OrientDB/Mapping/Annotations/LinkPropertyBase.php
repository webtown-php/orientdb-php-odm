<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

class LinkPropertyBase extends PropertyBase
{
    /**
     * @Required
     * @var string
     */
    public $targetClass;

    /**
     * @var string[]
     */
    public $cascade;
}