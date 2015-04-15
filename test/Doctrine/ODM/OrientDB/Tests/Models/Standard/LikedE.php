<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Standard;

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