<?php

namespace Doctrine\ODM\OrientDB\Tests\Models\CMS;

/**
 * @EmbeddedDocument(oclass="CmsPhonenumber")
 */
class CmsPhonenumber
{
    /**
     * @RID
     * @var string
     */
    public $rid;

    /**
     * @Property(max=50)
     */
    public $phonenumber;
    /**
     * @ManyToOne(targetEntity="CmsUser", inversedBy="phonenumbers", cascade={"merge"})
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    public $user;

    public function setUser(CmsUser $user) {
        $this->user = $user;
    }

    public function getUser() {
        return $this->user;
    }
}
