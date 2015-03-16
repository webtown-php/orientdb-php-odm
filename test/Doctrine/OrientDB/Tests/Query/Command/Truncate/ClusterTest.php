<?php

/**
 * ClusterTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @version
 */

namespace Doctrine\OrientDB\Tests\Query\Command\Truncate;

use Doctrine\OrientDB\Query\Command\Truncate\Cluster as TruncateCluster;
use PHPUnit\TestCase;

class ClusterTest extends TestCase
{
    public function testYouGenerateAValidSQLToTruncateAClass() {
        $truncate = new TruncateCluster('myClass');

        $this->assertCommandGives("TRUNCATE CLUSTER myClass", $truncate->getRaw());
    }

    public function testTheNameArgumentIsFiltered() {
        $truncate = new TruncateCluster('myClass 54..');

        $this->assertCommandGives("TRUNCATE CLUSTER myClass54", $truncate->getRaw());
    }
}
