<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\Tests\Models\Graph\PersonV;
use Doctrine\ODM\OrientDB\Tests\Models\Graph\PostV;

/**
 * Tests
 * @group integration
 */
class PersistenceWithRelatedToTest extends AbstractIntegrationTest
{
    protected function setUp() {
        $this->useModelSet('graph');
        parent::setUp();
    }

    /**
     * @test
     * @return string[]
     */
    public function persist_vertices() {
        $j       = new PersonV();
        $j->name = "Jennifer";
        $this->dm->persist($j);

        $s       = new PersonV();
        $s->name = "Sydney";
        $this->dm->persist($s);

        $c       = new PersonV();
        $c->name = "Cameron";
        $this->dm->persist($c);

        $p        = new PostV();
        $p->title = "The Title";
        $this->dm->persist($p);

        $this->dm->flush();
        $this->dm->clear();

        $this->assertInstanceOf(PersonV::class, $this->dm->findByRid($j->rid));
        $this->assertInstanceOf(PersonV::class, $this->dm->findByRid($s->rid));
        $this->assertInstanceOf(PersonV::class, $this->dm->findByRid($c->rid));
        $this->assertInstanceOf(PostV::class, $this->dm->findByRid($p->rid));

        return [$j->rid, $s->rid, $c->rid, $p->rid];
    }

    /**
     * @depends persist_vertices
     * @test
     *
     * @param string[] $rids
     *
     * @return \string[]
     */
    public function add_follows_and_also_included_in_related_followers($rids) {
        /** @var PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->dm->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->dm->findByRid($rids[2]);

        $j->follows->add($s);
        $j->follows->add($c);
        $this->dm->flush();
        $this->dm->clear();

        /** @var PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->dm->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->dm->findByRid($rids[2]);

        $this->assertCount(2, $j->follows);
        $this->assertContains($s, $j->follows);
        $this->assertContains($c, $j->follows);

        $this->assertCount(1, $s->followers);
        $this->assertContains($j, $s->followers);

        $this->assertCount(1, $c->followers);
        $this->assertContains($j, $c->followers);

        return $rids;
    }

    /**
     * @depends add_follows_and_also_included_in_related_followers
     * @test
     *
     * @param string[] $rids
     */
    public function remove_follows($rids) {
        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Graph\PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var PersonV $s */
        $c = $this->dm->findByRid($rids[2]);

        $j->follows->removeElement($c);
        $this->dm->flush();
        $this->dm->clear();

        /** @var PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->dm->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->dm->findByRid($rids[2]);

        $this->assertCount(1, $j->follows);
        $this->assertContains($s, $j->follows);

        $this->assertCount(1, $s->followers);
        $this->assertContains($j, $s->followers);

        $this->assertCount(0, $c->followers);
    }

    /**
     * @depends persist_vertices
     * @test
     *
     * @param string[] $rids
     *
     * @return \string[]
     */
    public function add_followers($rids) {
        /** @var PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->dm->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->dm->findByRid($rids[2]);

        $j->followers->add($s);
        $j->followers->add($c);
        $this->dm->flush();
        $this->dm->clear();

        /** @var PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->dm->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->dm->findByRid($rids[2]);

        $this->assertCount(2, $j->followers);
        $this->assertContains($s, $j->followers);
        $this->assertContains($c, $j->followers);

        $this->assertCount(1, $s->follows);
        $this->assertContains($j, $s->follows);

        $this->assertCount(1, $c->follows);
        $this->assertContains($j, $c->follows);

        return $rids;
    }

    /**
     * @depends add_followers
     * @test
     *
     * @param string[] $rids
     *
     * @return \string[]
     */
    public function clear_does_remove_all_followers($rids) {
        /** @var PersonV $j */
        $j = $this->dm->findByRid($rids[0]);

        $j->followers->clear();
        $this->dm->flush();
        $this->dm->clear();

        /** @var PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->dm->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->dm->findByRid($rids[2]);

        $this->assertCount(0, $j->followers);

        $this->assertCount(0, $s->follows);

        $this->assertCount(0, $c->follows);

        return $rids;
    }

    /**
     * @depends persist_vertices
     * @test
     *
     * @param string[] $rids
     */
    public function add_likes_to_PersonV_and_PostV($rids) {
        /** @var PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->dm->findByRid($rids[1]);
        /** @var PostV $s */
        $p = $this->dm->findByRid($rids[3]);

        $j->likes->add($s);
        $j->likes->add($p);
        $this->dm->flush();
        $this->dm->clear();

        /** @var PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->dm->findByRid($rids[1]);
        /** @var PostV $s */
        $p = $this->dm->findByRid($rids[3]);

        $this->assertCount(2, $j->likes);
        $this->assertContains($s, $j->likes);
        $this->assertContains($p, $j->likes);
    }

    /**
     * @depends persist_vertices
     * @test
     *
     * @param $rids
     */
    public function delete_vertices($rids) {
        foreach ($rids as $rid) {
            $v = $this->dm->findByRid($rid);
            $this->dm->remove($v);
        }

        $this->dm->flush();
        $this->dm->clear();

        foreach ($rids as $rid) {
            $v = $this->dm->findByRid($rid);
            $this->assertNull($v);
        }
    }
}