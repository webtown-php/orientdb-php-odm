<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Linked;

/**
 * @Document(class="LinkedPhone")
 */
class Phone
{
    /**
     * @RID
     */
    public $rid;

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
     * @Link(targetClass="Contact", childProperty="phones")
     * @var Contact
     */
    public $contact;
}