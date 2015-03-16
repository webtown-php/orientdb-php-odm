<?php

namespace test\Integration\Document;

/**
 * @EmbeddedDocument(class="EmailAddress")
 */
class EmailAddress
{
    /**
     * @RID
     * @var string
     */
    public $rid;

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