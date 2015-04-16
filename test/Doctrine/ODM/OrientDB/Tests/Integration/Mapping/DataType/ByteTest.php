<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;

/**
 * @group integration
 */
class ByteTest extends AbstractDataTypeTest
{
    public function testHydrationOfAByteProperty() {
        $role = $this->dm->findByRid("#4:0");

        $this->assertInternalType('integer', $role->mode);
    }
}
