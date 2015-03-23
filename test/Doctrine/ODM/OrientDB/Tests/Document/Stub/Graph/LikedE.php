<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Graph;

/**
 * @Relationship(oclass="LikedE")
 */
class LikedE
{
    /**
     * @RID
     * @var string
     */
    public $rid;

    /**
     * @Out
     * @var
     */
    public $out;

    /**
     * @In
     * @var object
     */
    public $in;
}