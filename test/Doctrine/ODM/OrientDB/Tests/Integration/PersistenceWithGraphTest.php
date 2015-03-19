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
        $p = $this->manager->findByRid('#26:1', '*:1');
        $followers = $p->followers->toArray();
    }
}