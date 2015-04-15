<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\Standard;

/**
 * @Document(oclass="EmailAddressLink")
 */
class EmailAddressLink
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
     * @Property(type="string", nullable=false)
     * @var string
     */
    public $type;

    /**
     * @Property(type="string", nullable=false)
     * @var string
     */
    public $email;
}