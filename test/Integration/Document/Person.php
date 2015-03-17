<?php

namespace Integration\Document;
use Doctrine\ODM\OrientDB\PersistentCollection;

/**
 * @Document(class="Person")
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
     * @Embedded(targetClass="EmailAddress", nullable=true)
     * @var EmailAddress
     */
    public $email;

    /**
     * @EmbeddedList(targetClass="EmailAddress")
     * @var EmailAddress[]|PersistentCollection
     */
    public $emails;

    /**
     * @EmbeddedMap(targetClass="Phone")
     * @var Phone[]|PersistentCollection
     */
    public $phones;
}