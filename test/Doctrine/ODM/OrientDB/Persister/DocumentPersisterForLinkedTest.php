<?php

namespace test\Doctrine\ODM\OrientDB\Persister;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use test\Doctrine\ODM\OrientDB\Document\Stub\Linked\Contact;
use test\Doctrine\ODM\OrientDB\Document\Stub\Linked\EmailAddress;
use test\PHPUnit\TestCase;

class DocumentPersisterForLinkedTest extends TestCase
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
        $this->manager         = $this->createDocumentManager([], ['test/Doctrine/ODM/OrientDB/Document/Stub/Linked']);
        $this->metadataFactory = $this->manager->getMetadataFactory();
    }

    /**
     * @test
     */
    public function first() {
        $uow = $this->manager->getUnitOfWork();
        $dp = $uow->getDocumentPersister(Contact::class);

        $c = new Contact();
        $c->name = "Sydney";

        $em        = new EmailAddress();
        $em->type  = "work";
        $em->email = "syd@gmail.com";
        $c->setEmail($em);
        $em->contact = $c;
        $this->manager->persist($c);

        $uow->computeChangeSets();

        $data = $dp->prepareInsertData($c);
    }
}
