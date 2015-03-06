<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub\Contact;

/**
 * @Document(class="ContactEmptyAddress")
 */
class EmptyAddress
{
    /**
     * @RID
     */
    public $rid;

    /**
     * @Property(type="string", nullable=true)
     */
    public $string;

    /**
     * @Property(type="integer", nullable=true)
     */
    public $integer;
}
