<?php

/**
 * OClassTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Doctrine\OrientDB\Tests\Query\Command\Truncate;

use Doctrine\OrientDB\Query\Command\Truncate\OClass as TruncateClass;
use PHPUnit\TestCase;

class OClassTest extends TestCase
{
    public function testYouGenerateAValidSQLToTruncateAClass() {
        $truncate = new TruncateClass('myClass');

        $this->assertCommandGives("TRUNCATE CLASS myClass", $truncate->getRaw());
    }

    public function testTheNameArgumentIsFiltered() {
        $truncate = new TruncateClass('myClass 54..');

        $this->assertCommandGives("TRUNCATE CLASS myClass54", $truncate->getRaw());
    }
}
