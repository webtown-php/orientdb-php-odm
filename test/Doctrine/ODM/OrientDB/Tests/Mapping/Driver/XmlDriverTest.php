<?php

namespace Doctrine\ODM\OrientDB\Tests\Mapping\Driver;

use Doctrine\ODM\OrientDB\Mapping\Driver\XmlDriver;

/**
 * @group functional
 */
class XmlDriverTest extends AbstractMappingDriverTest
{

    /**
     * @inheritdoc
     */
    protected function _loadDriver() {
        return new XmlDriver(__DIR__ . '/xml');
    }
}