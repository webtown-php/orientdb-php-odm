<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\LikedE;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\PersonV;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\PostV;
use PHPUnit\TestCase;

/**
 * Tests
 * @group integration
 */
class PersistenceWithRelatedToViaTest extends TestCase
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
     */
    public function add_LikedE_to_PostV($rids) {
        /** @var PersonV $j */
        $j = $this->manager->findByRid($rids[0]);
        /** @var PersonV $s */
        $s = $this->manager->findByRid($rids[1]);
        /** @var PostV $p */
        $p = $this->manager->findByRid($rids[3]);

        $e              = new LikedE();
        $e->description = "d1";
        $e->out         = $j;
        $e->in          = $p;

        $p->liked->add($e);
        $this->manager->flush();
        $this->manager->clear();

        /** @var PersonV $j */
        $j = $this->manager->findByRid($rids[0]);
        /** @var PostV $s */
        $p = $this->manager->findByRid($rids[3]);

        $this->assertCount(1, $p->liked);

        /** @var LikedE $e */
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