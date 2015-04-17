<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;
use Doctrine\ODM\OrientDB\Tests\Models\Standard\Country;

/**
 * @group integration
 */
class StringTest extends AbstractDataTypeTest
{
    private $rid;

    /**
     * @before
     */
    public function loadBefore() {
        $b         = $this->dm->getBinding();
        $this->rid = $b->command("INSERT INTO Country set name='Australia'")->result[0]->{'@rid'};;
    }

    public function testHydratingAStringProperty() {
        /** @var Country $country */
        $country = $this->dm->findByRid($this->rid);

        $this->assertInternalType('string', $country->name);
        $this->assertEquals('Australia', $country->name);
    }
}
