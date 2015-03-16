<?php

/**
 * FloatTest
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @version
 */

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;

use PHPUnit\TestCase;

/**
 * @group integration
 */
class FloatTest extends TestCase
{
    public function testHydrationOfAFloatProperty() {
        $manager = $this->createDocumentManager();
        //MapPoint
        $point = $manager->findByRid("#" . $this->getClassId('MapPoint') . ":0");

        $this->assertInternalType('float', $point->y);
    }
}
