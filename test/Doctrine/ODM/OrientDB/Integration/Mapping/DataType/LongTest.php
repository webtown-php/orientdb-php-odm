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

namespace test\Doctrine\ODM\OrientDB\Integration\Mapping\DataType;

use Doctrine\OrientDB\Query\Command;
use test\PHPUnit\TestCase;
use Doctrine\OrientDB\Query\Query;

/**
 * @group integration
 */
class LongTest extends TestCase
{
    public function testHydrationOfALongProperty()
    {

        $manager = $this->createDocumentManager();

        $query = new Query();
        $query->update('Profile')
              ->set(array('hash' => 2937480 ))
              ->where('@rid = ?', '#'.$this->getClassId('Profile').':0')
              ->returns(Command::RETURN_AFTER)
        ;

        $manager->execute($query);

        $neoProfile = $manager->findByRid("#".$this->getClassId('Profile').":0");

        $this->assertInternalType('integer', $neoProfile->hash);
    }
}
