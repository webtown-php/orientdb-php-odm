<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Linked;

/**
 * @Document(oclass="LinkedPhone")
 */
class Phone
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
    public $phoneNumber;

    /**
     * @Property(type="boolean", nullable=false)
     * @var bool
     */
    public $primary;

    /**
     * @Link(targetDoc="Contact", childProperty="phones")
     * @var Contact
     */
    public $contact;
}