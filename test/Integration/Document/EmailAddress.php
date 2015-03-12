<?php

namespace Integration\Document;

/**
 * @EmbeddedDocument(class="EmailAddress")
 */
class EmailAddress
{
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