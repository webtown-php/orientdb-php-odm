<?php

/**
 * QueryTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Doctrine\OrientDB\Tests\Query\Command\OClass;

use Doctrine\OrientDB\Query\Command\OClass\Alter;
use PHPUnit\TestCase;

class AlterTest extends TestCase
{
    public function setup() {
        $this->alter = new Alter('class', 'prop', 'value');
    }

    public function testTheSchemaIsValid() {
        $tokens = array(
            ':Class'     => array(),
            ':Attribute' => array(),
            ':Value'     => array(),
        );

        $this->assertTokens($tokens, $this->alter->getTokens());
    }

    public function testConstructionOfAnObject() {
        $query = 'ALTER CLASS class prop value';

        $this->assertCommandGives($query, $this->alter->getRaw());
    }
}
