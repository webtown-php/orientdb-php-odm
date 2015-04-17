<?php

/**
 * QueryTest
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @author     Daniele Alessandri <suppakilla@gmail.com>
 * @version
 */

namespace Doctrine\ODM\OrientDB\Tests;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Tests\Document\Stub\Contact\Address;
use Doctrine\OrientDB\Binding\BindingInterface;
use PHPUnit\TestCase;
use Prophecy\Argument as Arg;
use Prophecy\Prophecy\ObjectProphecy;

class DocumentManagerTest extends TestCase
{
    protected function createTestManager() {
        $rawResult = json_decode('{
            "@type": "d", "@rid": "#19:0", "@version": 2, "@class": "ContactAddress",
            "name": "Luca",
            "surname": "Garulli",
            "out": ["#20:1"]
        }', true);

        /** @var BindingInterface|ObjectProphecy $binding */
        $binding = $this->prophesize(BindingInterface::class);
        $binding->getDocument(Arg::any(), Arg::any())
                ->willReturn($rawResult);

        $data = <<<JSON
{
    "classes": [
        {"name":"ContactAddress", "clusters":[1]}
    ]
}
JSON;
        $data = json_decode($data, true);

        $binding->getDatabaseInfo()
                ->willReturn($data);

        $binding->getDatabaseName()
            ->willReturn('dummy');

        $configuration = $this->getConfiguration();
        $configuration->setMetadataDriverImpl($configuration->newDefaultAnnotationDriver(['test/Doctrine/ODM/OrientDB/Tests/Document/Stub']));
        $manager = new DocumentManager($binding->reveal(), $configuration);

        return $manager;
    }

    public function testMethodUsedToTryTheManager() {
        $manager  = $this->createTestManager();
        $metadata = $manager->getClassMetadata(Address::class);

        $this->assertInstanceOf(ClassMetadata::class, $metadata);
    }

    public function testFindingADocument() {
        $manager = $this->createTestManager();

        $this->assertInstanceOf('Doctrine\ODM\OrientDB\Tests\Document\Stub\Contact\Address', $manager->findByRid('1:1'));
    }
}
