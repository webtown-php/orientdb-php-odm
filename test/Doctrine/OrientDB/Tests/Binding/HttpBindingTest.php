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

use Doctrine\OrientDB\Binding\Adapter\HttpClientAdapterInterface;
use Doctrine\OrientDB\Binding\BindingInterface;
use Doctrine\OrientDB\Binding\BindingParameters;
use Doctrine\OrientDB\Binding\Client\Http\CurlClientResponse;
use Doctrine\OrientDB\Binding\HttpBinding;
use Doctrine\OrientDB\Binding\HttpBindingResultInterface;
use PHPUnit\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @group integration
 */
class HttpBindingTest extends AbstractBindingInterfaceTest
{
    private function assertHttpStatus($expected, HttpBindingResultInterface $result, $message = null) {
        $response = $result->getInnerResponse();
        $status   = $response->getStatusCode();
        $message  = $message ?: $response->getBody();

        $this->assertSame($expected, $status, $message);
    }

    /**
     * @return BindingInterface
     */
    protected function getBinding() {
        return self::createHttpBinding();
    }

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
     *
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

        $_document = $binding->getDocument($rid);
        $document  = json_encode(array('@rid' => $rid, '@class' => 'Address', 'name' => 'Test', '@version' => $_document->{'@version'}));
        $putResult = $binding->putDocument($rid, $document);

        $this->assertEquals(200, $putResult->getInnerResponse()->getStatusCode(), "Wrong Status Code");
        $document = json_encode(array('@rid' => 898989, '@class' => 'Address', 'name' => 'Test', '@version' => $_document->{'@version'}));
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
     * @see https://github.com/Reinmar/Orient/commit/6110c61778cd7592f4c1e4f5530ea84e79c0f9cd
     */
    public function testFetchPlansAreProperlyEncoded() {
        $host     = TEST_ODB_HOST;
        $port     = TEST_ODB_HTTP_PORT;
        $database = TEST_ODB_DATABASE;

        /** @var HttpClientAdapterInterface|ObjectProphecy $adapter */
        $adapter = $this->prophesize(HttpClientAdapterInterface::class);

        $adapter->setAuthentication(null, null)
                ->shouldBeCalled();

        /** @var CurlClientResponse|ObjectProphecy $res */
        $res = $this->prophesize(CurlClientResponse::class);
        $res->getStatusCode()
            ->willReturn(200);

        /** @var HttpBindingResultInterface|ObjectProphecy $br */
        $br = $this->prophesize(HttpBindingResultInterface::class);
        $br->getInnerResponse()
           ->willReturn($res->reveal());

        $br->getData()
           ->willReturn(json_decode('{"result":{}}'));

        $adapter->request('GET', "http://$host:$port/query/$database/sql/SELECT%20OMNOMNOMN/2/%2A%3A1%20field1%3A3", null, null)
                ->willReturn($br->reveal());

        $parameters = new BindingParameters($host, $port, null, null, $database);
        $binding    = new HttpBinding($parameters, $adapter->reveal());

        $binding->query("SELECT OMNOMNOMN", 2, "*:1 field1:3");
    }
}
