<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Relationship extends AbstractPersistentDocument
{
    /**
     * @Required
     * @var string
     */
    public $oclass;
}