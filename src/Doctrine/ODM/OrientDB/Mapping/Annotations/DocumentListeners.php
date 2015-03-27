<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class DocumentListeners
{

    /**
     * Specifies the names of the document listeners.
     *
     * @var string[]
     */
    public $value = [];
}