<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub\Contact;

/**
* @Document(class="EmptyAddress")
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
