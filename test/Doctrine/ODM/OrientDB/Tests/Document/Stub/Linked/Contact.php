<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Linked;

use Doctrine\Common\Collections\Collection;

/**
 * @Document(oclass="LinkedContact")
 */
class Contact
{
    /**
     * @RID
     */
    public $rid;

    /**
     * @Version
     */
    public $version;

    /**
     * Display name
     *
     * @Property(type="string", nullable=false)
     * @var string
     */
    public $name;

    /**
     * @Link(targetDoc="EmailAddress", parentProperty="contact", cascade={"persist"})
     * @var EmailAddress
     */
    protected $email;

    /**
     * @LinkList(targetDoc="Phone", parentProperty="contact", cascade={"persist"})
     * @var Phone[]|Collection
     */
    protected $phones;

    /**
     * @return EmailAddress
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param EmailAddress $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return Collection|Phone[]
     */
    public function getPhones() {
        return $this->phones;
    }

    /**
     * @param Collection|Phone[] $phones
     */
    public function setPhones($phones) {
        $this->phones = $phones;
    }


}