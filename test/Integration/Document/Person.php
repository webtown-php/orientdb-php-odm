<?php

namespace Integration\Document;

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
     * @var EmailAddress[]
     */
    public $emails;
}