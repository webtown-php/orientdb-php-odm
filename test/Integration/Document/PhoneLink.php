<?php

namespace test\Integration\Document;

/**
 * @Document(class="PhoneLink")
 */
class PhoneLink
{
    /**
     * @RID
     * @var string
     */
    public $rid;

    /**
     * @Property(type="string")
     * @var string
     */
    public $phone;
}