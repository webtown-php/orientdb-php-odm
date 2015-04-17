<?php

namespace Doctrine\OrientDB\Tests\Binding;

use Doctrine\OrientDB\Binding\BindingInterface;
use PHPUnit\TestCase;

abstract class AbstractBindingInterfaceTest extends TestCase
{
    /**
     * @var BindingInterface
     */
    protected $b;

    /**
     * @return BindingInterface
     */
    protected abstract function getBinding();

    protected function setUp() {
        $this->b = $this->getBinding();
        parent::setUp();
    }

    public function testServerInfoMethod() {
        $this->assertNotNull($res = $this->b->getServerInfo());
    }

    /**
     * @test
     */
    public function can_get_existing_database() {
        $this->assertInstanceOf('\stdClass', $res = $this->b->getDatabase(TEST_ODB_DATABASE), 'Get information about an existing database');
    }

    /**
     * @test
     */
    public function databaseExists_returns_true_for_existing_database() {
        $this->assertTrue($this->b->databaseExists(TEST_ODB_DATABASE), 'database should exist');
    }

    /**
     * @test
     */
    public function databaseExists_returns_false_for_nonexistent_database() {
        $this->assertFalse($this->b->databaseExists('INVALID_DB'), 'database should not exist');
    }


    /**
     * @test
     * @expectedException \Doctrine\OrientDB\Binding\Exception\InvalidDatabaseException
     */
    public function will_throw_exception_for_invalid_database() {
        $this->b->getDatabase('INVALID_DB');
    }

    public function testListDatabasesMethod() {
        $this->assertInternalType('array', $this->b->listDatabases());
    }

    /**
     * @test
     */
    public function can_create_database() {
        $db = $this->b->createDatabase(TEST_ODB_DATABASE . '_temporary');
        $this->assertInstanceOf('\stdClass', $db, 'Create new database');
        $this->assertObjectHasAttribute('currentUser', $db);
    }

    /**
     * @test
     * @depends can_create_database
     * @expectedException \Doctrine\OrientDB\Binding\Exception\BindingException
     */
    public function will_throw_exception_for_existing_database() {
        $this->b->createDatabase(TEST_ODB_DATABASE . '_temporary');
    }

    /**
     * @test
     * @depends can_create_database
     */
    public function can_delete_existing_database() {
        $this->b->deleteDatabase(TEST_ODB_DATABASE . '_temporary');
    }

    /**
     * @test
     * @depends can_delete_existing_database
     * @expectedException \Doctrine\OrientDB\Binding\Exception\InvalidDatabaseException
     */
    public function will_throw_exception_for_nonexistent_database() {
        $this->b->deleteDatabase(TEST_ODB_DATABASE . '_temporary');
    }

    /**
     * @test
     */
    public function command_returns_expected_results() {
        $this->assertNotNull($this->b->command('SELECT FROM Address'), 'Execute a simple select');
        $this->assertNotNull($this->b->command("SELECT FROM City WHERE name = 'Rome'"), 'Execute a select with WHERE condition');
        $this->assertNotNull($this->b->command('SELECT FROM City WHERE name = "Rome"'), 'Execute another select with WHERE condition');
    }



    /**
     * @test
     * @expectedException \Doctrine\OrientDB\Binding\Exception\BindingException
     */
    public function command_throws_exception_for_invalid_command() {
        $this->b->command('INVALID SQL');
    }

    /**
     * @test
     */
    public function query_returns_expected_results() {
        $this->assertNotNull($this->b->query('SELECT FROM Address'), 'Executes a SELECT');
        $this->assertNotNull($this->b->query('SELECT FROM Address', null, 10), 'Executes a SELECT with LIMIT');
    }

    /**
     * @test
     * @expectedException \Doctrine\OrientDB\Binding\Exception\BindingException
     */
    public function query_throws_exception_for_update() {
        $this->b->query("UPDATE Profile SET online = false");
    }

    /**
     * @test
     * @expectedException \Doctrine\OrientDB\Binding\Exception\BindingException
     */
    public function getDocument_with_invalid_cluster_throws_exception() {
        $this->b->getDocument('999:0');
    }

    /**
     * @test
     * @expectedException \Doctrine\OrientDB\Binding\Exception\BindingException
     */
    public function getDocument_with_invalid_RID_throws_exception() {
        $this->b->getDocument('991');
    }

    /**
     * @test
     */
    public function getDocument_returns_expected_result() {
        $this->assertNull($this->b->getDocument('9:10000'), 'Retrieves a non existing document');
        $this->assertNotNull($this->b->getDocument('1:0'), 'Retrieves a valid document');
    }

    /**
     * @depends testDeleteADocument
     *
     * @param $rid
     */
    public function testDocumentDoesNotExist($rid) {
        $res = $this->b->documentExists($rid);
        $this->assertFalse($res);
    }

    public function testGetDatabaseName() {
        $this->assertEquals(TEST_ODB_DATABASE, $this->b->getDatabaseName());
    }

}