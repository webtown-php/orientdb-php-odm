<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Mapping\DataType;

use Doctrine\ODM\OrientDB\Tests\Integration\AbstractIntegrationTest;

abstract class AbstractDataTypeTest extends AbstractIntegrationTest
{
    protected function setUp() {
        $this->useModelSet('standard');
        parent::setUp();
    }
}