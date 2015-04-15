<?php

/**
 * TestCase class bound to Doctrine\OrientDB.
 *
 * @author Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author Daniele Alessandri <suppakilla@gmail.com>
 */

namespace PHPUnit;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ODM\OrientDB\Configuration;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping;
use Doctrine\OrientDB\Binding\Adapter\CurlClientAdapter;
use Doctrine\OrientDB\Binding\BindingParameters;
use Doctrine\OrientDB\Binding\Client\Http\CurlClient;
use Doctrine\OrientDB\Binding\HttpBinding;
use Doctrine\OrientDB\Binding\HttpBindingInterface;
use Doctrine\OrientDB\Binding\HttpBindingResultInterface;
use Prophecy\Exception\Prediction\PredictionException;
use Prophecy\Prophet;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    const COLLECTION_CLASS = ArrayCollection::class;

    protected function getBindingParameters($options) {
        $parameters = array();

        array_walk($options, function ($value, $key) use (&$parameters) {
            if (0 === $pos = strpos($key, 'odb.')) {
                $parameters[substr($key, strpos($key, '.') + 1)] = $value;
            }
        });

        return BindingParameters::fromArray($parameters);
    }

    protected function createHttpBinding(array $opts = []) {
        $opts = array_merge(array(
            'http.adapter' => null,
            'http.restart' => false,
            'http.timeout' => TEST_ODB_TIMEOUT,
            'odb.host'     => TEST_ODB_HOST,
            'odb.port'     => TEST_ODB_PORT,
            'odb.username' => TEST_ODB_USER,
            'odb.password' => TEST_ODB_PASSWORD,
            'odb.database' => TEST_ODB_DATABASE,
        ), $opts);

        if (!isset($opts['adapter'])) {
            $client          = new CurlClient($opts['http.restart'], $opts['http.timeout']);
            $opts['adapter'] = new CurlClientAdapter($client);
        }

        $parameters = $this->getBindingParameters($opts);
        $binding    = new HttpBinding($parameters, $opts['adapter']);

        return $binding;
    }

    /**
     * @param String $className
     *
     * @return String
     */
    public function getClassId($className) {
        return $this->createHttpBinding()->getClass($className)->getData()->clusters[0];
    }


    protected function getProxyDirectory() {
        return __DIR__ . '/../../test/proxies/Doctrine/OrientDB/Proxy/test';
    }

    protected function getConfiguration(array $opts = []) {
        return new Configuration(array_merge(
            [
                'proxy_dir'                 => $this->getProxyDirectory(),
                'proxy_autogenerate_policy' => AbstractProxyFactory::AUTOGENERATE_ALWAYS,
            ],
            $opts
        ));
    }

    /**
     * @param array $opts
     * @param array $paths
     *
     * @return DocumentManager
     */
    protected function createDocumentManager(array $opts = [], $paths = []) {
        $parameters = new BindingParameters(TEST_ODB_HOST, TEST_ODB_PORT, TEST_ODB_USER, TEST_ODB_PASSWORD, TEST_ODB_DATABASE);
        $binding    = new HttpBinding($parameters);

        return $this->createDocumentManagerWithBinding($binding, $opts, $paths);
    }

    /**
     * @param HttpBindingInterface $binding
     * @param array                $opts
     * @param array                $paths
     *
     * @return DocumentManager
     */
    protected function createDocumentManagerWithBinding(HttpBindingInterface $binding, array $opts = [], $paths = []) {
        $config = $this->getConfiguration($opts);
        if (empty($paths)) {
            $paths = [__DIR__ . '/../Doctrine/ODM/OrientDB/Tests/Models/Standard'];
        }
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths));
        $config->setMetadataCacheImpl(new ArrayCache());

        return new DocumentManager($binding, $config);
    }

    protected function createAnnotationDriver($paths = null, $alias = null) {
        // Register the ORM Annotations in the AnnotationRegistry
        $reader = new \Doctrine\Common\Annotations\SimpleAnnotationReader();
        $reader->addNamespace('Doctrine\ODM\OrientDB\Mapping\Annotations');
        $reader = new \Doctrine\Common\Annotations\CachedReader($reader, new ArrayCache());

        \Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
            __DIR__ . '/../../src/Doctrine/ODM/OrientDB/Mapping/Annotations/DoctrineAnnotations.php');

        return new Mapping\Driver\AnnotationDriver($reader, (array)$paths);
    }

    public function assertHttpStatus($expected, HttpBindingResultInterface $result, $message = null) {
        $response = $result->getInnerResponse();
        $status   = $response->getStatusCode();
        $message  = $message ?: $response->getBody();

        $this->assertSame($expected, $status, $message);
    }

    public function assertCommandGives($expected, $got) {
        $this->assertEquals($expected, $got, 'The raw command does not match the given SQL query');
    }

    public function assertTokens($expected, $got) {
        $this->assertEquals($expected, $got, 'The given command tokens do not match');
    }

    /**
     * @var Prophet
     */
    private $prophet;

    /**
     * @param string|null $classOrInterface
     *
     * @return \Prophecy\Prophecy\ObjectProphecy
     * @throws \LogicException
     */
    protected function prophesize($classOrInterface = null) {
        if (null === $this->prophet) {
            throw new \LogicException(sprintf('The setUp method of %s must be called to initialize Prophecy.', __CLASS__));
        }

        return $this->prophet->prophesize($classOrInterface);
    }

    protected function setUp() {
        $this->prophet = new Prophet();
    }

    protected function assertPostConditions() {
        if ($this->prophet) {
            $this->prophet->checkPredictions();
        }
    }

    protected function tearDown() {
        $this->prophet = null;
    }

    protected function onNotSuccessfulTest(\Exception $e) {
        if ($e instanceof PredictionException) {
            $e = new \PHPUnit_Framework_AssertionFailedError($e->getMessage(), $e->getCode(), $e);
        }

        return parent::onNotSuccessfulTest($e);
    }
}
