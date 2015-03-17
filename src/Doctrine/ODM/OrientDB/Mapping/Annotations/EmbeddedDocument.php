<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class EmbeddedDocument extends AbstractDocument
{
    /**
     * @Required
     * @var string
     */
    public $class;
}