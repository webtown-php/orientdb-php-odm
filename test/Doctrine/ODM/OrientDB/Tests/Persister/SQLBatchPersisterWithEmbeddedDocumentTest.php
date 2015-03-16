<?php

namespace Doctrine\ODM\OrientDB\Tests\Persister;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Persister\SQLBatch\SQLBatchPersister;
use Doctrine\ODM\OrientDB\Tests\Document\Stub\Embedded\Contact;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Binding\BindingResultInterface;
use Doctrine\OrientDB\Binding\HttpBindingInterface;
use PHPUnit\TestCase;
use Prophecy\Argument as Arg;

/**
 * functional
 */
class SQLBatchPersisterWithEmbeddedDocumentTest extends TestCase
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
        $this->manager         = $this->createDocumentManager([], ['test/Doctrine/ODM/OrientDB/Tests/Document/Stub/Embedded']);
        $this->metadataFactory = $this->manager->getMetadataFactory();
    }

    /**
     * @test
     */
    public function first() {
        $c   = new Contact();
        $oid = spl_object_hash($c);

        $uow = $this->prophesize(UnitOfWork::class);
        $uow->getDocumentInsertions()
            ->willReturn([$oid => $c]);
        $uow->getDocumentChangeSet(Arg::is($c))
            ->willReturn(['name' => ['old', 'new']]);

        $uow->getDocumentUpdates()
            ->willReturn([]);
        $uow->getDocumentDeletions()
            ->willReturn([]);

        $res = json_decode(<<<JSON
{
    "result":[]
}
JSON
        );

        $ri = $this->prophesize(BindingResultInterface::class);
        $ri->getData()
           ->willReturn($res);
        $b = $this->prophesize(HttpBindingInterface::class);
        $b->batch(Arg::any())
          ->willReturn($ri->reveal());

        $p = new SQLBatchPersister($this->metadataFactory, $b->reveal());
        $p->process($uow->reveal());
    }
}
