<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

abstract class RelatedToBase
{
    /**
     * The name of the mapped edge document; leave null to load any type
     *
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