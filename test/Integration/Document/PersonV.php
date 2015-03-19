<?php

namespace Integration\Document;
use Doctrine\ODM\OrientDB\PersistentCollection;

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
     * @var PersistentCollection
     */
    public $liked;

    /**
     * @RelatedTo(oclass="LikedE", direction="out")
     * @var PersistentCollection|LikedE[]
     */
    public $likes;

    /**
     * @RelatedTo(targetDoc="PersonV", oclass="FollowedE", direction="in")
     * @var PersistentCollection|PersonV[]
     */
    public $followers;

    /**
     * @RelatedTo(targetDoc="PersonV", oclass="FollowedE", direction="out")
     * @var PersistentCollection|PersonV[]
     */
    public $followed;
}