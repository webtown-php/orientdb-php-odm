<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Embedded;

/**
 * @EmbeddedDocument(oclass="EmbeddedEmailAddress")
 */
class EmailAddress
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