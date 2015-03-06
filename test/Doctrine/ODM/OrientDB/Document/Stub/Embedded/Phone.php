<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub\Embedded;

/**
 * @EmbeddedDocument(class="EmbeddedPhone")
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