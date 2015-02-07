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

namespace test\Doctrine\ODM\OrientDB;

use test\PHPUnit\TestCase;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping;
use Doctrine\ODM\OrientDB\DocumentRepository;
use Doctrine\ODM\OrientDB\Types\Rid;

class RepositoryTest extends TestCase
{
    protected function createRepository()
    {
        $rawResult = json_decode('[{
            "@type": "d", "@rid": "#19:1", "@version": 1, "@class": "Address",
            "name": "Luca",
            "surname": "Garulli",
            "out": ["#20:1"]
        }, {
            "@type": "d", "@rid": "#19:1", "@version": 1, "@class": "Address",
            "name": "Luca",
            "surname": "Garulli",
            "out": ["#20:1"]
        }]');

        $result = $this->getMock('Doctrine\OrientDB\Binding\BindingResultInterface');
        $result->expects($this->at(0))
               ->method('getResult')
               ->will($this->returnValue($rawResult));


        $binding = $this->getMock('Doctrine\OrientDB\Binding\BindingInterface');
        $binding->expects($this->any())
                ->method('execute')
                ->will($this->returnValue($result));

        $manager = $this->prepareManager($binding);

        $repository = new DocumentRepository('test\Doctrine\ODM\OrientDB\Document\Stub\Contact\Address', $manager);

        return $repository;
    }

    protected function prepareManager($binding)
    {
        $configuration = $this->getConfiguration(array('document_dirs' => array('test/Doctrine/ODM/OrientDB/Document/Stub' => 'test')));
        return new DocumentManager($binding, $configuration);
    }

    public function testFindAll()
    {
        $repository = $this->createRepository();
        $documents = $repository->findAll();

        $this->assertSame(2, count($documents));
    }
    
    public function testYouCanExecuteFindByQueries()
    {
        $repository = $this->createRepository();
        $documents  = $repository->findByUsername('hello');
        
        $this->assertSame(2, count($documents));
    }
    
    public function testYouCanExecuteFindOneByQueries()
    {
        $repository = $this->createRepository();
        $documents  = $repository->findOneByUsername('hello');
        
        $this->assertSame(1, count($documents));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testYouCantCallWhateverMethodOfARepository()
    {
        $repository = new DocumentRepository('My\\Class', $this->prepareManager(new \Doctrine\OrientDB\Binding\HttpBinding(new \Doctrine\OrientDB\Binding\BindingParameters())));
        $documents  = $repository->findOn();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testYouCanOnlyPassObjectsHavingGetRidMethodAsArgumentsOfFindSomeBySomething()
    {
        $repository = new DocumentRepository('My\\Class', $this->prepareManager(new \Doctrine\OrientDB\Binding\HttpBinding(new \Doctrine\OrientDB\Binding\BindingParameters())));
        $documents  = $repository->findOneByJeex(new \stdClass());
    }
}
