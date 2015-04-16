<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Linked;
use Doctrine\ODM\OrientDB\Collections\PersistentCollection;

/**
 * @Document(oclass="LinkedPerson")
 */
class Person
{
    /**
     * @RID
     * @var string
     */
    public $rid;

    /**
     * @Version
     */
    public $version;

    /**
     * @Property(type="string")
     * @var string
     */
    public $name;

    /**
     * @Link(targetDoc="EmailAddress", nullable=true, cascade={"persist"}, orphanRemoval=true)
     * @var EmailAddress
     */
    public $email;

    /**
     * @Link(targetDoc="EmailAddress", nullable=true, cascade={"persist"})
     * @var EmailAddress
     */
    public $emailNoOrphan;

    /**
     * @LinkList(targetDoc="EmailAddress", cascade={"persist"}, orphanRemoval=true)
     * @var EmailAddress[]|PersistentCollection
     */
    public $emails;

    /**
     * @LinkMap(targetDoc="Phone", cascade={"persist"}, orphanRemoval=true)
     * @var Phone[]|PersistentCollection
     */
    public $phones;
}