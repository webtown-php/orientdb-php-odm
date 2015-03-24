<?php

namespace Doctrine\ODM\OrientDB\Tests;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Tests\Document\Stub\Graph\Contact;
use Doctrine\ODM\OrientDB\Tests\Document\Stub\Graph\LikedE;
use Doctrine\ODM\OrientDB\Tests\Document\Stub\Graph\Post;
use PHPUnit\TestCase;

/**
 * @group functional
 */
class UnitOfWorkGraphDocumentTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    private $manager;
    /**
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @before
     */
    public function before() {
        $this->manager         = $this->createDocumentManager([], ['Doctrine/ODM/OrientDB/Tests/Document/Stub/Graph']);
        $this->metadataFactory = $this->manager->getMetadataFactory();
    }

    /**
     * @test
     */
    public function computeChangeSet_generates_insert_for_relationship() {
        $uow = $this->manager->getUnitOfWork();

        $c = new Contact();
        $c->rid = "#1:0";
        $c->name = "Sydney";
        $uow->registerManaged($c, $c->rid, $uow->getDocumentActualData($c));

        $p = new Post();
        $p->rid = "#2:0";
        $p->title = "The title";
        $uow->registerManaged($p, $p->rid, $uow->getDocumentActualData($p));

        $e = new LikedE();
        $e->out = $c;
        $e->in  = $p;

        $c->liked->add($e);

        $uow->computeChangeSets();
        $this->assertTrue($uow->isScheduledForInsert($e));
    }
}
