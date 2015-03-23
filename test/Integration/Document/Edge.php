<?php

namespace Integration\Document;

/**
 * @Relationship(oclass="E")
 */
class Edge
{
    /**
     * @RID
     * @var string
     */
    public $rid;

    /**
     * @In
     * @var object
     */
    public $in;

    /**
     * @Out
     * @var object
     */
    public $out;
}