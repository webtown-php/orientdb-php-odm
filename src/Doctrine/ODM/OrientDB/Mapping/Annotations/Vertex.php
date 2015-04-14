<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Vertex extends AbstractPersistentDocument
{
    /**
     * @Required
     * @var string
     */
    public $oclass;
}