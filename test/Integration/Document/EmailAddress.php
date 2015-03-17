<?php

namespace Integration\Document;

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