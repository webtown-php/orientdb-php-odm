<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class EmbeddedDocument
{
    /**
     * @Required
     * @var string
     */
    public $class;
}