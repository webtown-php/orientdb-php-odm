<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Linked;

/**
 * @Document(oclass="LinkedPhone")
 */
class Phone
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
     * @Property(type="string")
     * @var string
     */
    public $phone;
}