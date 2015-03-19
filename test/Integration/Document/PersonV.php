<?php

namespace Integration\Document;
use Doctrine\ODM\OrientDB\PersistentCollection;

/**
 * @Vertex(oclass="PersonV")
 */
class PersonV extends Vertex
{
    /**
     * @EdgeBag(targetDoc="LikedE", oclass="LikedE", direction="in")
     * @var PersistentCollection|LikedE[]
     */
    public $liked;

    /**
     * @EdgeBag(targetDoc="LikedE", oclass="LikedE", direction="out")
     * @var PersistentCollection|LikedE[]
     */
    public $likes;

    /**
     * @EdgeBag(targetDoc="FollowedE", oclass="FollowedE", direction="in")
     * @var PersistentCollection|FollowedE[]
     */
    public $followers;

    /**
     * @EdgeBag(targetDoc="FollowedE", oclass="FollowedE", direction="out")
     * @var PersistentCollection|FollowedE[]
     */
    public $followed;
}