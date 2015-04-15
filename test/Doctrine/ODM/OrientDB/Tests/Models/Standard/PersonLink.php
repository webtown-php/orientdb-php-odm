<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Standard;
use Doctrine\ODM\OrientDB\Collections\PersistentCollection;

/**
 * @Document(oclass="PersonLink")
 */
class PersonLink
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
     * @Link(targetDoc="EmailAddressLink", nullable=true, cascade={"persist"}, orphanRemoval=true)
     * @var EmailAddressLink
     */
    public $email;

    /**
     * @Link(targetDoc="EmailAddressLink", nullable=true, cascade={"persist"})
     * @var EmailAddressLink
     */
    public $emailNoOrphan;

    /**
     * @LinkList(targetDoc="EmailAddressLink", cascade={"persist"}, orphanRemoval=true)
     * @var EmailAddressLink[]|PersistentCollection
     */
    public $emails;

    /**
     * @LinkMap(targetDoc="PhoneLink", cascade={"persist"}, orphanRemoval=true)
     * @var PhoneLink[]|PersistentCollection
     */
    public $phones;
}