<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration;


use Doctrine\ODM\OrientDB\Tests\Models\Embedded\EmailAddress;
use Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person;
use Doctrine\ODM\OrientDB\Tests\Models\Embedded\Phone;

/**
 * @group integration
 */
class EmbeddedPersistenceTest extends AbstractIntegrationTest
{
    protected function setUp() {
        $this->useModelSet('embedded');
        parent::setUp();
    }

    #region embedded persistence tests

    /**
     * @test
     */
    public function persist_with_embedded() {
        $d       = new Person();
        $d->name = "Sydney";

        $e        = new EmailAddress();
        $e->type  = 'work';
        $e->email = 'syd@gmail.com';

        $d->email = $e;

        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();
        $this->assertNotNull($d->rid);

        /** @var Person $d */
        $d = $this->dm->findByRid($d->rid);
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
        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person $d */
        $d               = $this->dm->findByRid($rid);
        $d->name         = 'Cameron';
        $d->email->email = 'cam@gmail.com';

        unset($d);
        $this->dm->flush();
        $this->dm->clear();

        $d = $this->dm->findByRid($rid);
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
        $d        = $this->dm->findByRid($rid);
        $d->email = null;

        unset($d);
        $this->dm->flush();
        $this->dm->clear();

        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person $d */
        $d = $this->dm->findByRid($rid);
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
        $document = $this->dm->findByRid($rid);
        $this->dm->remove($document);
        $this->dm->flush();
        unset($document);
        $this->dm->clear();

        $this->assertNull($this->dm->findByRid($rid));
    }

    #endregion

    #region embedded list persistence test

    /**
     * @test
     */
    public function persist_with_embedded_list_document() {
        $d       = new Person();
        $d->name = "Sydney";

        $e1        = new EmailAddress();
        $e1->type  = 'work';
        $e1->email = 'syd-work@gmail.com';

        $e2        = new EmailAddress();
        $e2->type  = 'home';
        $e2->email = 'syd-home@gmail.com';

        $d->emails = [$e1, $e2];

        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();
        $this->assertNotNull($d->rid);

        /** @var Person $d */
        $d = $this->dm->findByRid($d->rid);
        $this->assertEquals('Sydney', $d->name);
        $this->assertCount(2, $d->emails);

        return $d->rid;
    }

    /**
     * @test
     * @depends persist_with_embedded_list_document
     */
    public function update_with_embedded_list_document($rid) {
        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person $d */
        $d                   = $this->dm->findByRid($rid);
        $d->emails[0]->email = 'cam@gmail.com';

        unset($d);
        $this->dm->flush();
        $this->dm->clear();

        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person $d */
        $d = $this->dm->findByRid($rid);
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
        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person $d */
        $d = $this->dm->findByRid($rid);
        unset($d->emails[0]);

        unset($d);
        $this->dm->flush();
        $this->dm->clear();

        /** @var Person $d */
        $d = $this->dm->findByRid($rid);
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
        $d = $this->dm->findByRid($rid);
        $this->dm->remove($d);
        $this->dm->flush();
        unset($d);
        $this->dm->clear();

        $this->assertNull($this->dm->findByRid($rid));
    }

    #endregion

    #region embedded map persistence test

    /**
     * @test
     */
    public function persist_with_embedded_map_document() {
        $d       = new Person();
        $d->name = "Sydney";

        $e1        = new Phone();
        $e1->phone = '4804441999';

        $e2        = new Phone();
        $e2->phone = '5554443333';

        $d->phones = [
            'home' => $e1,
            'work' => $e2,
        ];

        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();
        $this->assertNotNull($d->rid);

        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person $d */
        $d = $this->dm->findByRid($d->rid);
        $this->assertEquals('Sydney', $d->name);
        $this->assertCount(2, $d->phones);
        $actual = $d->phones->getKeys();
        sort($actual);
        $this->assertEquals(['home', 'work'], $actual);

        return $d->rid;
    }

    /**
     * @test
     * @depends persist_with_embedded_map_document
     */
    public function update_existing_item_in_embedded_map_document($rid) {
        /** @var Person $d */
        $d                        = $this->dm->findByRid($rid);
        $d->phones['home']->phone = '4804442000';

        unset($d);
        $this->dm->flush();
        $this->dm->clear();

        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person $d */
        $d = $this->dm->findByRid($rid);
        $this->assertEquals('Sydney', $d->name);
        $this->assertArrayHasKey('home', $d->phones);
        $this->assertEquals('4804442000', $d->phones['home']->phone);
    }

    /**
     * @test
     * @depends persist_with_embedded_map_document
     */
    public function update_add_new_key_to_existing_embedded_map_document($rid) {
        /** @var Person $d */
        $d                   = $this->dm->findByRid($rid);
        $p                   = new Phone();
        $p->phone            = '4801112222';
        $d->phones['mobile'] = $p;

        unset($d);
        $this->dm->flush();
        $this->dm->clear();

        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person $d */
        $d = $this->dm->findByRid($rid);
        $this->assertEquals('Sydney', $d->name);
        $this->assertCount(3, $d->phones);
        $this->assertArrayHasKey('mobile', $d->phones);
        $this->assertEquals('4801112222', $d->phones['mobile']->phone);

        return $rid;
    }

    /**
     * @test
     * @depends update_add_new_key_to_existing_embedded_map_document
     */
    public function update_remove_existing_key_from_existing_embedded_map_document($rid) {
        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person $d */
        $d = $this->dm->findByRid($rid);
        unset($d->phones['mobile']);
        unset($d);
        $this->dm->flush();
        $this->dm->clear();

        /** @var Person $d */
        $d = $this->dm->findByRid($rid);
        $this->assertEquals('Sydney', $d->name);
        $this->assertCount(2, $d->phones);
        $this->assertArrayNotHasKey('mobile', $d->phones);
    }

    /**
     * @test
     * @depends persist_with_embedded_map_document
     *
     * @param $rid
     */
    public function delete_with_embedded_map_document($rid) {
        $d = $this->dm->findByRid($rid);
        $this->dm->remove($d);
        $this->dm->flush();
        unset($d);
        $this->dm->clear();

        $this->assertNull($this->dm->findByRid($rid));
    }

    #endregion
}