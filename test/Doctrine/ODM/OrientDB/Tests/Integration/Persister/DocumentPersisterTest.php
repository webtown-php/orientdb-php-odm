<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Persister;


use Doctrine\ODM\OrientDB\Tests\Integration\AbstractIntegrationTest;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\Post;

/**
 * @group integration
 */
class DocumentPersisterTest extends AbstractIntegrationTest
{
    /**
     * @var int
     */
    private $postId;

    private $rid;

    /**
     * @before
     */
    public function before() {
        $this->postId = $this->getClassId('Post');
        $b            = $this->dm->getBinding();
        $this->rid    = $b->command('INSERT INTO Post set id=10, title=20')['result'][0]['@rid'];
    }

    protected function setUp() {
        $this->useModelSet('standard');
        parent::setUp();
    }

    /**
     * @test
     */
    public function exists_returns_true() {
        $dp        = $this->dm->getUnitOfWork()->getDocumentPersister(Post::class);
        $post      = new Post();
        $post->rid = $this->rid;
        $res       = $dp->exists($post);
        $this->assertTrue($res);
    }

    /**
     * @test
     */
    public function load_existing_document() {
        $dp  = $this->dm->getUnitOfWork()->getDocumentPersister(Post::class);
        $rid = $this->rid;
        /** @var Post $res */
        $res = $dp->load($rid);
        $this->assertInstanceOf(Post::class, $res);
        $this->assertEquals($rid, $res->getRid());
    }

    /**
     * @test
     */
    public function exists_returns_false() {
        $dp        = $this->dm->getUnitOfWork()->getDocumentPersister(Post::class);
        $post      = new Post();
        $post->rid = "#{$this->postId}:999999999";
        $res       = $dp->exists($post);
        $this->assertFalse($res);
    }
}
