<?php

namespace test\Doctrine\ODM\OrientDB\Integration;


use Doctrine\ODM\OrientDB\DocumentManager;
use Integration\Document\EmailAddress;
use Integration\Document\Person;
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

    #region single document persistence

    /**
     * @test
     * @return mixed
     */
    public function persist_single_document() {
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
     * @test
     * @depends persist_single_document
     *
     * @param $rid
     */
    public function update_single_document($rid) {
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
     * @test
     * @depends update_single_document
     *
     * @param $rid
     */
    public function delete_single_document($rid) {
        $document = $this->manager->findByRid($rid);
        $this->manager->remove($document);
        $this->manager->flush();
        unset($document);
        $this->manager->clear();

        $this->assertNull($this->manager->findByRid($rid));
    }

    #endregion

    #region embedded persistence tests

    /**
     * @test
     */
    public function persist_with_embedded() {
        $d       = new Person();
        $d->name = "Sydney";

        $e = new EmailAddress();
        $e->type = 'work';
        $e->email = 'syd@gmail.com';

        $d->email = $e;

        $this->manager->persist($d);
        $this->manager->flush();
        $this->manager->clear();
        $this->assertNotNull($d->rid);

        /** @var Person $d */
        $d = $this->manager->findByRid($d->rid);
        $this->assertEquals('Sydney', $d->name);
        $this->assertNotNull($d->email);
        $this->assertEquals('work', $d->email->type);
        $this->assertEquals('syd@gmail.com', $d->email->email);

        return $d->rid;
    }

    /**
     * @test
     * @depends persist_with_embedded
     */
    public function update_top_level_and_embedded_document($rid) {
        /** @var Person $d */
        $d       = $this->manager->findByRid($rid);
        $d->name = 'Cameron';
        $d->email->email = 'cam@gmail.com';

        unset($d);
        $this->manager->flush();
        $this->manager->clear();

        $d = $this->manager->findByRid($rid);
        $this->assertEquals('Cameron', $d->name);
        $this->assertNotNull($d->email);
        $this->assertEquals('cam@gmail.com', $d->email->email);

        return $rid;
    }

    /**
     * @test
     * @depends update_top_level_and_embedded_document
     */
    public function update_embedded_document_to_null($rid) {
        /** @var Person $d */
        $d       = $this->manager->findByRid($rid);
        $d->email = null;

        unset($d);
        $this->manager->flush();
        $this->manager->clear();

        $d = $this->manager->findByRid($rid);
        $this->assertEquals('Cameron', $d->name);
        $this->assertNull($d->email);
    }

    /**
     * @test
     * @depends persist_with_embedded
     *
     * @param $rid
     */
    public function delete_document_with_embedded($rid) {
        $document = $this->manager->findByRid($rid);
        $this->manager->remove($document);
        $this->manager->flush();
        unset($document);
        $this->manager->clear();

        $this->assertNull($this->manager->findByRid($rid));
    }

    #endregion

    #region embedded list persistence test

    /**
     * @test
     */
    public function persist_with_embedded_list_document() {
        $d       = new Person();
        $d->name = "Sydney";

        $e1 = new EmailAddress();
        $e1->type = 'work';
        $e1->email = 'syd-work@gmail.com';

        $e2 = new EmailAddress();
        $e2->type = 'home';
        $e2->email = 'syd-home@gmail.com';

        $d->emails = [$e1, $e2];

        $this->manager->persist($d);
        $this->manager->flush();
        $this->manager->clear();
        $this->assertNotNull($d->rid);

        /** @var Person $proxy */
        $proxy = $this->manager->findByRid($d->rid);
        $this->assertEquals('Sydney', $proxy->name);
        $this->assertCount(2, $d->emails);

        return $d->rid;
    }

    /**
     * @test
     * @depends persist_with_embedded_list_document
     */
    public function update_with_embedded_list_document($rid) {
        /** @var Person $d */
        $d       = $this->manager->findByRid($rid);
        $d->emails[0]->email = 'cam@gmail.com';

        unset($d);
        $this->manager->flush();
        $this->manager->clear();

        $d = $this->manager->findByRid($rid);
        $this->assertEquals('Sydney', $d->name);
        $this->assertCount(2, $d->emails);
        $this->assertEquals('cam@gmail.com', $d->emails[0]->email);

        return $rid;
    }

    /**
     * @test
     * @depends persist_with_embedded_list_document
     */
    public function remove_item_from_embedded_list_document($rid) {
        /** @var Person $d */
        $d       = $this->manager->findByRid($rid);
        unset($d->emails[0]);

        unset($d);
        $this->manager->flush();
        $this->manager->clear();

        $d = $this->manager->findByRid($rid);
        $this->assertEquals('Sydney', $d->name);
        $this->assertCount(1, $d->emails);
        $this->assertEquals('syd-home@gmail.com', $d->emails[0]->email);

        return $rid;
    }

    /**
     * @test
     * @depends persist_with_embedded_list_document
     *
     * @param $rid
     */
    public function delete_with_embedded_list_document($rid) {
        $document = $this->manager->findByRid($rid);
        $this->manager->remove($document);
        $this->manager->flush();
        unset($document);
        $this->manager->clear();

        $this->assertNull($this->manager->findByRid($rid));
    }

    #endregion
} 