<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Embedded;

/**
 * @EmbeddedDocument(oclass="EmbeddedPhone")
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