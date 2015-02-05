<?php

namespace test\Doctrine\ODM\OrientDB\Integration\Mapping;


use Doctrine\ODM\OrientDB\Mapping\Annotations\ReaderInterface;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use test\PHPUnit\TestCase;

class ClassMetadataFactoryTest extends TestCase
{
    protected $metadataFactory;

    public function setup()
    {
        $this->metadataFactory = $this->createManager(array('document_dirs' => array('test/Doctrine/ODM/OrientDB/Document/Stub' => 'test')))->getMetadataFactory();
    }

    public function testConvertPathToClassName()
    {
        $className = $this->metadataFactory->getClassByPath('./test/Doctrine/ODM/OrientDB/Document/Stub/City.php', 'test');
        $this->assertEquals('\test\Doctrine\ODM\OrientDB\Document\Stub\City', $className);
    }

    public function testConvertPathToClassNameWhenProvidingNestedNamespaces()
    {
        $className = $this->metadataFactory->getClassByPath('./test/Doctrine/ODM/OrientDB/Document/Stub/City.php', 'test\Doctrine\ODM\OrientDB');
        $this->assertEquals('\test\Doctrine\ODM\OrientDB\Document\Stub\City', $className);
    }

    public function testGettingTheDirectoriesInWhichTheMapperLooksForPOPOs()
    {
        $metadataFactory = new ClassMetadataFactory(
            $this->getMock(ReaderInterface::class),
            $this->getMock('\Doctrine\Common\Cache\Cache')
        );

        $directories = array('dir' => 'namespace', 'dir2' => 'namespace2');

        $metadataFactory->setDocumentDirectories($directories);

        $this->assertEquals($directories, $metadataFactory->getDocumentDirectories());
    }
} 