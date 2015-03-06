<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub\Simple;

/**
 * @Document(class="SimpleContact")
 */
class Contact
{
    /**
     * @RID
     */
    public $rid;

    /**
     * Display name
     *
     * @Property(type="string", nullable=false)
     * @var string
     */
    public $name;

    /**
     * Height in cm
     *
     * @Property(type="integer", nullable=true)
     * @var int
     */
    public $height;

    /**
     * @Property(type="datetime", nullable=true)
     * @var \DateTime
     */
    public $birthday;

    /**
     * @Property(type="boolean", nullable=false)
     * @var bool
     */
    public $active;
}