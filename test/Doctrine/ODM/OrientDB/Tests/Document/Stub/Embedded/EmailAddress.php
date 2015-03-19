<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Embedded;

/**
 * @EmbeddedDocument(oclass="EmbeddedEmailAddress")
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