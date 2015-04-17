<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;

use Doctrine\ODM\OrientDB\Tests\Models\Standard\MapPoint;

/**
 * @group integration
 */
class DoubleTest extends AbstractDataTypeTest
{
    private $rid;

    /**
     * @before
     */
    public function loadBefore() {
        $b         = $this->dm->getBinding();
        $this->rid = $b->command('INSERT INTO MapPoint set x=5.5, y=10.10')['result'][0]['@rid'];
    }

    public function testHydrationOfADoubleProperty() {
        /** @var MapPoint $point */
        $point = $this->dm->findByRid($this->rid);

        $this->assertInternalType('float', $point->x);
        $this->assertEquals(5.5, $point->x);
        $this->assertInternalType('float', $point->y);
        $this->assertEquals(10.10, $point->y);
    }
}
