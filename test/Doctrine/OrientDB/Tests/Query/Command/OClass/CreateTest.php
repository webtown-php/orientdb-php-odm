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

use Doctrine\OrientDB\Query\Command\OClass\Create;
use PHPUnit\TestCase;

class CreateTest extends TestCase
{
    public function setup() {
        $this->create = new Create('p');
    }

    public function testTheSchemaIsValid() {
        $tokens = array(
            ':Class' => array(),
        );

        $this->assertTokens($tokens, $this->create->getTokens());
    }

    public function testConstructionOfAnObject() {
        $query = 'CREATE CLASS p';

        $this->assertCommandGives($query, $this->create->getRaw());
    }
}
