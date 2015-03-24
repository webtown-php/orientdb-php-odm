<?php

namespace Doctrine\ODM\OrientDB\Tests;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Tests\Document\Stub\Simple\Contact;
use PHPUnit\TestCase;

/**
 * @group functional
 */
class UnitOfWorkSimpleDocumentTest extends TestCase
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
        $this->manager         = $this->createDocumentManager([], ['Doctrine/ODM/OrientDB/Tests/Document/Stub/Simple']);
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
    public function getDocumentChangeSet_includes_expected_changes_for_new() {
        $uow = $this->manager->getUnitOfWork();
        $c   = new Contact();
        $this->manager->persist($c);

        $md      = $this->manager->getClassMetadata(Contact::class);
        $c->name = "Sydney";
        $uow->computeChangeSet($md, $c);
        $cs = $uow->getDocumentChangeSet($c);

        $this->assertEquals(['name', 'height', 'birthday', 'active'], array_keys($cs));
        $this->assertEquals([null, 'Sydney'], $cs['name']);
        $this->assertEquals([null, null], $cs['height']);
    }

    /**
     * @test
     */
    public function getDocumentChangeSet_includes_only_changes_for_update() {
        $uow         = $this->manager->getUnitOfWork();
        $c           = new Contact();
        $c->rid      = "#1:1";
        $c->name     = "Sydney";
        $c->height   = 5;
        $c->birthday = null;
        $uow->registerManaged($c, "#1:1", ['rid' => '#1:1', 'name' => 'Sydney', 'height' => null, 'birthday' => null]);

        $md = $this->manager->getClassMetadata(Contact::class);
        $uow->computeChangeSet($md, $c);
        $cs = $uow->getDocumentChangeSet($c);
        $this->assertEquals(['height'], array_keys($cs));
        $this->assertEquals([null, 5], $cs['height']);
    }
}
