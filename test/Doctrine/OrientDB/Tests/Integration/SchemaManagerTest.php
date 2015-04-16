<?php

namespace Doctrine\OrientDB\Tests\Integration;

use Doctrine\OrientDB\Binding\BindingInterface;
use Doctrine\OrientDB\Schema\OClass;
use Doctrine\OrientDB\Schema\SchemaManager;
use PHPUnit\TestCase;

/**
 * @group integration
 */
class SchemaManagerTest extends TestCase
{
    /**
     * @var BindingInterface
     */
    private static $_b;
    
    private static function getDbName() {
        return TEST_ODB_DATABASE . '__tmp';
    }

    /**
     * @beforeClass
     */
    public static function beforeClass() {
        self::$_b = self::createHttpBinding();
        $b = self::$_b;
        if ($b->databaseExists(self::getDbName())) {
            $b->deleteDatabase(self::getDbName());
        }
        $b->createDatabase(self::getDbName(), 'plocal', 'graph');
    }

    /**
     * @afterClass
     */
    public static function afterClass() {
        $b = self::$_b;
        if ($b->databaseExists(self::getDbName())) {
            $b->deleteDatabase(self::getDbName());
        }
    }

    /**
     * @var SchemaManager
     */
    private $sm;

    /**
     * @before
     */
    public function before() {
        $this->sm = new SchemaManager(self::createHttpBinding([
            'odb.database' => self::getDbName()
        ]));
    }

    /**
     * @test
     */
    public function can_list_databases() {
        $dbs = $this->sm->listDatabases();

        $this->assertInternalType('array', $dbs);
        $this->assertContains(self::getDbName(), $dbs);
    }

    /**
     * @test
     */
    public function can_list_class_names() {
        $names = $this->sm->listClassNames();

        $this->assertInternalType('array', $names);
        $this->assertContains('V', $names);
        $this->assertContains('E', $names);
    }

    /**
     * @test
     */
    public function can_list_classes() {
        $classes = $this->sm->listClasses();
        $this->assertArrayHasKey('OUser', $classes);
        $this->assertArrayHasKey('ORole', $classes);
        $this->assertArrayHasKey('OIdentity', $classes);
        $this->assertArrayHasKey('V', $classes);
        $this->assertArrayHasKey('E', $classes);
    }

    /**
     * @test
     */
    public function OIdentity_has_expected_class_properties() {
        $class = $this->sm->getClass('OIdentity');
        $this->assertInstanceOf(OClass::class, $class);
        $this->assertTrue($class->isAbstract(), 'isAbstract should be true');
    }

    /**
     * @test
     */
    public function OUser_has_expected_class_properties() {
        $class = $this->sm->getClass('OUser');
        $this->assertInstanceOf(OClass::class, $class);
        $this->assertEquals('OUser', $class->getName());
        $this->assertEquals('OIdentity', $class->getSuperClassName(), 'expected OIdentity superclass');
    }
}
