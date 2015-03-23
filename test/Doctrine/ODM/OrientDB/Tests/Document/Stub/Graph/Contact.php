<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Graph;

use Doctrine\ODM\OrientDB\Collections\PersistentCollection;

/**
 * @Vertex(oclass="ContactV")
 */
class Contact
{
    /**
     * @RID
     */
    public $rid;

    /**
     * @RelatedToVia(oclass="liked", direction="out")
     * @var LikedE[]|PersistentCollection
     */
    public $liked;

    /**
     * @RelatedToVia(oclass="liked", direction="in")
     * @var LikedE[]|PersistentCollection
     */
    public $likes;
}