<?php

/**
 * RemoveTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     David Funaro <ing.davidino@gmail.com>
 * @version
 */

namespace Doctrine\OrientDB\Tests\Query\Command\Index;

use Doctrine\OrientDB\Query\Command\Index\Rebuild;
use PHPUnit\TestCase;

class RebuildTest extends TestCase
{
    public function setup() {
        $this->rebuild = new Rebuild('indexName');
    }

    public function testTheSchemaIsValid() {
        $tokens = array(
            ':IndexName' => array(),
        );

        $this->assertTokens($tokens, $this->rebuild->getTokens());
    }

    public function testConstructionOfAnObject() {
        $query = 'REBUILD INDEX indexName';

        $this->assertCommandGives($query, $this->rebuild->getRaw());
    }
}
