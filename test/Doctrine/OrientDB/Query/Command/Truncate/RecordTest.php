<?php

/**
 * RecordTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace test\Doctrine\OrientDB\Query\Command\Truncate;

use Doctrine\OrientDB\Query\Command\Truncate\Record as TruncateRecord;
use test\PHPUnit\TestCase;

class RecordTest extends TestCase
{
    public function testYouGenerateAValidSQLToTruncateAClass() {
        $truncate = new TruncateRecord('myClass');

        $this->assertCommandGives("TRUNCATE RECORD", $truncate->getRaw());
    }

    public function testTheNameArgumentIsFiltered() {
        $truncate = new TruncateRecord('10:2');

        $this->assertCommandGives("TRUNCATE RECORD 10:2", $truncate->getRaw());
    }
}
