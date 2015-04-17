<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration\Proxy;

use Doctrine\Common\Persistence\Proxy;
use Doctrine\ODM\OrientDB\Proxy\__CG__\Doctrine\ODM\OrientDB\Tests\Models\Standard\Country;
use Doctrine\ODM\OrientDB\Tests\Integration\AbstractIntegrationTest;
use PHPUnit\TestCase;

/**
 * @group   integration
 */
class ProxyFactoryTest extends AbstractIntegrationTest
{
    private $rid;

    protected function setUp() {
        $this->useModelSet('standard');
        parent::setUp();

        $b = $this->dm->getBinding();
        $this->rid = $b->command('INSERT INTO City set name="Rome"')['result'][0]['@rid'];
    }

    public function testGenerate() {
        $manager      = $this->createDocumentManager();
        $metadata     = $manager->getClassMetadata(Country::class);
        $proxyFactory = $manager->getProxyFactory();
        $proxyFactory->generateProxyClasses(array($metadata));

        $filename = $this->getProxyDirectory() . '/__CG__testIntegrationDocumentCountry.php';
        $this->assertFileExists($filename);
    }

    public function testLazyLoad() {
        $manager = $this->dm;

        $proxy = $manager->getReference($this->rid);
        $this->assertEquals($this->rid, $proxy->getRid());
        $this->assertFalse($proxy->__isInitialized());
        $this->assertEquals('Rome', $proxy->name);
        $this->assertTrue($proxy->__isInitialized());
    }

    public function testEagerLoad() {
        $manager = $this->dm;
        $proxy   = $manager->findByRid($this->rid);
        $this->assertNotEmpty($proxy);
        $this->assertNotInstanceOf(Proxy::class, $proxy);
        $this->assertEquals($this->rid, $proxy->getRid());
        $this->assertEquals('Rome', $proxy->name);
    }

    public function testCloner() {
        $manager = $this->dm;
        $proxy   = $manager->getReference($this->rid);
        $clone = clone $proxy;
        $this->assertTrue($clone->__isInitialized());

        $this->assertEquals($this->rid, $clone->getRid());
        $this->assertEquals('Rome', $clone->name);
    }
}