<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Edge extends AbstractDocument
{
    /**
     * @Required
     * @var string
     */
    public $oclass;
}