<?php

namespace Integration\Document;

/**
 * @Document(class="EmailAddressLink")
 */
class EmailAddressLink
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