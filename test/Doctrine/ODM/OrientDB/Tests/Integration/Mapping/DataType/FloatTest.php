<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;

/**
 * @group integration
 */
class FloatTest extends AbstractDataTypeTest
{
    private $rid;

    /**
     * @before
     */
    public function loadBefore() {
        $b         = $this->dm->getBinding();
        $this->rid = $b->command('INSERT INTO MapPoint set x=5.5, y=10.10')->result[0]->{'@rid'};
    }

    public function testHydrationOfAFloatProperty() {
        //MapPoint
        $point = $this->dm->findByRid($this->rid);

        $this->assertInternalType('float', $point->y);
    }
}
