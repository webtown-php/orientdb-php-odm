<?php

/**
 * CountTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Doctrine\OrientDB\Tests\Query\Command\Index;

use Doctrine\OrientDB\Query\Command\Index\Count;
use PHPUnit\TestCase;

class CountTest extends TestCase
{
    public function setup() {
        $this->count = new Count('indexName');
    }

    public function testTheSchemaIsValid() {
        $tokens = array(
            ':Name' => array(),
        );

        $this->assertTokens($tokens, $this->count->getTokens());
    }

    public function testConstructionOfAnObject() {
        $query = 'SELECT count(*) AS size from index:indexName';

        $this->assertCommandGives($query, $this->count->getRaw());
    }
}
