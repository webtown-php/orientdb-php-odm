<?php

namespace Integration\Document;

/**
 * @EmbeddedDocument(oclass="Phone")
 */
class Phone
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