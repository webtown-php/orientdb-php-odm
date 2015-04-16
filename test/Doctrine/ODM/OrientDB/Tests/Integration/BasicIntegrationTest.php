<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\Tests\Models\CMS\CmsUser;

/**
 * @group integration
 */
class BasicIntegrationTest extends AbstractIntegrationTest
{
    protected function setUp() {
        $this->useModelSet('cms');
        parent::setUp();
    }

    /**
     * @test
     */
    public function persist_single_entity() {
        $dm = $this->dm;

        $u           = new CmsUser();
        $u->username = "sydney";
        $u->name     = "Sydney C";

        $dm->persist($u);
        $dm->flush();
        $dm->clear();

        $rid = $u->rid;
        $this->assertNotEmpty($rid, 'missing RID');

        /** @var CmsUser $u */
        $u = $dm->findByRid($rid);
        $this->assertInstanceOf(CmsUser::class, $u);
        $this->assertEquals('sydney', $u->username);
        $this->assertEquals('Sydney C', $u->name);

        return $rid;
    }

    /**
     * @test
     * @depends persist_single_entity
     *
     * @param $rid
     */
    public function update_single_entity($rid) {
        $dm = $this->dm;

        /** @var CmsUser $u */
        $u = $dm->find(CmsUser::class, $rid);
        $u->name = "Sydney J C";
        $dm->flush();
        $dm->clear();

        /** @var CmsUser $u */
        $u = $dm->findByRid($rid);
        $this->assertEquals('Sydney J C', $u->name);
    }

    /**
     * @test
     * @depends persist_single_entity
     *
     * @param $rid
     */
    public function delete_single_entity($rid) {
        $u = $this->dm->find(CmsUser::class, $rid);
        $this->dm->remove($u);
        $this->dm->flush();

        $u = $this->dm->findByRid($rid);
        $this->assertNull($u);
    }

}