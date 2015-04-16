<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Graph;
use Doctrine\ODM\OrientDB\Tests\Models\Graph\Edge;

/**
 * @Relationship(oclass="LikedE")
 */
class LikedE extends Edge
{
    /**
     * @Property(type="string")
     * @var string
     */
    public $description;
}