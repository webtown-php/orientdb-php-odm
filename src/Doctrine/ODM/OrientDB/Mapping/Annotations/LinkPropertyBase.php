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

    /**
     * @var bool
     */
    public $orphanRemoval = false;

    /**
     * Specified on the parent class in order to identify the parent property on the child.
     *
     * @var string
     */
    public $parentProperty;

    /**
     * Specified on the child class in order to identify the associated property on the parent
     *
     * @var string
     */
    public $childProperty;

}