<?php

/**
 * StringTest
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
class StringTest extends TestCase
{
    public function testHydratingAStringProperty() {
        $manager = $this->createDocumentManager();
        //Country
        $country = $manager->findByRid('#' . $this->getClassId('Country') . ':0');

        $this->assertInternalType('string', $country->name);
    }
}
