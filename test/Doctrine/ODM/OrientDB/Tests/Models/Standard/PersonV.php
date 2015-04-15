<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Standard;
use Doctrine\ODM\OrientDB\Collections\PersistentCollection;

/**
 * @Vertex(oclass="PersonV")
 */
class PersonV extends Vertex
{
    /**
     * @Property(type="string")
     * @var string
     */
    public $name;
    /**
     * @RelatedTo(oclass="LikedE", direction="in")
     * @var \Doctrine\ODM\OrientDB\Collections\PersistentCollection
     */
    public $liked;

    /**
     * @RelatedTo(oclass="LikedE", direction="out")
     * @var \Doctrine\ODM\OrientDB\Collections\PersistentCollection|LikedE[]
     */
    public $likes;

    /**
     * @RelatedTo(targetDoc="PersonV", oclass="FollowedE", direction="in")
     * @var PersistentCollection|PersonV[]
     */
    public $followers;

    /**
     * @RelatedTo(targetDoc="PersonV", oclass="FollowedE", direction="out")
     * @var \Doctrine\ODM\OrientDB\Collections\PersistentCollection|PersonV[]
     */
    public $follows;
}