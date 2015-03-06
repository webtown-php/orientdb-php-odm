<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

class EmbeddedPropertyBase extends PropertyBase
{
    /**
     * @Required
     * @var string
     */
    public $targetClass;
}