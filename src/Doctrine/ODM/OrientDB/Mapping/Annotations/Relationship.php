<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Relationship extends AbstractDocument
{
    /**
     * @Required
     * @var string
     */
    public $oclass;
}