<?php

/*
 * This file is part of the Orient package.
 *
 * (c) Alessandro Nadalin <alessandro.nadalin@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\ODM\OrientDB\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata as DoctrineMetadata;
use Doctrine\Instantiator\Instantiator;
use Doctrine\ODM\OrientDB\Mapping as DataMapper;

/**
 * Class ClassMetadata
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class ClassMetadata implements DoctrineMetadata
{
    /**
     * Identifies a link association
     */
    const LINK = 0x01;
    /**
     * Identifies a link list association
     */
    const LINK_LIST = 0x02;
    /**
     * Identifies a link set association
     */
    const LINK_SET = 0x04;
    /**
     * Identifies a link map association
     */
    const LINK_MAP = 0x08;

    /**
     * Identifies a embedded association
     */
    const EMBED = 0x10;
    /**
     * Identifies a embedded list association
     */
    const EMBED_LIST = 0x20;
    /**
     * Identifies a embedded set association
     */
    const EMBED_SET = 0x40;
    /**
     * Identifies a embedded map association
     */
    const EMBED_MAP = 0x80;

    /**
     * Combined bit mask for single-valued associations.
     */
    const TO_ONE = 0x11;

    /**
     * Combined bit mask for collection-valued associations.
     */
    const TO_MANY = 0xEE;

    /**
     * READ-ONLY: The name of the OrientDB class to which this document is mapped
     *
     * @var string
     */
    public $orientClass;

    /**
     * READ-ONLY: The name of the entity class.
     *
     * @var string
     */
    public $name;

    /**
     * READ-ONLY: The name of the entity class that is at the root of the mapped entity inheritance
     * hierarchy. If the entity is not part of a mapped inheritance hierarchy this is the same
     * as {@see $name}.
     *
     * @var string
     */
    public $rootDocumentName;

    /**
     * READ-ONLY: Whether this class describes the mapping of a embedded document.
     *
     * @var boolean
     */
    public $isEmbeddedDocument = false;

    /**
     * The name of the custom repository class used for the document class.
     * (Optional).
     *
     * @var string
     */
    public $customRepositoryClassName;

    /**
     * @var string identifier property name
     */
    public $identifier;

    /**
     * @var \ReflectionClass
     */
    protected $reflClass;

    /**
     * @var \ReflectionProperty[]
     */
    protected $reflFields;

    /**
     * READONLY
     *
     * @var array
     */
    public $fieldMappings = [];

    public $associationMappings;

    /**
     * @var callable
     */
    protected $setter;

    /**
     * @var callable
     */
    protected $getter;

    /**
     * @var Instantiator
     */
    private $instantiator;

    /**
     * Instantiates a new Metadata for the given $className.
     *
     * @param string $className
     */
    public function __construct($className) {
        $this->name   = $className;
        $this->setter = function ($document, $property, $value) {
            $document->$property = $value;
        };

        $this->getter       = function ($document, $property) {
            return $document->$property;
        };
        $this->instantiator = new Instantiator();
    }

    /**
     * @inheritdoc
     */
    public function getName() {
        return $this->name;
    }

    public function setIdentifier($propertyName) {
        $this->identifier = $propertyName;
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier() {
        return [$this->identifier];
    }

    /**
     * RID property name
     *
     * @return string
     */
    public function getRidPropertyName() {
        return $this->identifier;
    }

    /**
     * @inheritdoc
     */
    public function getReflectionClass() {
        return $this->reflClass;
    }

    /**
     * @inheritdoc
     */
    public function isIdentifier($fieldName) {
        return ($fieldName === '@rid');
    }

    /**
     * @inheritdoc
     */
    public function hasField($fieldName) {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * @inheritdoc
     */
    public function hasAssociation($fieldName) {
        return isset($this->associationMappings[$fieldName]);
    }

    /**
     * @inheritdoc
     */
    public function isSingleValuedAssociation($fieldName) {
        return isset($this->associationMappings[$fieldName])
        && ($this->associationMappings[$fieldName]['association'] & self::TO_ONE);
    }

    /**
     * @inheritdoc
     */
    public function isCollectionValuedAssociation($fieldName) {
        return isset($this->associationMappings[$fieldName])
        && !($this->associationMappings[$fieldName]['association'] & self::TO_ONE);
    }

    /**
     * @inheritdoc
     */
    public function getFieldNames() {
        return array_keys($this->fieldMappings);
    }

    /**
     * @inheritdoc
     */
    public function getAssociationNames() {
        return array_keys($this->associationMappings);
    }

    /**
     * @inheritdoc
     */
    public function getTypeOfField($fieldName) {
        return isset($this->fieldMappings[$fieldName])
            ? $this->fieldMappings[$fieldName]['type']
            : null;
    }

    /**
     * @inheritDoc
     */
    public function getAssociationTargetClass($assocName) {
        if (!isset($this->associationMappings[$assocName])) {
            throw new \InvalidArgumentException("association name expected, '" . $assocName . "' is not an association.");
        }

        return $this->associationMappings[$assocName]['targetClass'];
    }

    /**
     * @return \ReflectionProperty[]
     */
    public function getReflectionProperties() {
        if (!$this->reflFields) {
            $this->discoverReflectionFields();
        }

        return $this->reflFields;
    }

    protected function discoverReflectionFields() {
        $this->reflFields = array();
        foreach ($this->getReflectionClass()->getProperties() as $property) {
            if (in_array($property->name, $this->getIdentifierFieldNames())) {
                $property->setAccessible(true);
            }
            $this->reflFields[$property->getName()] = $property;
        }
    }

    /**
     * @inheritdoc
     */
    public function getIdentifierFieldNames() {
        return [$this->identifier];
    }

    /**
     * @inheritdoc
     */
    public function isAssociationInverseSide($assocName) {
        throw new \Exception('to be implemented');
    }

    /**
     * @inheritdoc
     */
    public function getAssociationMappedByTargetField($assocName) {
        throw new \Exception('to be implemented');
    }

    /**
     * @inheritdoc
     */
    public function getIdentifierValues($object) {
        return [$this->getFieldValue($object, $this->identifier)];
    }

    /**
     * Returns the RID for the specified document
     *
     * @param object $document
     *
     * @return string
     */
    public function getIdentifierValue($document) {
        return $this->getFieldValue($document, $this->identifier);
    }

    /**
     * Adds a mapping for a field
     *
     * @param array $mapping The mapping.
     *
     * @return $this
     * @throws MappingException if the fieldName has already been mapped
     */
    public function mapField(array $mapping) {
        $fieldMame = $mapping['fieldName'];
        $this->assertFieldNotMapped($fieldMame);

        $namespace = $this->reflClass->getNamespaceName();
        if (isset($mapping['targetClass']) && strpos($mapping['targetClass'], '\\') === false && strlen($namespace)) {
            $mapping['targetClass'] = $namespace . '\\' . $mapping['targetClass'];
        }

        $cascades = isset($mapping['cascade']) ? array_map('strtolower', (array)$mapping['cascade']) : [];

        if (in_array('all', $cascades) || isset($mapping['embedded'])) {
            $cascades = ['remove', 'persist', 'refresh', 'merge', 'detach'];
        }

        if (isset($mapping['embedded'])) {
            unset($mapping['cascade']);
        } elseif (isset($mapping['cascade'])) {
            $mapping['cascade'] = $cascades;
        }

        $mapping['isCascadeRemove']  = in_array('remove', $cascades);
        $mapping['isCascadePersist'] = in_array('persist', $cascades);
        $mapping['isCascadeRefresh'] = in_array('refresh', $cascades);
        $mapping['isCascadeMerge']   = in_array('merge', $cascades);
        $mapping['isCascadeDetach']  = in_array('detach', $cascades);

        $mapping['isOwningSide']  = true;
        $mapping['isInverseSide'] = false;
        if (isset($mapping['reference'])) {
            if (isset($mapping['inversedBy']) && $mapping['inversedBy']) {
                $mapping['isOwningSide']  = true;
                $mapping['isInverseSide'] = false;
            }
            if (isset($mapping['mappedBy']) && $mapping['mappedBy']) {
                $mapping['isOwningSide']  = false;
                $mapping['isInverseSide'] = true;
            }
            if (!isset($mapping['orphanRemoval'])) {
                $mapping['orphanRemoval'] = false;
            }
        }

        $this->fieldMappings[$mapping['fieldName']] = $mapping;
        if (isset($mapping['association'])) {
            $this->associationMappings[$fieldMame] = $mapping;
        }

        return $this;
    }

    /**
     * Adds a mapping for a link
     *
     * @param array $mapping The mapping.
     *
     * @return $this
     * @throws MappingException if the fieldName has already been mapped
     */
    public function mapLink(array $mapping) {
        $mapping['type']        = 'link';
        $mapping['association'] = self::LINK;
        $mapping['reference']   = true;
        $this->mapField($mapping);

        return $this;
    }

    /**
     * Adds a mapping for a link list or array
     *
     * @param array $mapping The mapping.
     *
     * @return $this
     * @throws MappingException if the fieldName has already been mapped
     */
    public function mapLinkList(array $mapping) {
        $mapping['type']        = 'link_list';
        $mapping['association'] = self::LINK_LIST;
        $mapping['reference']   = true;
        $this->mapField($mapping);

        return $this;
    }

    /**
     * Adds a mapping for a link set
     *
     * @param array $mapping The mapping.
     *
     * @return $this
     * @throws MappingException if the fieldName has already been mapped
     */
    public function mapLinkSet(array $mapping) {
        $mapping['type']        = 'link_set';
        $mapping['association'] = self::LINK_SET;
        $mapping['reference']   = true;
        $this->mapField($mapping);

        return $this;
    }

    /**
     * Adds a mapping for a link map
     *
     * @param array $mapping The mapping.
     *
     * @return $this
     * @throws MappingException if the fieldName has already been mapped
     */
    public function mapLinkMap(array $mapping) {
        $mapping['type']        = 'link_map';
        $mapping['association'] = self::LINK_MAP;
        $mapping['reference']   = true;
        $this->mapField($mapping);

        return $this;
    }

    /**
     * Adds a mapping for an embedded type
     *
     * @param array $mapping
     *
     * @return $this
     * @throws MappingException if the fieldName has already been mapped
     */
    public function mapEmbedded(array $mapping) {
        $mapping['type']        = 'embedded';
        $mapping['association'] = self::EMBED;
        $mapping['embedded']    = true;
        $this->mapField($mapping);

        return $this;
    }

    /**
     * Adds a mapping for an embedded list
     *
     * @param array $mapping
     *
     * @return $this
     * @throws MappingException if the fieldName has already been mapped
     */
    public function mapEmbeddedList(array $mapping) {
        $mapping['type']        = 'embedded_list';
        $mapping['association'] = self::EMBED_LIST;
        $mapping['embedded']    = true;
        $this->mapField($mapping);

        return $this;
    }

    /**
     * Adds a mapping for an embedded set
     *
     * @param array $mapping
     *
     * @return $this
     * @throws MappingException if the fieldName has already been mapped
     */
    public function mapEmbeddedSet(array $mapping) {
        $mapping['type']        = 'embedded_set';
        $mapping['association'] = self::EMBED_SET;
        $mapping['embedded']    = true;
        $this->mapField($mapping);

        return $this;
    }

    /**
     * Adds a mapping for an embedded map
     *
     * @param array $mapping
     *
     * @return $this
     * @throws MappingException if the fieldName has already been mapped
     */
    public function mapEmbeddedMap(array $mapping) {
        $mapping['type']        = 'embedded_map';
        $mapping['association'] = self::EMBED_MAP;
        $mapping['embedded']    = true;
        $this->mapField($mapping);

        return $this;
    }

    public function setOrientClass($orientClass) {
        $this->orientClass = $orientClass;
    }

    public function getOrientClass() {
        return $this->orientClass;
    }

    /**
     * Given a $property and its $value, sets that property on the given $document
     *
     * @param mixed  $document
     * @param string $property
     * @param string $value
     */
    public function setFieldValue($document, $property, $value) {
        $p = $this->setter->bindTo(null, $document);
        $p($document, $property, $value);
    }

    /**
     * Gets the value of the specified $property
     *
     * @param mixed  $document
     * @param string $property
     */
    public function getFieldValue($document, $property) {
        $p = $this->getter->bindTo(null, $document);

        return $p($document, $property);
    }

    /**
     * Restores some state that can not be serialized/unserialized.
     *
     * @param \Doctrine\Common\Persistence\Mapping\ReflectionService $reflService
     */
    public function wakeupReflection($reflService) {

    }

    /**
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param \Doctrine\Common\Persistence\Mapping\ReflectionService $reflService The reflection service.
     */
    public function initializeReflection($reflService) {
        $this->reflClass = $reflService->getClass($this->name);

        if ($this->reflClass) {
            $this->name = $this->rootDocumentName = $this->reflClass->getName();
        }
    }

    /**
     * @param string $fieldName
     *
     * @throws MappingException
     */
    private function assertFieldNotMapped($fieldName) {
        if (isset($this->fieldMappings[$fieldName]) ||
            isset($this->associationMappings[$fieldName])
        ) {
            throw MappingException::duplicateFieldMapping($this->name, $fieldName);
        }
    }

    /**
     * Registers a custom repository class for the document class.
     *
     * @param string $repositoryClassName The class name of the custom repository.
     */
    public function setCustomRepositoryClass($repositoryClassName) {
        $namespace = $this->reflClass->getNamespaceName();
        if ($repositoryClassName && strpos($repositoryClassName, '\\') === false && strlen($namespace)) {
            $repositoryClassName = $namespace . '\\' . $repositoryClassName;
        }

        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     */
    public function newInstance() {
        return $this->instantiator->instantiate($this->name);
    }
}
