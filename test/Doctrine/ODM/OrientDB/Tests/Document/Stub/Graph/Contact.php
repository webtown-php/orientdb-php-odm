<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Graph;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @Property(type="string")
     * @var string
     */
    public $name;

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

    public function __construct() {
        $this->liked = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }
}