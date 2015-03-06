<?php

namespace test\Doctrine\ODM\OrientDB;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use test\Doctrine\ODM\OrientDB\Document\Stub\Simple\Contact;
use test\PHPUnit\TestCase;

/**
 * @group functional
 */
class UnitOfWorkWithSimpleContactTest extends TestCase
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
        $this->manager         = $this->createDocumentManager([], ['test/Doctrine/ODM/OrientDB/Document/Stub/Simple']);
        $this->metadataFactory = $this->manager->getMetadataFactory();
    }

    /**
     * @test
     */
    public function persist_for_new_is_scheduled_for_insert() {
        $uow = $this->manager->getUnitOfWork();
        $c   = new Contact();
        $this->manager->persist($c);
        $this->assertTrue($uow->isScheduledForInsert($c));
    }

    /**
     * @test
     * @depends persist_for_new_is_scheduled_for_insert
     */
    public function getDocumentChangeSet_returns_expected_changes_for_new() {
        $uow = $this->manager->getUnitOfWork();
        $c   = new Contact();
        $this->manager->persist($c);

        $md      = $this->manager->getClassMetadata(Contact::class);
        $c->name = "Sydney";
        $uow->computeChangeSet($md, $c);
        $cs = $uow->getDocumentChangeSet($c);

        $this->assertEquals(['rid', 'name', 'height', 'birthday'], array_keys($cs));
        $this->assertEquals([null, 'Sydney'], $cs['name']);
        $this->assertEquals([null, null], $cs['height']);
    }

    /**
     * @test
     */
    public function getDocumentChangeSet_returns_only_changes_for_update() {
        $uow = $this->manager->getUnitOfWork();
        $adr = new Contact();
        $this->manager->persist($adr);

        $md            = $this->manager->getClassMetadata(Contact::class);
        $adr->rid      = "#1:1";
        $adr->name     = "Sydney";
        $adr->height   = 5;
        $adr->birthday = null;
        $uow->setOriginalDocumentData($adr, ['rid' => '#1:1', 'name' => 'Sydney', 'height' => null, 'birthday' => null]);
        $uow->computeChangeSet($md, $adr);
        $cs = $uow->getDocumentChangeSet($adr);
        $this->assertEquals(['height'], array_keys($cs));
        $this->assertEquals([null, 5], $cs['height']);
    }
}
