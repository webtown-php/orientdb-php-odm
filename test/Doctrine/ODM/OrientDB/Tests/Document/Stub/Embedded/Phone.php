<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Embedded;

/**
 * @EmbeddedDocument(oclass="EmbeddedPhone")
 */
class Phone
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
    public $phoneNumber;

    /**
     * @Property(type="boolean", nullable=false)
     * @var bool
     */
    public $primary;
}