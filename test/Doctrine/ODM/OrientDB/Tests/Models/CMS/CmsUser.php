<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\CMS;

use Doctrine\ODM\OrientDB\Collections\PersistentCollection;

/**
 * @Document(oclass="CmsUser")
 */
class CmsUser
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
     * @Property(type="string", mandatory=true, nullable=false)
     * @var string
     */
    public $username;

    /**
     * @Property(type="string")
     * @var string
     */
    public $status;

    /**
     * @Property
     */
    public $name;

    /**
     * @EmbeddedList(targetDoc="CmsPhonenumber")
     * @var CmsPhonenumber[]|PersistentCollection
     */
    public $phonenumbers;

    /**
     * @LinkList(targetDoc="CmsArticle")
     * @var CmsArticle[]|PersistentCollection
     */
    public $articles;
}