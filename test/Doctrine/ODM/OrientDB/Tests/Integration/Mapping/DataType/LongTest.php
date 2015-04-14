<?php

/**
 * LongTest
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @version
 */

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;

use Doctrine\OrientDB\Query\Command;
use Integration\Document\Profile;
use PHPUnit\TestCase;

/**
 * @group integration
 */
class LongTest extends TestCase
{
    public function testHydrationOfALongProperty() {

        $manager = $this->createDocumentManager();

        /** @var Profile $neoProfile */
        $neoProfile = $manager->findByRid("#" . $this->getClassId('Profile') . ":0");
        $neoProfile->hash = 2937480;
        $manager->flush();
        unset($neoProfile);

        $neoProfile = $manager->findByRid("#" . $this->getClassId('Profile') . ":0");

        $this->assertInternalType('integer', $neoProfile->hash);
    }
}
