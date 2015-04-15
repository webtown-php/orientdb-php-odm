<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Standard;

/**
 * @Document(oclass="PhoneLink")
 */
class PhoneLink
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