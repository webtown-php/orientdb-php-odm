<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Embedded extends EmbeddedPropertyBase
{
    /**
     * @var bool
     */
    public $nullable = false;
}