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

namespace test\Doctrine\ODM\OrientDB;

use Doctrine\ODM\OrientDB\DocumentRepository;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\OrientDB\Binding\BindingInterface;
use Doctrine\OrientDB\Binding\BindingResultInterface;
use test\PHPUnit\TestCase;
use Doctrine\OrientDB\Query\Query;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Types\Rid;

class ManagerTest extends TestCase
{
    protected function createTestManager()
    {
        $rawResult = json_decode('[{
            "@type": "d", "@rid": "#19:0", "@version": 2, "@class": "Address",
            "name": "Luca",
            "surname": "Garulli",
            "out": ["#20:1"]
        }]');

        $result = $this->getMock(BindingResultInterface::class);
        $result->expects($this->any())
               ->method('getResult')
               ->will($this->returnValue($rawResult));

        $binding = $this->getMock(BindingInterface::class);
        $binding->expects($this->any())
                ->method('execute')
                ->will($this->returnValue($result));

        $configuration = $this->getConfiguration(array('document_dirs' => array('test/Doctrine/ODM/OrientDB/Document/Stub' => 'test')));
        $manager = new DocumentManager($binding, $configuration);

        return $manager;
    }

    public function testMethodUsedToTryTheManager()
    {
        $manager = $this->createTestManager();
        $metadata = $manager->getClassMetadata('test\Doctrine\ODM\OrientDB\Document\Stub\Contact\Address');

        $this->assertInstanceOf(ClassMetadata::class, $metadata);
    }

    public function testManagerActsAsAProxyForExecutingQueries()
    {
        $query = new Query(array('Address'));
        $manager = $this->createTestManager();
        $results = $manager->execute($query);

        $this->isInstanceOf(static::COLLECTION_CLASS, $results);
        $this->assertInstanceOf('test\Doctrine\ODM\OrientDB\Document\Stub\Contact\Address', $results[0]);
    }

    public function testFindingADocument()
    {
        $manager = $this->createTestManager();

        $this->assertInstanceOf('test\Doctrine\ODM\OrientDB\Document\Stub\Contact\Address', $manager->find('1:1'));
    }

    public function testProvidingRightRepositoryClass()
    {
        $manager = $this->createManager();
        $cityRepository = $manager->getRepository('test\Doctrine\ODM\OrientDB\Document\Stub\City');

        $this->assertInstanceOf('test\Doctrine\ODM\OrientDB\Document\Stub\CityRepository',$cityRepository);

        $addressRepository = $manager->getRepository('test\Doctrine\ODM\OrientDB\Document\Stub\Contact\Address');
        $this->assertInstanceOf(DocumentRepository::class,$addressRepository);
    }
}
