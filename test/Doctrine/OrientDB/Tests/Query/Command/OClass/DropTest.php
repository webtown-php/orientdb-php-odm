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

use Doctrine\OrientDB\Query\Command\OClass\Drop;
use PHPUnit\TestCase;

class DropTest extends TestCase
{
    public function setup() {
        $this->drop = new Drop('p');
    }

    public function testTheSchemaIsValid() {
        $tokens = array(
            ':Class' => array(),
        );

        $this->assertTokens($tokens, $this->drop->getTokens());
    }

    public function testConstructionOfAnObject() {
        $query = 'DROP CLASS p';

        $this->assertCommandGives($query, $this->drop->getRaw());
    }
}
