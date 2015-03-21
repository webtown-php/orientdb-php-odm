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
     * @return mixed
     */
    public function persist_single_document() {
        /** @var PersonV $p */
        $p = $this->manager->findByRid('#26:1');
        $followers = $p->followers->toArray();
    }

    /**
     * @test
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