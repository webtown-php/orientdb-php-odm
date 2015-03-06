<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub\Linked;

/**
 * @Document(class="LinkedPhone")
 */
class Phone
{
    /**
     * @RID
     */
    public $rid;

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