<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\Tests\Models\Graph\LikedE;
use Doctrine\ODM\OrientDB\Tests\Models\Graph\PersonV;
use Doctrine\ODM\OrientDB\Tests\Models\Graph\PostV;

/**
 * Tests
 * @group integration
 */
class PersistenceWithRelatedToViaTest extends AbstractIntegrationTest
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
     */
    public function add_LikedE_to_PostV($rids) {
        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Graph\PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Graph\PersonV $s */
        $s = $this->dm->findByRid($rids[1]);
        /** @var PostV $p */
        $p = $this->dm->findByRid($rids[3]);

        $e              = new LikedE();
        $e->description = "d1";
        $e->out         = $j;
        $e->in          = $p;

        $p->liked->add($e);
        $this->dm->flush();
        $this->dm->clear();

        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Graph\PersonV $j */
        $j = $this->dm->findByRid($rids[0]);
        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Graph\PostV $s */
        $p = $this->dm->findByRid($rids[3]);

        $this->assertCount(1, $p->liked);

        /** @var \Doctrine\ODM\OrientDB\Tests\Models\Graph\LikedE $e */
        $e = $p->liked->first();
        $this->assertEquals('d1', $e->description);
        $this->assertSame($p, $e->in);
        $this->assertSame($j, $e->out);
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