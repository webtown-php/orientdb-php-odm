<?php

namespace Doctrine\ODM\OrientDB\Tests\Persister;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Persister\SQLBatch\SQLBatchPersister;
use Doctrine\ODM\OrientDB\Tests\Document\Stub\Simple\Contact;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Binding\BindingResultInterface;
use Doctrine\OrientDB\Binding\HttpBindingInterface;
use PHPUnit\TestCase;
use Prophecy\Argument as Arg;

/**
 * functional
 */
class SQLBatchPersisterWithSimpleDocumentTest extends TestCase
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
        $this->manager         = $this->createDocumentManager([], ['test/Doctrine/ODM/OrientDB/Tests/Document/Stub/Simple']);
        $this->metadataFactory = $this->manager->getMetadataFactory();
    }

    /**
     * @test
     * @expectedException \Doctrine\ODM\OrientDB\Persister\SQLBatch\SQLBatchException
     * @expectedExceptionMessage unexpected response from server when executing batch request
     */
    public function exception_for_insert_when_server_returns_invalid_response() {
        $c   = new Contact();
        $oid = spl_object_hash($c);

        $uow = $this->prophesize(UnitOfWork::class);
        $uow->getDocumentInsertions()
            ->willReturn([$oid => $c]);
        $uow->getDocumentChangeSet(Arg::is($c))
            ->willReturn(['name' => [null, 'new']]);

        $uow->getDocumentUpdates()
            ->willReturn([]);
        $uow->getCollectionUpdates()
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

    /**
     * @test
     * @expectedException \Doctrine\ODM\OrientDB\Persister\SQLBatch\SQLBatchException
     * @expectedExceptionMessage missing RIDs for one or more inserted documents
     */
    public function exception_for_insert_when_server_returns_missing_RIDs() {
        $c   = new Contact();
        $oid = spl_object_hash($c);

        $uow = $this->prophesize(UnitOfWork::class);
        $uow->getDocumentInsertions()
            ->willReturn([$oid => $c]);
        $uow->getDocumentChangeSet(Arg::is($c))
            ->willReturn(['name' => [null, 'new']]);

        $uow->getDocumentUpdates()
            ->willReturn([]);
        $uow->getCollectionUpdates()
            ->willReturn([]);
        $uow->getDocumentDeletions()
            ->willReturn([]);

        $res = json_decode(<<<JSON
{
    "result":[{
        "n1": "#1:0"
    }]
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
            ->willReturn(['name' => [null, 'new']]);

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
        "n0": "#1:0"
    }]
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

        $this->assertEquals('#1:0', $c->rid);
    }
}
