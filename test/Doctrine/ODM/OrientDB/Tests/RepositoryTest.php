<?php

/**
 * ReporitoryTest
 *
 * @package    Doctrine\ODM\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @version
 */

namespace Doctrine\ODM\OrientDB\Tests;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\DocumentRepository;
use Doctrine\ODM\OrientDB\Mapping;
use Doctrine\OrientDB\Binding\HttpBindingInterface;
use PHPUnit\TestCase;

class RepositoryTest extends TestCase
{
    protected function createRepository() {
        $rawResult = json_decode('[{
            "@type": "d", "@rid": "#19:1", "@version": 1, "@class": "ContactAddress",
            "name": "Luca",
            "surname": "Garulli",
            "out": ["#20:1"]
        }, {
            "@type": "d", "@rid": "#19:1", "@version": 1, "@class": "ContactAddress",
            "name": "Luca",
            "surname": "Garulli",
            "out": ["#20:1"]
        }]');

        $result = $this->getMock('Doctrine\OrientDB\Binding\BindingResultInterface');
        $result->expects($this->at(0))
               ->method('getResult')
               ->will($this->returnValue($rawResult));


        $binding = $this->getMock(HttpBindingInterface::class);
        $binding->expects($this->any())
                ->method('query')
                ->will($this->returnValue($result));

        $manager  = $this->prepareManager($binding);
        $uow      = $manager->getUnitOfWork();
        $metadata = $manager->getClassMetadata('Doctrine\ODM\OrientDB\Tests\Document\Stub\Contact\Address');

        $repository = new DocumentRepository($manager, $uow, $metadata);

        return $repository;
    }

    protected function prepareManager(HttpBindingInterface $binding) {
        $config = $this->getConfiguration();
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(['test/Doctrine/ODM/OrientDB/Tests/Document/Stub']));

        return new DocumentManager($binding, $config);
    }

    public function testFindAll() {
        $repository = $this->createRepository();
        $documents  = $repository->findAll();

        $this->assertSame(2, count($documents));
    }

    public function testYouCanExecuteFindByQueries() {
        $repository = $this->createRepository();
        $documents  = $repository->findByUsername('hello');

        $this->assertSame(2, count($documents));
    }

    public function testYouCanExecuteFindOneByQueries() {
        $repository = $this->createRepository();
        $documents  = $repository->findOneByUsername('hello');

        $this->assertSame(1, count($documents));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testYouCantCallWhateverMethodOfARepository() {
        $dm         = $this->prepareManager(new \Doctrine\OrientDB\Binding\HttpBinding(new \Doctrine\OrientDB\Binding\BindingParameters()));
        $uow        = $dm->getUnitOfWork();
        $metadata   = $dm->getClassMetadata('Doctrine\ODM\OrientDB\Tests\Document\Stub\Contact\Address');
        $repository = new DocumentRepository($dm, $uow, $metadata);
        $documents  = $repository->findOn();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testYouCanOnlyPassObjectsHavingGetRidMethodAsArgumentsOfFindSomeBySomething() {
        $dm         = $this->prepareManager(new \Doctrine\OrientDB\Binding\HttpBinding(new \Doctrine\OrientDB\Binding\BindingParameters()));
        $uow        = $dm->getUnitOfWork();
        $metadata   = $dm->getClassMetadata('Doctrine\ODM\OrientDB\Tests\Document\Stub\Contact\Address');
        $repository = new DocumentRepository($dm, $uow, $metadata);
        $documents  = $repository->findOneByJeex(new \stdClass());
    }
}
