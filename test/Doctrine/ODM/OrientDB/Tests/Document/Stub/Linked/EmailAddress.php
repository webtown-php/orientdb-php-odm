<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Linked;

/**
 * @Document(class="LinkedEmailAddress")
 */
class EmailAddress
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
    public $email;

    /**
     * @Link(targetClass="Contact", childProperty="email")
     * @var Contact
     */
    public $contact;
}