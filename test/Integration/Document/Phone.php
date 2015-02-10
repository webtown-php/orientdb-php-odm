<?php

namespace test\Integration\Document;

/**
 * @EmbeddedDocument(class="Phone")
 */
class Phone
{
    /**
     * @Property(type="string")
     * @var string
     */
    public $phone;
}