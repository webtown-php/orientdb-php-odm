<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

class TypedCollectionBase extends PropertyBase
{
    /**
     * @Required
     * @var string
     */
    public $type;
}