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
    public function sets_RID_and_registers_inserted_document_with_UnitOfWork() {
        $c   = new Contact();
        $oid = spl_object_hash($c);

        $uow = $this->prophesize(UnitOfWork::class);
        $uow->getDocumentInsertions()
            ->willReturn([$oid => $c]);
        $uow->getDocumentChangeSet(Arg::is($c))
            ->willReturn(['name' => ['old', 'new']]);

        $uow->getDocumentUpdates()
            ->willReturn([]);
        $uow->getCollectionUpdates()
            ->willReturn([]);
        $uow->getCollectionDeletions()
            ->willReturn([]);
        $uow->getDocumentDeletions()
            ->willReturn([]);
        $uow->getDocumentActualData(Arg::is($c))
            ->willReturn([]);
        $uow->registerManaged($c, '#1:0', [])
            ->shouldBeCalled();
        $uow->raisePostPersist(Arg::any(), $c)
            ->shouldBeCalled();

        $res = json_decode(<<<JSON
{
    "result":[{
        "n0":"#1:0"
    }]
}
JSON
        );

        $b = $this->prophesize(HttpBindingInterface::class);
        $b->sqlBatch(Arg::any())
          ->willReturn($res);

        $p = new SQLBatchPersister($this->metadataFactory, $b->reveal());
        $p->process($uow->reveal());
    }
}
