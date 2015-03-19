<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Linked;

/**
 * @Document(oclass="LinkedEmailAddress")
 */
class EmailAddress
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
     * @Property(type="string", nullable=false)
     * @var string
     */
    public $type;

    /**
     * @Property(type="string", nullable=false)
     * @var string
     */
    public $email;

    /**
     * @Link(targetDoc="Contact", childProperty="email")
     * @var Contact
     */
    public $contact;
}