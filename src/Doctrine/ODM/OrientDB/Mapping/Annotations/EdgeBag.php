<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class EdgeBag
{
    /**
     * The name of the mapped edge class
     *
     * @Required
     * @var string
     */
    public $targetDoc;

    /**
     * The name of the OrientDB edge class
     *
     * @Required
     * @var string
     */
    public $oclass;

    /**
     * @Required
     * @Enum({"in", "out"})
     * @var string
     */
    public $direction;
}