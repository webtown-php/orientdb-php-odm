<?php

/**
 * DropTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Doctrine\OrientDB\Tests\Query\Command\Property;

use Doctrine\OrientDB\Query\Command\Property\Drop;
use PHPUnit\TestCase;

class DropTest extends TestCase
{
    public function setup() {
        $this->drop = new Drop('p');
    }

    public function testTheSchemaIsValid() {
        $tokens = array(
            ':Class'    => array(),
            ':Property' => array(),
        );

        $this->assertTokens($tokens, $this->drop->getTokens());
    }

    public function testConstructionOfAnObject() {
        $query = 'DROP PROPERTY .p';

        $this->assertCommandGives($query, $this->drop->getRaw());
    }

    public function testUsingTheFluentInterface() {
        $query = 'DROP PROPERTY c.p';

        $this->assertCommandGives($query, $this->drop->on('c')->getRaw());
    }
}
