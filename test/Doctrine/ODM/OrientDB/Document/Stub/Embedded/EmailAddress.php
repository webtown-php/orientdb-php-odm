<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub\Embedded;

/**
 * @EmbeddedDocument(class="EmbeddedEmailAddress")
 */
class EmailAddress
{
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
}