<?php

/**
 * HttpBindingTest
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     Daniele Alessandri <suppakilla@gmail.com>
 * @version
 */

namespace Doctrine\OrientDB\Tests\Binding;

use Doctrine\OrientDB\Binding\BindingParameters;
use Doctrine\OrientDB\Binding\HttpBinding;
use PhpOrient\PhpOrient;
use PhpOrient\Protocols\Binary\Data\ID;
use PHPUnit\TestCase;

/**
 * @group integration
 */
class HttpBindingTest extends TestCase
{
    public function testConnectToDatabase() {
        $binding = self::createHttpBinding([
            'odb.username' => TEST_ODB_USER,
            'odb.password' => TEST_ODB_PASSWORD,
            'odb.database' => null,
        ]);

        $this->assertHttpStatus(200, $binding->connect(TEST_ODB_DATABASE));
    }

    public function testConnectToDatabaseWithWrongCredentials() {
        $binding = self::createHttpBinding([
            'odb.username' => 'invalid',
            'odb.password' => 'invalid',
        ]);

        $this->assertHttpStatus(401, $binding->connect('INVALID_DB'));
    }

    public function testDisconnectFromTheServer() {
        $binding = self::createHttpBinding();

        $response = $binding->disconnect()->getInnerResponse();
        $this->assertEquals('Logged out', $response->getBody());
    }

    public function testClassMethods() {
        $binding = self::createHttpBinding();

        $this->assertHttpStatus(500, $binding->getClass('OMG'), 'Get a non existing class');
        $this->assertHttpStatus(201, $binding->postClass('OMG'), 'Create a class');
        $this->assertHttpStatus(204, $binding->deleteClass('OMG'), 'Delete a class');
    }

    public function testClusterMethod() {
        $binding = self::createHttpBinding();

        $this->assertHttpStatus(500, $binding->cluster('ORole'));
        $this->assertHttpStatus(200, $binding->cluster('ORole', 1));

        $result = json_decode($binding->cluster('ORole', 1)->getInnerResponse()->getBody(), true);
        $this->assertSame('ORole', $result['result'][0]['@class'], 'The cluster is wrong');
    }

    public function testServerInfoMethod() {

        $binding = self::createHttpBinding();

        $this->assertHttpStatus(200, $binding->getServerInfo());
    }

    /**
     * @test
     */
    public function can_get_existing_database() {
        $binding = self::createHttpBinding();

        $this->assertInstanceOf('\stdClass', $res = $binding->getDatabase(TEST_ODB_DATABASE), 'Get information about an existing database');
    }

    /**
     * @test
     */
    public function databaseExists_returns_true_for_existing_database() {
        $binding = self::createHttpBinding();

        $this->assertTrue($binding->databaseExists(TEST_ODB_DATABASE), 'database should exist');
    }

    /**
     * @test
     */
    public function databaseExists_returns_false_for_nonexistent_database() {
        $binding = self::createHttpBinding();

        $this->assertFalse($binding->databaseExists('INVALID_DB'), 'database should not exist');
    }


    /**
     * @test
     * @expectedException \Doctrine\OrientDB\Binding\Exception\InvalidDatabaseException
     */
    public function will_throw_exception_for_invalid_database() {
        $binding = self::createHttpBinding();
        $binding->getDatabase('INVALID_DB');
    }

    public function testListDatabasesMethod() {
        $binding = self::createHttpBinding();
        $this->assertInternalType('array', $binding->listDatabases());
    }

    /**
     * @test
     */
    public function can_create_database() {
        $binding = self::createHttpBinding();

        $db = $binding->createDatabase(TEST_ODB_DATABASE . '_temporary');
        $this->assertInstanceOf('\stdClass', $db, 'Create new database');
        $this->assertObjectHasAttribute('currentUser', $db);
    }

    /**
     * @test
     * @depends can_create_database
     * @expectedException \Doctrine\OrientDB\Binding\Exception\BindingException
     */
    public function will_throw_exception_for_existing_database() {
        $binding = self::createHttpBinding();
        $binding->createDatabase(TEST_ODB_DATABASE . '_temporary');
    }

    /**
     * @test
     * @depends can_create_database
     */
    public function can_delete_existing_database() {
        $binding = self::createHttpBinding();
        $binding->deleteDatabase(TEST_ODB_DATABASE . '_temporary');
    }

    /**
     * @test
     * @depends can_delete_existing_database
     * @expectedException \Doctrine\OrientDB\Binding\Exception\InvalidDatabaseException
     */
    public function will_throw_exception_for_nonexistent_database() {
        $binding = self::createHttpBinding();
        $binding->deleteDatabase(TEST_ODB_DATABASE . '_temporary');
    }

    public function testCommandMethod() {
        $binding = self::createHttpBinding();

        $this->assertHttpStatus(200, $binding->command('SELECT FROM Address'), 'Execute a simple select');
        $this->assertHttpStatus(200, $binding->command("SELECT FROM City WHERE name = 'Rome'"), 'Execute a select with WHERE condition');
        $this->assertHttpStatus(200, $binding->command('SELECT FROM City WHERE name = "Rome"'), 'Execute another select with WHERE condition');
        $this->assertHttpStatus(500, $binding->command('INVALID SQL'), 'Execute a wrong SQL command');
        # HTTPTODO: status code should be 400 or 404
    }

    public function testQueryMethod() {
        $binding = self::createHttpBinding();

        $this->assertHttpStatus(200, $binding->query('SELECT FROM Address'), 'Executes a SELECT');
        $this->assertHttpStatus(200, $binding->query('SELECT FROM Address', null, 10), 'Executes a SELECT with LIMIT');
        $this->assertHttpStatus(500, $binding->query("UPDATE Profile SET online = false"), 'Tries to execute an UPDATE with the query command');
    }

    public function testSettingAuthentication() {
        $adapter = $this->getMock('Doctrine\OrientDB\Binding\Adapter\HttpClientAdapterInterface');
        $adapter->expects($this->at(1))
                ->method('setAuthentication')
                ->with(null, null);
        $adapter->expects($this->at(2))
                ->method('setAuthentication')
                ->with(TEST_ODB_USER, TEST_ODB_PASSWORD);

        $parameters = new BindingParameters();
        $binding    = new HttpBinding($parameters, $adapter);

        $binding->setAuthentication();
        $binding->setAuthentication(TEST_ODB_USER, TEST_ODB_PASSWORD);
    }

    public function testInjectHttpClientAdapter() {
        $adapter = $this->getMock('Doctrine\OrientDB\Binding\Adapter\HttpClientAdapterInterface');

        $parameters = new BindingParameters();
        $binding    = new HttpBinding($parameters, $adapter);

        $this->assertSame($adapter, $binding->getAdapter());
    }

    /**
     * @expectedException \Doctrine\OrientDB\OrientDBException
     */
    public function testResolveDatabase() {
        $adapter = $this->getMock('Doctrine\OrientDB\Binding\Adapter\HttpClientAdapterInterface');

        $parameters = new BindingParameters();
        $binding    = new HttpBinding($parameters, $adapter);

        $binding->deleteClass('MyClass');
    }

    public function testDocumentMethods() {
        $binding = self::createHttpBinding();

        $this->assertHttpStatus(500, $binding->getDocument('991'), 'Retrieves a document with an invalid RID');
        $this->assertHttpStatus(404, $binding->getDocument('9:10000'), 'Retrieves a non existing document');
        $this->assertHttpStatus(500, $binding->getDocument('999:0'), 'Retrieves a document from a non existing cluster');
        $this->assertHttpStatus(200, $binding->getDocument('1:0'), 'Retrieves a valid document');
    }

    public function testCreateDocument() {
        $binding = self::createHttpBinding();

        $document = json_encode(array('@class' => 'Address', 'name' => 'Pippo'));

        $creation = $binding->postDocument($document);

        $this->assertHttpStatus(201, $creation, 'Creates a valid document');
        $body = str_replace('#', '', $creation->getInnerResponse()->getBody());

        $decode = json_decode($body, true);

        return $decode['@rid'];
    }

    /**
     * @depends testCreateDocument
     * @param $rid
     */
    public function testDocumentExists($rid) {
        $binding = self::createHttpBinding();

        $binding->getAdapter()->getClient()->restart();

        $res = $binding->documentExists($rid);
        $this->assertTrue($res);
    }

    /**
     * @depends testCreateDocument
     */
    public function testUpdateAnExistingRecord($rid) {
        $binding = self::createHttpBinding();

        $binding->getAdapter()->getClient()->restart();

        $_document = json_decode($binding->getDocument($rid)->getInnerResponse()->getBody(), true);
        $document  = json_encode(array('@rid' => $rid, '@class' => 'Address', 'name' => 'Test', '@version' => $_document['@version']));
        $putResult = $binding->putDocument($rid, $document);

        $this->assertEquals(200, $putResult->getInnerResponse()->getStatusCode(), "Wrong Status Code");
        $document = json_encode(array('@rid' => 898989, '@class' => 'Address', 'name' => 'Test', '@version' => $_document['@version']));
        $this->assertHttpStatus(500, $binding->putDocument('9991', $document), 'Updates an invalid document');

        return $rid;
    }

    /**
     * @depends testUpdateAnExistingRecord
     */
    public function testDeleteADocument($rid) {
        $binding = self::createHttpBinding();

        $binding->getAdapter()->getClient()->restart();

        $this->assertHttpStatus(204, $binding->deleteDocument($rid), 'Deletes a valid document');
        $this->assertHttpStatus(404, $binding->deleteDocument('999:1'), 'Deletes a non existing document');
        $this->assertHttpStatus(500, $binding->deleteDocument('9991'), 'Deletes an invalid document');

    }

    /**
     * @depends testDeleteADocument
     * @param $rid
     */
    public function testDocumentDoesNotExist($rid) {
        $binding = self::createHttpBinding();

        $binding->getAdapter()->getClient()->restart();

        $res = $binding->documentExists($rid);
        $this->assertFalse($res);
    }

    public function testGetDatabaseName() {
        $binding = self::createHttpBinding();
        $this->assertEquals(TEST_ODB_DATABASE, $binding->getDatabaseName());
    }

    /**
     * @see https://github.com/Reinmar/Orient/commit/6110c61778cd7592f4c1e4f5530ea84e79c0f9cd
     */
    public function testFetchPlansAreProperlyEncoded() {
        $host     = TEST_ODB_HOST;
        $port     = TEST_ODB_HTTP_PORT;
        $database = TEST_ODB_DATABASE;

        $adapter = $this->getMock('Doctrine\OrientDB\Binding\Adapter\HttpClientAdapterInterface');
        $adapter->expects($this->once())
                ->method('request')
                ->with('GET', "http://$host:$port/query/$database/sql/SELECT%20OMNOMNOMN/2/%2A%3A1%20field1%3A3", null, null);

        $parameters = new BindingParameters($host, $port, null, null, $database);
        $binding    = new HttpBinding($parameters, $adapter);

        $binding->query("SELECT OMNOMNOMN", 2, "*:1 field1:3");
    }

    public function testOptionalDatabaseArgumentDoesNotSwitchCurrentDatabase() {
        $host     = TEST_ODB_HOST;
        $port     = TEST_ODB_HTTP_PORT;
        $database = TEST_ODB_DATABASE;

        $adapter = $this->getMock('Doctrine\OrientDB\Binding\Adapter\HttpClientAdapterInterface');
        $adapter->expects($this->at(0))
                ->method('request')
                ->with('POST', "http://$host:$port/command/$database/sql/SELECT%201", null, null);
        $adapter->expects($this->at(1))
                ->method('request')
                ->with('POST', "http://$host:$port/command/HIJACKED/sql/SELECT%202", null, null);
        $adapter->expects($this->at(2))
                ->method('request')
                ->with('POST', "http://$host:$port/command/$database/sql/SELECT%203", null, null);

        $parameters = new BindingParameters(TEST_ODB_HOST, TEST_ODB_HTTP_PORT, TEST_ODB_USER, TEST_ODB_PASSWORD, TEST_ODB_DATABASE);
        $binding    = new HttpBinding($parameters);
        $binding->setAdapter($adapter);

        $binding->command('SELECT 1');
        $binding->command('SELECT 2', HttpBinding::LANGUAGE_SQLPLUS, "HIJACKED");
        $binding->command('SELECT 3');
    }
}
