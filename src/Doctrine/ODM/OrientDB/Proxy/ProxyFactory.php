<?php

namespace Doctrine\ODM\OrientDB\Proxy;


use Doctrine\Common\Persistence\Mapping\ClassMetadata as BaseClassMetadata;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\Common\Proxy\Proxy as BaseProxy;
use Doctrine\Common\Proxy\ProxyDefinition;
use Doctrine\Common\Proxy\ProxyGenerator;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\DocumentNotFoundException;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Persisters\DocumentPersister;

/**
 * Class ProxyFactory
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class ProxyFactory extends AbstractProxyFactory
{

    /**
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var \Doctrine\ODM\OrientDB\UnitOfWork The UnitOfWork this factory is bound to.
     */
    private $uow;

    /**
     * @var string The namespace that contains all proxy classes.
     */
    private $proxyNamespace;

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>DocumentManager</tt>.
     *
     * @param DocumentManager $manager
     * @param string          $proxyDir                              The directory to use for the proxy classes. It
     *                                                               must exist.
     * @param string          $proxyNamespace                        The namespace to use for the proxy classes.
     * @param int             $autoGenerate                          Whether to automatically generate proxy classes.
     */
    public function __construct(DocumentManager $manager, $proxyDir, $proxyNamespace, $autoGenerate = AbstractProxyFactory::AUTOGENERATE_NEVER) {
        $this->metadataFactory = $manager->getMetadataFactory();
        $this->uow             = $manager->getUnitOfWork();
        $this->proxyNamespace  = $proxyNamespace;
        $proxyGenerator        = new ProxyGenerator($proxyDir, $proxyNamespace);
        $proxyGenerator->setPlaceholder('baseProxyInterface', 'Doctrine\ODM\OrientDB\Proxy\Proxy');
        parent::__construct($proxyGenerator, $this->metadataFactory, $autoGenerate);
    }

    public function skipClass(BaseClassMetadata $classMetadata) {
        return false;
    }

    public function createProxyDefinition($className) {
        /** @var ClassMetadata $classMetadata */
        $classMetadata    = $this->metadataFactory->getMetadataFor($className);
        $documentPersister = $this->uow->getDocumentPersister($className);
        $reflectionFields = $classMetadata->getReflectionProperties();
        $reflectionId     = $reflectionFields[$classMetadata->getRidPropertyName()];

        return new ProxyDefinition(
            ClassUtils::generateProxyClassName($className, $this->proxyNamespace),
            $classMetadata->getIdentifierFieldNames(),
            $reflectionFields,
            $this->createInitializer($classMetadata, $documentPersister, $reflectionId),
            $this->createCloner($classMetadata, $documentPersister, $reflectionId)
        );
    }

    /**
     * Generates a closure capable of initializing a proxy
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param DocumentPersister                                  $documentPersister
     * @param \ReflectionProperty                                $reflectionId
     *
     * @return callable
     */
    private function createInitializer(
        BaseClassMetadata $classMetadata,
        DocumentPersister $documentPersister,
        \ReflectionProperty $reflectionId
    ) {
        if ($classMetadata->getReflectionClass()->hasMethod('__wakeup')) {
            return function (BaseProxy $proxy) use ($reflectionId, $documentPersister) {
                $proxy->__setInitializer(null);
                $proxy->__setCloner(null);
                if ($proxy->__isInitialized()) {
                    return;
                }
                $properties = $proxy->__getLazyProperties();
                foreach ($properties as $propertyName => $property) {
                    if (!isset($proxy->$propertyName)) {
                        $proxy->$propertyName = $properties[$propertyName];
                    }
                }
                $proxy->__setInitialized(true);
                $proxy->__wakeup();

                $rid    = $reflectionId->getValue($proxy);
                if ($documentPersister->load($rid, '*:0', $proxy) === null) {
                    throw DocumentNotFoundException::documentNotFound(get_class($proxy), $rid);
                }
            };
        }

        return function (BaseProxy $proxy) use ($reflectionId, $documentPersister) {
            $proxy->__setInitializer(null);
            $proxy->__setCloner(null);
            if ($proxy->__isInitialized()) {
                return;
            }
            $properties = $proxy->__getLazyProperties();
            foreach ($properties as $propertyName => $property) {
                if (!isset($proxy->$propertyName)) {
                    $proxy->$propertyName = $properties[$propertyName];
                }
            }
            $proxy->__setInitialized(true);

            $rid    = $reflectionId->getValue($proxy);
            if ($documentPersister->load($rid, '*:0', $proxy) === null) {
                throw DocumentNotFoundException::documentNotFound(get_class($proxy), $rid);
            }
        };
    }

    /**
     * Generates a closure capable of finalizing a cloned proxy
     *
     * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata
     * @param DocumentPersister                                  $documentPersister
     * @param \ReflectionProperty                                $reflectionId
     *
     * @return callable
     */
    private function createCloner(
        BaseClassMetadata $classMetadata,
        DocumentPersister $documentPersister,
        \ReflectionProperty $reflectionId
    ) {
        return function (BaseProxy $proxy) use ($reflectionId, $documentPersister, $classMetadata) {
            if ($proxy->__isInitialized()) {
                return;
            }
            $proxy->__setInitialized(true);
            $proxy->__setInitializer(null);
            $rid      = $reflectionId->getValue($proxy);
            $original = $documentPersister->load($rid);

            if (null === $original) {
                throw DocumentNotFoundException::documentNotFound(get_class($proxy), $rid);
            }

            foreach ($classMetadata->getReflectionClass()->getProperties() as $reflectionProperty) {
                $propertyName = $reflectionProperty->getName();
                if ($classMetadata->hasField($propertyName) || $classMetadata->hasAssociation($propertyName)) {
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($proxy, $reflectionProperty->getValue($original));
                }
            }
        };
    }
}