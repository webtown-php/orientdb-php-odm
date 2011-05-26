<?php

/**
 * CreateTest
 *
 * @package    Orient
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Orient\Test\Query\Command\Index;

use Orient\Test\PHPUnit\TestCase;
use Orient\Query\Command\Index\Drop;

class DropTest extends TestCase
{
    public function setup()
    {
        $this->drop = new Drop('p', 'c');
    }

    public function testTheSchemaIsValid()
    {
        $tokens = array(
            ':IndexClass' => array(),
            ':Property' => array(),
        );

        $this->assertTokens($tokens, $this->drop->getTokens());
    }

    public function testConstructionOfAnObject()
    {
        $query = 'DROP INDEX c.p';

        $this->assertCommandGives($query, $this->drop->getRaw());
    }

    public function testConstructionOfAnIndexWithoutClass()
    {
        $query = 'DROP INDEX p';
        $this->drop = new Drop('p');

        $this->assertCommandGives($query, $this->drop->getRaw());
    }
}
