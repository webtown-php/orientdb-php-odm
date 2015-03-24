<?php

namespace Doctrine\ODM\OrientDB\Tests\Document\Stub\Graph;

/**
 * @Vertex(oclass="PostV")
 */
class Post
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
    public $title;
}