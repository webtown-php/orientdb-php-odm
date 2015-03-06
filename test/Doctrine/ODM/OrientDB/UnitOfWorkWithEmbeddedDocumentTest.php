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
class UnitOfWorkWithEmbeddedDocumentTest extends TestCase
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
    public function getDocumentChangeSet_includes_embedded() {
        $uow = $this->manager->getUnitOfWork();
        $c   = new Contact();
        $this->manager->persist($c);

        $md      = $this->manager->getClassMetadata(Contact::class);
        $c->name = "Sydney";

        $em = new EmailAddress();
        $em->type = "work";
        $em->email = "syd@gmail.com";
        $c->email = $em;

        $uow->computeChangeSet($md, $c);
        $cs = $uow->getDocumentChangeSet($c);

    }
}
