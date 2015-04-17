<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;

use Doctrine\ODM\OrientDB\Tests\Models\Standard\Profile;

/**
 * @group integration
 */
class LongTest extends AbstractDataTypeTest
{
    private $rid;

    /**
     * @before
     */
    public function loadBefore() {
        $b         = $this->dm->getBinding();
        $this->rid = $b->command('INSERT INTO Profile set hash=null')->result[0]->{'@rid'};;
    }

    public function testHydrationOfALongProperty() {
        /** @var Profile $neoProfile */
        $neoProfile       = $this->dm->findByRid($this->rid);
        $neoProfile->hash = 2937480;
        $this->dm->flush();
        unset($neoProfile);

        $neoProfile = $this->dm->findByRid($this->rid);

        $this->assertInternalType('integer', $neoProfile->hash);
    }
}
