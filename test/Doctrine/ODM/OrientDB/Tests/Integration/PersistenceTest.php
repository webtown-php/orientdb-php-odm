<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\Tests\Models\Standard\Country;

/**
 * @group integration
 */
class PersistenceTest extends AbstractIntegrationTest
{
    protected function setUp() {
        $this->useModelSet('standard');
        parent::setUp();
    }

    #region single document persistence

    /**
     * @test
     * @return mixed
     */
    public function persist_single_document() {
        $d       = new Country();
        $d->name = 'SinglePersistTest';

        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();
        $this->assertNotNull($d->getRid());

        /** @var Country $d */
        $d = $this->dm->findByRid($d->getRid());
        $this->assertEquals('SinglePersistTest', $d->name);

        return $d->getRid();
    }

    /**
     * @test
     * @depends persist_single_document
     *
     * @param $rid
     */
    public function update_single_document($rid) {
        $d       = $this->dm->findByRid($rid);
        $d->name = "'updated'";

        unset($d);
        $this->dm->flush();
        $this->dm->clear();

        /** @var Country $d */
        $d = $this->dm->findByRid($rid);
        $this->assertEquals("'updated'", $d->name);

        return $rid;
    }

    /**
     * @test
     * @depends persist_single_document
     *
     * @param $rid
     *
     * @expectedException \Doctrine\ODM\OrientDB\LockException
     */
    public function LockException_for_update_if_version_mismatch($rid) {
        $d          = $this->dm->findByRid($rid);
        $d->name    = 'FailedUpdateTest';
        $d->version = 100;

        unset($d);
        $this->dm->flush();
    }

    /**
     * @test
     * @depends persist_single_document
     *
     * @param $rid
     */
    public function delete_single_document($rid) {
        $d = $this->dm->findByRid($rid);
        $this->dm->remove($d);
        $this->dm->flush();
        unset($d);
        $this->dm->clear();

        $this->assertNull($this->dm->findByRid($rid));
    }

    /**
     * @depends      delete_single_document
     * @test
     * @dataProvider string_data
     */
    public function persist_string_value($value) {
        $d       = new Country();
        $d->name = $value;

        $this->dm->persist($d);
        $this->dm->flush();
        $this->dm->clear();
        $this->assertNotNull($d->getRid());

        /** @var Country $d */
        $d = $this->dm->findByRid($d->getRid());
        $this->assertEquals($value, $d->name);
        $this->dm->remove($d);
        $this->dm->flush();
    }

    public function string_data() {
        return [
            'basic string'       => ['basic string'],
            'with double quote'  => ['"double quotes"'],
            'with single quote'  => ["'single quotes'"],
            'with newline quote' => ["one\ntwo"],
            'null'               => [null],
        ];
    }

    #endregion


} 