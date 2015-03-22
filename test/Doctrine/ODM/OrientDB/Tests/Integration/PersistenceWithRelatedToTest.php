<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\DocumentManager;
use Integration\Document\PersonV;
use Integration\Document\PostV;
use PHPUnit\TestCase;

/**
 * Tests
 * @group integration
 */
class PersistenceWithRelatedToTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    protected $manager;

    protected function setUp() {
        $this->manager = $this->createDocumentManager();
    }

    /**
     * @test
     * @return string[]
     */
    public function persist_vertices() {
        $j       = new PersonV();
        $j->name = "Jennifer";
        $this->manager->persist($j);

        $s       = new PersonV();
        $s->name = "Sydney";
        $this->manager->persist($s);

        $c       = new PersonV();
        $c->name = "Cameron";
        $this->manager->persist($c);

        $p        = new PostV();
        $p->title = "The Title";
        $this->manager->persist($p);

        $this->manager->flush();
        $this->manager->clear();

        $this->assertInstanceOf(PersonV::class, $this->manager->findByRid($j->rid));
        $this->assertInstanceOf(PersonV::class, $this->manager->findByRid($s->rid));
        $this->assertInstanceOf(PersonV::class, $this->manager->findByRid($c->rid));
        $this->assertInstanceOf(PostV::class, $this->manager->findByRid($p->rid));

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
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->manager->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->manager->findByRid($rids[2]);

        $j->follows->add($s);
        $j->follows->add($c);
        $this->manager->flush();
        $this->manager->clear();

        /** @var PersonV $j */
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->manager->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->manager->findByRid($rids[2]);

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
        /** @var PersonV $j */
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $c = $this->manager->findByRid($rids[2]);

        $j->follows->removeElement($c);
        $this->manager->flush();
        $this->manager->clear();

        /** @var PersonV $j */
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->manager->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->manager->findByRid($rids[2]);

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
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->manager->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->manager->findByRid($rids[2]);

        $j->followers->add($s);
        $j->followers->add($c);
        $this->manager->flush();
        $this->manager->clear();

        /** @var PersonV $j */
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->manager->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->manager->findByRid($rids[2]);

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
        $j = $this->manager->findByRid($rids[0]);

        $j->followers->clear();
        $this->manager->flush();
        $this->manager->clear();

        /** @var PersonV $j */
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->manager->findByRid($rids[1]);
        /** @var PersonV $s */
        $c = $this->manager->findByRid($rids[2]);

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
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->manager->findByRid($rids[1]);
        /** @var PostV $s */
        $p = $this->manager->findByRid($rids[3]);

        $j->likes->add($s);
        $j->likes->add($p);
        $this->manager->flush();
        $this->manager->clear();

        /** @var PersonV $j */
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->manager->findByRid($rids[1]);
        /** @var PostV $s */
        $p = $this->manager->findByRid($rids[3]);

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
            $v = $this->manager->findByRid($rid);
            $this->manager->remove($v);
        }

        $this->manager->flush();
        $this->manager->clear();

        foreach ($rids as $rid) {
            $v = $this->manager->findByRid($rid);
            $this->assertNull($v);
        }
    }
}