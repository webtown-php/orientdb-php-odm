<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\DocumentManager;
use Integration\Document\PersonV;
use PHPUnit\TestCase;

/**
 * Tests
 * @group integration
 */
class PersistenceWithGraphTest extends TestCase
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
        $j = new PersonV();
        $j->name = "Jennifer";
        $this->manager->persist($j);

        $s = new PersonV();
        $s->name = "Sydney";
        $this->manager->persist($s);

        $c = new PersonV();
        $c->name = "Cameron";
        $this->manager->persist($c);

        $this->manager->flush();

        return [$j->rid, $s->rid, $c->rid];
    }

    /**
     * @depends persist_vertices
     * @test
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

    /**
     * @ntest
     */
    public function add_follow() {
        /** @var PersonV $p */
        $p = $this->manager->findByRid('#26:1');
        /** @var PersonV $o */
        $o = $this->manager->findByRid('#26:0');
        $p->followed->add($o);
        $o = $this->manager->findByRid('#26:2');
        $p->followed->add($o);
        $this->manager->flush();
        $this->manager->clear();

        /** @var PersonV $p */
        $p = $this->manager->findByRid('#26:1');

        $p->followed->removeElement($p->followed->first());
        $this->manager->flush();
    }
}