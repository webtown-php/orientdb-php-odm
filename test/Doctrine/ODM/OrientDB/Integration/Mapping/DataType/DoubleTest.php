<?php

/**
 * DoubleTest
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @version
 */

namespace test\Doctrine\ODM\OrientDB\Integration\Mapping\DataType;

use test\PHPUnit\TestCase;

/**
 * @group integration
 */
class DoubleTest extends TestCase
{

    public function testHydrationOfADoubleProperty() {
        $manager = $this->createDocumentManager();
        //MapPoint
        $point = $manager->findByRid("#" . $this->getClassId('MapPoint') . ":0");

        $this->assertInternalType('float', $point->y);
    }
}
