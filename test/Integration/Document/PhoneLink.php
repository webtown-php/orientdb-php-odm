<?php

namespace Integration\Document;

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