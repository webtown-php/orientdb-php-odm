<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Embedded;

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