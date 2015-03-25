<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Persister;


use Integration\Document\Post;
use PHPUnit\TestCase;

/**
 * @group integration
 */
class DocumentPersisterTest extends TestCase
{
    /**
     * @var int
     */
    private $postId;

    /**
     * @before
     */
    public function before() {
        $this->postId = $this->getClassId('Post');
    }

    /**
     * @test
     */
    public function exists_returns_true() {
        $dm        = $this->createDocumentManager();
        $dp        = $dm->getUnitOfWork()->getDocumentPersister(Post::class);
        $post      = new Post();
        $post->rid = "#{$this->postId}:0";
        $res       = $dp->exists($post);
        $this->assertTrue($res);
    }

    /**
     * @test
     */
    public function load_existing_document() {
        $dm  = $this->createDocumentManager();
        $dp  = $dm->getUnitOfWork()->getDocumentPersister(Post::class);
        $rid = "#{$this->postId}:0";
        /** @var Post $res */
        $res = $dp->load($rid);
        $this->assertInstanceOf(Post::class, $res);
        $this->assertEquals($rid, $res->getRid());
    }

    /**
     * @test
     */
    public function exists_returns_false() {
        $dm        = $this->createDocumentManager();
        $dp        = $dm->getUnitOfWork()->getDocumentPersister(Post::class);
        $post      = new Post();
        $post->rid = "#{$this->postId}:999999999";
        $res       = $dp->exists($post);
        $this->assertFalse($res);
    }
}
