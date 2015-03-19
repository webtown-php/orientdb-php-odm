<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Embedded;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Document(oclass="EmbeddedContact")
 */
class Contact
{
    /**
     * @RID
     */
    public $rid;

    /**
     * @Version
     */
    public $version;

    /**
     * Display name
     *
     * @Property(type="string", nullable=false)
     * @var string
     */
    public $name;

    /**
     * @Embedded(targetDoc="EmailAddress")
     * @var EmailAddress
     */
    public $email;

    /**
     * @EmbeddedList(targetDoc="Phone")
     * @var Phone[]|Collection
     */
    public $phones;

    function __construct() {
        $this->phones = new ArrayCollection();
    }
}