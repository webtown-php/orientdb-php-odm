<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub\Embedded;
use Doctrine\Common\Collections\Collection;

/**
 * @Document(class="EmbeddedContact")
 */
class Contact
{
    /**
     * @RID
     */
    public $rid;

    /**
     * Display name
     *
     * @Property(type="string", nullable=false)
     * @var string
     */
    public $name;

    /**
     * @Embedded(targetClass="EmailAddress")
     * @var EmailAddress
     */
    public $email;

    /**
     * @EmbeddedList(targetClass="Phone")
     * @var Phone[]|Collection
     */
    public $phones;
}