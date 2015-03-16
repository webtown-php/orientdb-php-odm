<?php

namespace test\Integration\Document;

/**
 * @EmbeddedDocument(class="Phone")
 */
class Phone
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