<?php

namespace Doctrine\ODM\OrientDB;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Proxy\ProxyFactory;
use Doctrine\ODM\OrientDB\Types\Rid;
use test\Doctrine\ODM\OrientDB\Document\Stub\Contact\Address;
use test\PHPUnit\TestCase;

/**
 * @group functional
 */
class UnitOfWorkTest extends TestCase
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
        $this->manager = $this->createDocumentManager([], ['test/Doctrine/ODM/OrientDB/Document/Stub']);
        $this->metadataFactory = $this->manager->getMetadataFactory();
    }

    /**
     * @test
     */
    public function can_do_it() {
        $uow = $this->manager->getUnitOfWork();

        $adr = new Address();
        $orig = [];
        $cs = $uow->buildChangeSet($adr, $orig);
    }
}
