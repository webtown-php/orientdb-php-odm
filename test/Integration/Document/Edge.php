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
     * @Link
     * @var
     */
    public $in;

    /**
     * @Link
     * @var
     */
    public $out;
}