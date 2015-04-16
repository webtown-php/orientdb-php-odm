<?php

namespace Doctrine\ODM\OrientDB\Tests\Tools;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Tests\Models\CMS\CmsUser;
use Doctrine\ODM\OrientDB\Tools\SchemaTool;
use Doctrine\OrientDB\Schema\OClass;
use PHPUnit\TestCase;

/**
 * @group functional
 */
class SchemaToolTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    private $dm;


    /**
     * @before
     */
    public function before() {
        $this->dm = $this->createDocumentManager();
    }

    /**
     * @test
     */
    public function getClass_returns_instance() {
        $dm     = $this->dm;
        $st     = new SchemaTool($dm);
        $schema = $st->getSchemaFromMetadata([$dm->getClassMetadata(CmsUser::class)]);
        $class  = $schema->getClass('CmsUser');
        $this->assertInstanceOf(OClass::class, $class);

        return $class;
    }

    /**
     * @test
     * @depends getClass_returns_instance
     *
     * @param OClass $class
     */
    public function class_has_expected_values(OClass $class) {
        $this->assertEquals('CmsUser', $class->getName(), 'getName');
        $this->assertNull($class->getSuperClass(), 'getSuperClass should be null');
        $this->assertFalse($class->isAbstract(), 'isAbstract');
    }

    /**
     * @test
     * @depends getClass_returns_instance
     *
     * @param OClass $class
     */
    public function class_has_expected_properties(OClass $class) {
        $expected = ['articles', 'username', 'status', 'phonenumbers', 'name'];
        sort($expected);

        $props = $class->getProperties();
        $names = array_keys($props);
        sort($names);

        $this->assertCount(5, $props, 'unexpected number of properties');
        $this->assertEquals($expected, $names, 'properties do not match');

        $p = $props['articles'];

    }
}
