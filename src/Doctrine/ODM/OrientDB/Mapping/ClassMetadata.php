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
    # region class attributes

    const CA_NONE = 0x0000;

    /**
     * class is a document, represented by an associated OrientDB class
     */
    const CA_TYPE_DOCUMENT = 0x0001;

    /**
     * class is a mapped superclass
     */
    const CA_TYPE_MAPPED_SUPERCLASS = 0x0002;

    /**
     * class is an embedded document
     */
    const CA_TYPE_EMBEDDED = 0x004;

    /**
     * class inherits from vertex, and supports edge relationships
     */
    const CA_IS_VERTEX = 0x0010;

    /**
     * class inherits from edge and supports relating vertexes
     */
    const CA_IS_EDGE = 0x0020;

    /**
     * class is abstract, and cannot be persisted directly to OrientDB, but will have an associated class
     */
    const CA_IS_ABSTRACT = 0x0040;

    /**
     * mask for all document type flags, which are mutually exclusive
     */
    const CA_MASK_DOC_TYPE = 0x0007;

    /**
     * mask indicating this class inherits from an OrientDB graph class
     */
    const CA_MASK_GRAPH_SUPPORT = 0x0030;

    #endregion

    /**
     * prefix for the field that maps outgoing connections of a vertex
     */
    const CONNECTION_OUT_PREFIX = 'out_';

    /**
     * prefix for the field that maps incoming connections of a vertex
     */
    const CONNECTION_IN_PREFIX = 'in_';

    /**
     * Base OrientDB class for all vertex documents
     */
    const VERTEX_BASE_CLASS = 'V';

    /**
     * Base OrientDB class for all edge documents
     */
    const EDGE_BASE_CLASS = 'E';

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
     * Identifies a link bag association specifically for graph edges
     */
    const LINK_BAG_EDGE = 0x10;

    /**
     * Identifies a embedded association
     */
    const EMBED = 0x100;
    /**
     * Identifies a embedded list association
     */
    const EMBED_LIST = 0x200;
    /**
     * Identifies a embedded set association
     */
    const EMBED_SET = 0x400;
    /**
     * Identifies a embedded map association
     */
    const EMBED_MAP = 0x800;

    /**
     * Identifies associations that must use key
     */
    const ASSOCIATION_USE_KEY = 0xA1A;

    /**
     * Combined bit mask for single-valued associations.
     */
    const TO_ONE = 0x101;

    /**
     * Combined bit mask for collection-valued associations.
     */
    const TO_MANY = 0xE1E;

    /**
     * Bit mask for linked collection associations
     */
    const LINK_MANY = 0x01E;

    /**
     * Bit mask for embedded collection associations
     */
    const EMBED_MANY = 0xE00;

    /**
     * DEFERRED_IMPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done for all entities that are in MANAGED state at commit-time.
     *
     * This is the default change tracking policy.
     */
    const CHANGETRACKING_DEFERRED_IMPLICIT = 1;

    /**
     * DEFERRED_EXPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done only for entities that were explicitly saved (through persist() or a cascade).
     */
    const CHANGETRACKING_DEFERRED_EXPLICIT = 2;

    /**
     * NOTIFY means that Doctrine relies on the entities sending out notifications
     * when their properties change. Such entity classes must implement
     * the <tt>NotifyPropertyChanged</tt> interface.
     */
    const CHANGETRACKING_NOTIFY = 3;

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
     * READ-ONLY: Various flags
     *
     * @var int
     */
    public $attributes = self::CA_NONE;

    /**
     * READ-ONLY: The names of the parent classes (ancestors).
     *
     * @var array
     */
    public $parentClasses = [];

    /**
     * READ-ONLY: The policy used for change-tracking on entities of this class.
     *
     * @var integer
     */
    public $changeTrackingPolicy = self::CHANGETRACKING_DEFERRED_IMPLICIT;

    /**
     * The name of the custom repository class used for the document class.
     * (Optional).
     *
     * @var string
     */
    public $customRepositoryClassName;

    /**
     * @var string property name of identifier
     */
    public $identifier;

    /**
     * @var string property name of version
     */
    public $version;

    /**
     * @var \ReflectionClass
     */
    public $reflClass;

    /**
     * @var \ReflectionProperty[]
     */
    public $reflFields;

    /**
     * READ-ONLY
     *
     * @var array
     */
    public $fieldMappings = [];

    /**
     * READ-ONLY
     *
     * @var array
     */
    public $associationMappings = [];

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
        $this->getter = function ($document, $property) {
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
     * Set the @version field for this class
     *
     * @param string $propertyName
     */
    public function setVersion($propertyName) {
        $this->version = $propertyName;
    }

    /**
     * Returns the @version property name
     *
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }

    /**
     * @return bool
     */
    public function isDocument() {
        return ($this->attributes & self::CA_TYPE_DOCUMENT) !== 0;
    }

    /**
     * Set the document attribute flag for this mapping
     *
     * @return $this
     */
    public function setIsDocument() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_DOC_TYPE) | self::CA_TYPE_DOCUMENT;

        return $this;
    }

    /**
     * Clear the document attribute flag for this mapping
     *
     * @return $this
     */
    public function clearIsDocument() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_DOC_TYPE) & ~self::CA_TYPE_DOCUMENT;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMappedSuperclass() {
        return ($this->attributes & self::CA_TYPE_MAPPED_SUPERCLASS) !== 0;
    }

    /**
     * Set the mapped superclass attribute flag for this mapping
     *
     * @return $this
     */
    public function setIsMappedSuperclass() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_DOC_TYPE) | self::CA_TYPE_MAPPED_SUPERCLASS;

        return $this;
    }

    /**
     * Clear the mapped superclass attribute flag for this mapping
     *
     * @return $this
     */
    public function clearIsMappedSuperclass() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_DOC_TYPE) & ~self::CA_TYPE_MAPPED_SUPERCLASS;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmbeddedDocument() {
        return ($this->attributes & self::CA_TYPE_EMBEDDED) !== 0;
    }

    /**
     * Set the embeddd attribute flag for this mapping
     *
     * @return $this
     */
    public function setIsEmbeddedDocument() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_DOC_TYPE) | self::CA_TYPE_EMBEDDED;

        return $this;
    }

    /**
     * Clear the embedded attribute flag for this mapping
     *
     * @return $this
     */
    public function clearIsEmbeddedDocument() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_DOC_TYPE) & ~self::CA_TYPE_EMBEDDED;

        return $this;
    }

    /**
     * @return bool
     */
    public function isVertex() {
        return ($this->attributes & self::CA_IS_VERTEX) !== 0;
    }

    /**
     * Set the vertex attribute flag for this mapping
     *
     * @return $this
     */
    public function setIsVertex() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_GRAPH_SUPPORT) | self::CA_IS_VERTEX;

        return $this;
    }

    /**
     * Clear the vertex attribute flag for this mapping
     *
     * @return $this
     */
    public function clearIsVertex() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_GRAPH_SUPPORT) & ~self::CA_IS_VERTEX;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEdge() {
        return ($this->attributes & self::CA_IS_EDGE) !== 0;
    }

    /**
     * Set the edge attribute flag for this mapping
     *
     * @return $this
     */
    public function setIsEdge() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_GRAPH_SUPPORT) | self::CA_IS_EDGE;

        return $this;
    }

    /**
     * Clear the edge attribute flag for this mapping
     *
     * @return $this
     */
    public function clearIsEdge() {
        $this->attributes = ($this->attributes & ~self::CA_MASK_GRAPH_SUPPORT) & ~self::CA_IS_EDGE;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAbstract() {
        return ($this->attributes & self::CA_IS_ABSTRACT) !== 0;
    }

    /**
     * Set the abstract attribute flag for this mapping
     *
     * @return $this
     */
    public function setIsAbstract() {
        $this->attributes = $this->attributes | self::CA_IS_ABSTRACT;

        return $this;
    }

    /**
     * Clear the abstract attribute flag for this mapping
     *
     * @return $this
     */
    public function clearIsAbstract() {
        $this->attributes = $this->attributes & ~self::CA_IS_ABSTRACT;

        return $this;
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
        return $fieldName === $this->identifier;
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

        return $this->associationMappings[$assocName]['targetDoc'];
    }

    /**
     * Sets the change tracking policy used by this class.
     *
     * @param integer $policy
     */
    public function setChangeTrackingPolicy($policy) {
        $this->changeTrackingPolicy = $policy;
    }

    /**
     * Whether the change tracking policy of this class is "deferred explicit".
     *
     * @return bool
     */
    public function isChangeTrackingDeferredExplicit() {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_DEFERRED_EXPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "deferred implicit".
     *
     * @return bool
     */
    public function isChangeTrackingDeferredImplicit() {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_DEFERRED_IMPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "notify".
     *
     * @return bool
     */
    public function isChangeTrackingNotify() {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_NOTIFY;
    }

    /**
     * @return \ReflectionProperty[]
     */
    public function getReflectionProperties() {
        return $this->reflFields;
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
     * Checks whether a mapped field is inherited from an entity superclass.
     *
     * @param string $fieldName
     *
     * @return bool TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName) {
        return isset($this->fieldMappings[$fieldName]['inherited']);
    }

    /**
     * Checks if this document is the root in a document hierarchy
     *
     * @return bool
     */
    public function isRootDocument() {
        return $this->name === $this->rootDocumentName;
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
        $this->_validateFieldMapping($mapping);
        $this->_mapField($mapping);

        return $this;
    }

    public function mapRid($fieldName) {
        $mapping = [
            'fieldName' => $fieldName,
            'name'      => '@rid',
            'type'      => 'string',
            'nullable'  => false,
            'notSaved'  => true,
        ];
        $this->_mapField($mapping);
        $this->identifier = $fieldName;
    }

    public function mapVersion($fieldName) {
        $mapping = [
            'fieldName' => $fieldName,
            'name'      => '@version',
            'type'      => 'integer',
            'nullable'  => false,
            'notSaved'  => true,
        ];
        $this->_mapField($mapping);
        $this->version = $fieldName;
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
        $this->_validateFieldMapping($mapping);
        $mapping['association'] = self::LINK;
        $mapping['reference']   = true;
        $this->_mapField($mapping);

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
        $this->_validateFieldMapping($mapping);
        $mapping['association'] = self::LINK_LIST;
        $mapping['reference']   = true;
        $this->_mapField($mapping);


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
        $this->_validateFieldMapping($mapping);
        $mapping['association'] = self::LINK_SET;
        $mapping['reference']   = true;
        $this->_mapField($mapping);


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
        $this->_validateFieldMapping($mapping);
        $mapping['association'] = self::LINK_MAP;
        $mapping['reference']   = true;
        $this->_mapField($mapping);


        return $this;
    }

    /**
     * Adds a mapping for a edge link bag, used for graph edges
     *
     * @param array $mapping
     *
     * @return $this
     * @throws MappingException
     */
    public function mapRelatedToLinkBag(array $mapping) {
        $this->_validateFieldMapping($mapping);

        $mapping['association']   = self::LINK_BAG_EDGE;
        $mapping['reference']     = true;
        $mapping['cascade']       = ['persist'];
        $mapping['orphanRemoval'] = !$mapping['indirect'];
        $suffix                   = $mapping['oclass'] !== self::EDGE_BASE_CLASS
            ? $mapping['oclass']
            : '';

        if (!isset($mapping['direction'])) {
            throw MappingException::relatedToRequiresDirection($this->name, $mapping['fieldName']);
        }

        $mapping['name'] = sprintf('%s%s', $mapping['direction'] === 'in' ? self::CONNECTION_IN_PREFIX : self::CONNECTION_OUT_PREFIX, $suffix);
        $this->_mapField($mapping);

        return $this;
    }

    public function mapVertexLink($mapping, $direction) {
        $mapping = array_merge([
            'name'        => $direction,
            'type'        => 'object',
            'nullable'    => false,
            'association' => self::LINK,
            'reference'   => true,
        ],
            $mapping
        );

        $this->_validateFieldMapping($mapping);
        $this->_mapField($mapping);
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
        $this->_validateFieldMapping($mapping);
        $mapping['association'] = self::EMBED;
        $mapping['embedded']    = true;
        $this->_mapField($mapping);

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
        $this->_validateFieldMapping($mapping);
        $mapping['association'] = self::EMBED_LIST;
        $mapping['embedded']    = true;
        $this->_mapField($mapping);

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
        $this->_validateFieldMapping($mapping);
        $mapping['association'] = self::EMBED_SET;
        $mapping['embedded']    = true;
        $this->_mapField($mapping);

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
        $this->_validateFieldMapping($mapping);
        $mapping['association'] = self::EMBED_MAP;
        $mapping['embedded']    = true;
        $this->_mapField($mapping);

        return $this;
    }

    /**
     * INTERNAL:
     * Adds a field mapping without completing/validating it.
     * This is mainly used to add inherited field mappings to derived classes.
     *
     * @param array $fieldMapping
     */
    public function addInheritedFieldMapping(array $fieldMapping) {
        $this->fieldMappings[$fieldMapping['fieldName']] = $fieldMapping;
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
        foreach ($this->fieldMappings as $field => $mapping) {
            $this->reflFields[$field] = isset($mapping['declared'])
                ? $reflService->getAccessibleProperty($mapping['declared'], $field)
                : $reflService->getAccessibleProperty($this->name, $field);
        }
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
     * Sets the parent class names.
     * Assumes that the class names in the passed array are in the order:
     * directParent -> directParentParent -> directParentParentParent ... -> root.
     *
     * @param array $classNames
     *
     * @return void
     */
    public function setParentClasses(array $classNames) {
        $this->parentClasses = $classNames;
        if (count($classNames) > 0) {
            $this->rootDocumentName = array_pop($classNames);
        }
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     */
    public function newInstance() {
        return $this->instantiator->instantiate($this->name);
    }

    /**
     * @param array $mapping
     *
     * @throws MappingException
     */
    private function _validateFieldMapping(array &$mapping) {
        if (!isset($mapping['fieldName'])) {
            throw MappingException::missingFieldName($this->name);
        }

        if (isset($this->fieldMappings[$mapping['fieldName']])) {
            throw MappingException::duplicateFieldMapping($this->name, $mapping['fieldName']);
        }
    }

    /**
     * @param array $mapping
     *
     * @throws MappingException
     */
    private function _mapField(array &$mapping) {
        $fieldName = $mapping['fieldName'];

        if (!isset($mapping['name'])) {
            $mapping['name'] = $fieldName;
        }

        $namespace = $this->reflClass->getNamespaceName();
        if (isset($mapping['targetDoc']) && strpos($mapping['targetDoc'], '\\') === false && strlen($namespace)) {
            $mapping['targetDoc'] = $namespace . '\\' . $mapping['targetDoc'];
        }

        // If targetDoc is unqualified, assume it is in the same namespace as
        // the sourceDoc.
        $mapping['sourceDoc'] = $this->name;

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

        $mapping['isOwningSide'] = true;
        if (isset($mapping['reference'])) {
            if (isset($mapping['childProperty']) && $mapping['childProperty']) {
                $mapping['isOwningSide'] = false;
            }
            if (!isset($mapping['orphanRemoval'])) {
                $mapping['orphanRemoval'] = false;
            }

            if ($mapping['isOwningSide'] && $mapping['orphanRemoval']) {
                $mapping['isCascadeRemove'] = true;
            }
        }

        $this->fieldMappings[$fieldName] = $mapping;
        if (isset($mapping['association'])) {
            $this->associationMappings[$fieldName] = $mapping;
        }
    }
}
