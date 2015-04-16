<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Graph;

/**
 * @Relationship(oclass="E")
 */
class Edge
{
    /**
     * @RID
     * @var string
     */
    public $rid;

    /**
     * @In
     * @var object
     */
    public $in;

    /**
     * @Out
     * @var object
     */
    public $out;
}