<?php

namespace Doctrine\ODM\OrientDB\Mapping\Annotations;

class VertexLink
{
    /**
     * Name of target class; otherwise, <code>null</code> if linked type is polymorphic with no common ancestor
     *
     * @var string
     */
    public $targetDoc = null;
}