<?php

namespace test\Doctrine\ODM\OrientDB\Integration;


use Doctrine\ODM\OrientDB\DocumentManager;
use test\Integration\Document\Country;
use test\PHPUnit\TestCase;

/**
 * @group integration
 */
class PersistenceTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    protected $manager;

    protected function setUp() {
        $this->manager = $this->createDocumentManager();
    }

    public function testPersistDocument() {
        $document       = new Country();
        $document->name = 'SinglePersistTest';

        $this->manager->persist($document);
        $this->manager->flush();
        $this->manager->clear();
        $this->assertNotNull($document->getRid());

        $proxy = $this->manager->findByRid($document->getRid());
        $this->assertEquals('SinglePersistTest', $proxy->name);

        return $document->getRid();
    }

    /**
     * @depends testPersistDocument
     *
     * @param $rid
     */
    public function testUpdateDocument($rid) {
        $document       = $this->manager->findByRid($rid);
        $document->name = 'SingleUpdateTest';

        unset($document);
        $this->manager->flush();
        $this->manager->clear();

        $proxy = $this->manager->findByRid($rid);
        $this->assertEquals('SingleUpdateTest', $proxy->name);

        return $rid;
    }

    /**
     * @depends testUpdateDocument
     *
     * @param $rid
     */
    public function testDeleteDocument($rid) {
        $document = $this->manager->findByRid($rid);
        $this->manager->remove($document);
        $this->manager->flush();
        unset($document);
        $this->manager->clear();

        $this->assertNull($this->manager->findByRid($rid));
    }
} 