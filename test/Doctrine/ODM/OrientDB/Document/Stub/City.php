<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub;

/**
* @Document(class="OCity")
*/
class City
{
    /**
     * @RID
     */
    public $rid;

    private $name;
}
