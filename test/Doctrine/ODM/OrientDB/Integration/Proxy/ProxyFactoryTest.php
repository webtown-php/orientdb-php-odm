<?php

namespace test\Doctrine\ODM\OrientDB\Proxy;

use test\PHPUnit\TestCase;

/**
 * Class ProxyFactoryTest
 *
 * @package test\Doctrine\ODM\OrientDB\Proxy
 * @author  Tamás Millián <tamas.millian@gmail.com>
 */
class ProxyFactoryTest extends TestCase
{

    public function testGenerate() {
        $manager      = $this->createDocumentManager();
        $metadata     = $manager->getClassMetadata('test\Integration\Document\Country');
        $proxyFactory = $manager->getProxyFactory();
        $proxyFactory->generateProxyClasses(array($metadata));

        $filename = $this->getProxyDirectory() . '/__CG__testIntegrationDocumentCountry.php';
        $this->assertFileExists($filename);
    }

    public function testLazyLoad() {
        $manager = $this->createDocumentManager();

        $rid   = '#' . $this->getClassId('City') . ':0';
        $proxy = $manager->getReference($rid);
        $this->assertEquals($rid, $proxy->getRid());
        $this->assertFalse($proxy->__isInitialized());
        $this->assertEquals('Rome', $proxy->name);
        $this->assertTrue($proxy->__isInitialized());
    }

    public function testEagerLoad() {
        $manager = $this->createDocumentManager();
        $rid     = '#' . $this->getClassId('City') . ':0';
        $proxy   = $manager->findByRid($rid);
        $this->assertTrue($proxy->__isInitialized());
        $this->assertEquals($rid, $proxy->getRid());
        $this->assertEquals('Rome', $proxy->name);
    }

    public function testCloner() {
        $manager = $this->createDocumentManager();
        $rid     = '#' . $this->getClassId('City') . ':0';
        $proxy   = $manager->getReference($rid);

        $clone = clone $proxy;
        $this->assertFalse($proxy->__isInitialized());
        $this->assertTrue($clone->__isInitialized());

        $this->assertEquals($rid, $clone->getRid());
        $this->assertEquals('Rome', $clone->name);
    }
}