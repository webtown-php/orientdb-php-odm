<?php

namespace test\Doctrine\ODM\OrientDB\Document\Stub\Linked;

use Doctrine\Common\Collections\Collection;

/**
 * @Document(class="LinkedContact")
 */
class Contact
{
    /**
     * @RID
     */
    public $rid;

    /**
     * Display name
     *
     * @Property(type="string", nullable=false)
     * @var string
     */
    public $name;

    /**
     * @Link(targetClass="EmailAddress", parentProperty="contact", cascade={"persist"})
     * @var EmailAddress
     */
    protected $email;

    /**
     * @LinkList(targetClass="Phone", parentProperty="contact", cascade={"persist"})
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