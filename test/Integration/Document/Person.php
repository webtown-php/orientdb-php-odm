<?php

namespace Integration\Document;
use Doctrine\ODM\OrientDB\Collections\PersistentCollection;

/**
 * @Document(oclass="Person")
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
     * @Embedded(targetDoc="EmailAddress", nullable=true)
     * @var EmailAddress
     */
    public $email;

    /**
     * @EmbeddedList(targetDoc="EmailAddress")
     * @var EmailAddress[]|PersistentCollection
     */
    public $emails;

    /**
     * @EmbeddedMap(targetDoc="Phone")
     * @var Phone[]|PersistentCollection
     */
    public $phones;
}