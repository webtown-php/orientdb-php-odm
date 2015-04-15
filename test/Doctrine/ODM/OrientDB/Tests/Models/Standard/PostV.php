<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Standard;

/**
 * @Vertex(oclass="PostV")
 */
class PostV extends Vertex
{
    /**
     * @Property(type="string")
     * @var string
     */
    public $title;

    /**
     * @RelatedToVia(targetDoc="LikedE", oclass="LikedE", direction="in")
     * @var \Doctrine\ODM\OrientDB\Collections\PersistentCollection
     */
    public $liked;

    /**
     * @RelatedToVia(targetDoc="LikedE", oclass="LikedE", direction="out")
     * @var \Doctrine\ODM\OrientDB\Collections\PersistentCollection|LikedE[]
     */
    public $likes;

}