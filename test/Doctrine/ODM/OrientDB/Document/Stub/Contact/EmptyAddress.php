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
     * @Property(type="string", notnull="false")
     */
    public $string;
    
    /**
     * @Property(type="integer", notnull="false")
     */
    public $integer;
}
