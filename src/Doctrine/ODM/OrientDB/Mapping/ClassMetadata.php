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
     * Identifies a link.
     */
    const LINK = 0x01;
    const LINK_LIST = 0x02;
    const LINK_SET = 0x04;
    const LINK_MAP = 0x08;

    /**
     * Combined bitmask for single-valued associations.
     */
    const TO_ONE = 0x01;

    /**
     * Combined bitmask for collection-valued associations.
     */
    const TO_MANY = 0x0E;

    protected $orientClass;

    /**
     * READ-ONLY: The name of the entity class.
     *
     * @var string
     */
    public $name;

    /**
     * READ-ONLY: The name of the entity class that is at the root of the mapped entity inheritance
     * hierarchy. If the entity is not part of a mapped inheritance hierarchy this is the same
     * as {@link $entityName}.
     *
     * @var string
     */
    public $rootEntityName;

    protected $reflClass;
    protected $reflFields;

    protected $identifierPropertyName;
    protected $associationMappings;
    protected $fields;

    /**
     * READONLY
     *
     * @var array
     */
    public $fieldMappings = [];

    protected $setter;
    protected $getter;

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

        $this->getter = function ($document, $property) {
            return $document->$property;
        };
    }

    /**
     * @inheritdoc
     */
    public function getName() {
        return $this->name;
    }

    public function setIdentifier($propertyName) {
        $this->identifierPropertyName = $propertyName;
    }

    /**
     * @inheritdoc
     */
    public function getIdentifier() {
        return [$this->identifierPropertyName];
    }

    /**
     * RID property name
     *
     * @return string
     */
    public function getRidPropertyName() {
        return $this->identifierPropertyName;
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
            && ($this->associationMappings[$fieldName]['type'] & self::TO_ONE);
    }

    /**
     * @inheritdoc
     */
    public function isCollectionValuedAssociation($fieldName) {
        return isset($this->associationMappings[$fieldName])
        && !($this->associationMappings[$fieldName]['type'] & self::TO_ONE);
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
     * @inheritdoc
     */
    public function getAssociationTargetClass($assocName) {
        return null;
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
        return [$this->identifierPropertyName];
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
        return $this->getFieldValue($object, $this->identifierPropertyName);
    }

    public function mapField(array $mapping) {
//        $this->_validateAndCompleteFieldMapping($mapping);
        $this->assertFieldNotMapped($mapping['fieldName']);

        $this->fieldMappings[$mapping['fieldName']] = $mapping;
    }

    /**
     * Adds a link mapping.
     *
     * @param array $mapping The mapping.
     */
    public function mapLink(array $mapping) {
        $mapping['type'] = self::LINK;
        //$mapping = $this->_validateAndCompleteOneToOneMapping($mapping);
        $this->_storeAssociationMapping($mapping);
    }

    /**
     * Adds a link-set mapping.
     *
     * @param array $mapping The mapping.
     */
    public function mapLinkList(array $mapping) {
        $mapping['type'] = self::LINK_LIST;
        //$mapping = $this->_validateAndCompleteOneToOneMapping($mapping);
        $this->_storeAssociationMapping($mapping);
    }

    /**
     * Adds a link-set mapping.
     *
     * @param array $mapping The mapping.
     */
    public function mapLinkSet(array $mapping) {
        $mapping['type'] = self::LINK_SET;
        //$mapping = $this->_validateAndCompleteOneToOneMapping($mapping);
        $this->_storeAssociationMapping($mapping);
    }

    /**
     * Adds a link-set mapping.
     *
     * @param array $mapping The mapping.
     */
    public function mapLinkMap(array $mapping) {
        $mapping['type'] = self::LINK_MAP;
        //$mapping = $this->_validateAndCompleteOneToOneMapping($mapping);
        $this->_storeAssociationMapping($mapping);
    }

    /**
     * Stores the association mapping.
     *
     * @param array $assocMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    protected function _storeAssociationMapping(array $assocMapping) {
        $sourceFieldName = $assocMapping['fieldName'];
        $this->assertFieldNotMapped($sourceFieldName);
        $this->associationMappings[$sourceFieldName] = $assocMapping;
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
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param \Doctrine\Common\Persistence\Mapping\ReflectionService $reflService The reflection service.
     *
     * @return void
     */
    public function initializeReflection($reflService) {
        $this->reflClass = $reflService->getClass($this->name);

        if ($this->reflClass) {
            $this->name = $this->rootEntityName = $this->reflClass->getName();
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

}
