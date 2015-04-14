<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

abstract class AbstractDocument
{
    /**
     * Whether the mapping defines an abstract document
     *
     * @var bool
     */
    public $abstract = false;
}