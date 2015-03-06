<?php

namespace test\Doctrine\ODM\OrientDB;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use test\Doctrine\ODM\OrientDB\Document\Stub\Embedded\Contact;
use test\Doctrine\ODM\OrientDB\Document\Stub\Embedded\EmailAddress;
use test\PHPUnit\TestCase;

/**
 * @group functional
 */
class UnitOfWorkChangesetWithEmbeddedDocumentTest extends TestCase
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
        $this->manager         = $this->createDocumentManager([], ['test/Doctrine/ODM/OrientDB/Document/Stub/Embedded']);
        $this->metadataFactory = $this->manager->getMetadataFactory();
    }

    /**
     * @test
     */
    public function getDocumentChangeSet_includes_embedded_for_new() {
        $uow = $this->manager->getUnitOfWork();
        $c   = new Contact();
        $this->manager->persist($c);

        $c->name = "Sydney";

        $em        = new EmailAddress();
        $em->type  = "work";
        $em->email = "syd@gmail.com";
        $c->email  = $em;

        $md = $this->manager->getClassMetadata(Contact::class);
        $uow->computeChangeSet($md, $c);
        $cs = $uow->getDocumentChangeSet($c);
        $this->assertEquals(['rid', 'name', 'email', 'phones'], array_keys($cs));
    }

    /**
     * @test
     */
    public function getDocumentChangeSet_update_value_in_embedded() {
        $uow     = $this->manager->getUnitOfWork();
        $c       = new Contact();
        $c->name = "Sydney";
        $c->rid  = "#1:1";

        $e        = new EmailAddress();
        $e->type  = "work";
        $e->email = "syd@gmail.com";
        $c->email = $e;

        $uow->registerManaged($c, "#1:1", ['rid' => '#1:1', 'name' => 'Sydney']);
        $uow->registerManaged($e, null, ['type' => 'home', 'email' => 'syd@gmail.com']);

        $md = $this->manager->getClassMetadata(Contact::class);
        $uow->computeChangeSet($md, $c);
        $cs = $uow->getDocumentChangeSet($c);
        $this->assertEquals(['email'], array_keys($cs));

        $cs = $uow->getDocumentChangeSet($c->email);
        $this->assertEquals(['type'], array_keys($cs));
        $this->assertEquals(['home', 'work'], $cs['type']);
    }
}
