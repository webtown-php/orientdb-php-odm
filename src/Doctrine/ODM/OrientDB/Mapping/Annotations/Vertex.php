<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Vertex extends AbstractDocument
{
    /**
     * @Required
     * @var string
     */
    public $oclass;
}