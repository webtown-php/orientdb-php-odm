<?php

namespace Integration\Document;
use Doctrine\ODM\OrientDB\PersistentCollection;

/**
 * @Document(class="PersonLink")
 */
class PersonLink
{
    /**
     * @RID
     * @var string
     */
    public $rid;

    /**
     * @Property(type="string")
     * @var string
     */
    public $name;

    /**
     * @Link(targetClass="EmailAddressLink", nullable=true, cascade={"persist"}, orphanRemoval=true)
     * @var EmailAddressLink
     */
    public $email;

    /**
     * @Link(targetClass="EmailAddressLink", nullable=true, cascade={"persist"})
     * @var EmailAddressLink
     */
    public $emailNoOrphan;

    /**
     * @LinkList(targetClass="EmailAddressLink", cascade={"persist"}, orphanRemoval=true)
     * @var EmailAddressLink[]|PersistentCollection
     */
    public $emails;

    /**
     * @LinkMap(targetClass="PhoneLink", cascade={"persist"}, orphanRemoval=true)
     * @var PhoneLink[]|PersistentCollection
     */
    public $phones;
}