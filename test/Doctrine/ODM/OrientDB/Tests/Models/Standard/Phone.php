<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Standard;

/**
 * @EmbeddedDocument(oclass="Phone")
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