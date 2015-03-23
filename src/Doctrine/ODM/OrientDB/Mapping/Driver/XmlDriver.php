<?php

namespace Doctrine\ODM\OrientDB\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\MappingException;
use SimpleXMLElement;

class XmlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.xml';

    /**
     * @inheritdoc
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION) {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * @inheritdoc
     */
    protected function loadMappingFile($file) {
        $result     = [];
        $xmlElement = simplexml_load_file($file);

        foreach (['document', 'embedded-document', 'mapped-superclass', 'vertex', 'relationship'] as $type) {
            if (isset($xmlElement->$type)) {
                foreach ($xmlElement->$type as $documentElement) {
                    $documentName          = (string)$documentElement['name'];
                    $result[$documentName] = $documentElement;
                }
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function loadMetadataForClass($className, \Doctrine\Common\Persistence\Mapping\ClassMetadata $metadata) {
        /* @var $metadata ClassMetadata */
        /* @var $xmlRoot SimpleXMLElement */
        $xmlRoot = $this->getElement($className);

        switch ($xmlRoot->getName()) {
            case 'document':
                $metadata->setIsDocument();
                break;

            case 'vertex':
                $metadata->setIsDocument();
                $metadata->setIsVertex();
                break;

            case 'relationship':
                $metadata->setIsDocument();
                $metadata->setIsEdge();
                break;

            case 'embedded-document':
                $metadata->setIsEmbeddedDocument();
                break;

            case 'mapped-superclass':
                $metadata->setIsMappedSuperclass();
                break;

            default:
                throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        if (isset($xmlRoot['oclass'])) {
            $metadata->setOrientClass((string)$xmlRoot['oclass']);
        }

        if (isset($xmlRoot['abstract']) && ((string)$xmlRoot['abstract'] === 'true')) {
            $metadata->setIsAbstract();
        }

        if (isset($xmlRoot->{'rid'})) {
            $field = $xmlRoot->{'rid'};
            $metadata->mapRid((string)$field['fieldName']);
        }

        if (isset($xmlRoot->{'version'})) {
            $field = $xmlRoot->{'version'};
            $metadata->mapVersion((string)$field['fieldName']);
        }

        if ($metadata->isEdge()) {
            if (isset($xmlRoot->{'in'})) {
                $mapping = [];
                $this->_copyCommonPropertyAttributesToMapping($xmlRoot->{'in'}->attributes(), $mapping);
                $metadata->mapVertexLink($mapping, 'in');
            }

            if (isset($xmlRoot->{'out'})) {
                $mapping = [];
                $this->_copyCommonPropertyAttributesToMapping($xmlRoot->{'out'}->attributes(), $mapping);
                $metadata->mapVertexLink($mapping, 'out');
            }
        }

        // Evaluate <change-tracking-policy...>
        if (isset($xmlRoot['change-tracking-policy'])) {
            $metadata->setChangeTrackingPolicy(constant(sprintf('%s::CHANGETRACKING_%s', ClassMetadata::class, strtoupper((string)$xmlRoot['change-tracking-policy']))));
        }

        if (isset($xmlRoot->field)) {
            $this->readFields($metadata, $xmlRoot);
        }

        if (isset($xmlRoot->{'embed-one'})) {
            foreach ($xmlRoot->{'embed-one'} as $node) {
                $this->addEmbedMapping($metadata, $node, 'one');
            }
        }
        if (isset($xmlRoot->{'embed-many'})) {
            foreach ($xmlRoot->{'embed-many'} as $node) {
                $type = isset($node['collection']) ? (string)$node['collection'] : 'list';
                $this->addEmbedMapping($metadata, $node, $type);
            }
        }
        if (isset($xmlRoot->{'link-one'})) {
            foreach ($xmlRoot->{'link-one'} as $node) {
                $this->addLinkMapping($metadata, $node, 'one');
            }
        }
        if (isset($xmlRoot->{'link-many'})) {
            foreach ($xmlRoot->{'link-many'} as $node) {
                $type = isset($node['collection']) ? (string)$node['collection'] : 'list';
                $this->addLinkMapping($metadata, $node, $type);
            }
        }
        if (isset($xmlRoot->{'related-to'})) {
            foreach ($xmlRoot->{'related-to'} as $node) {
                $this->addRelatedToMapping($metadata, $node);
            }
        }
        if (isset($xmlRoot->{'related-to-via'})) {
            foreach ($xmlRoot->{'related-to-via'} as $node) {
                $this->addRelatedToMapping($metadata, $node, false);
            }
        }
    }

    private function _copyCommonPropertyAttributesToMapping(\SimpleXMLElement $attributes, array &$mapping) {
        if (isset($attributes['fieldName'])) {
            $mapping['fieldName'] = (string)$attributes['fieldName'];
        }
        if (isset($attributes['target-doc'])) {
            $mapping['targetDoc'] = (string)$attributes['target-doc'];
        }
    }

    private function addEmbedMapping(ClassMetadata $class, \SimpleXMLElement $embed, $type) {
        $attributes = $embed->attributes();
        $mapping = [];
        $this->_copyCommonPropertyAttributesToMapping($attributes, $mapping);

        switch ($type) {
            case 'one':
                $class->mapEmbedded($mapping);
                break;
            case 'list':
                $class->mapEmbeddedList($mapping);
                break;
            case 'set':
                $class->mapEmbeddedSet($mapping);
                break;
            case 'map':
                $class->mapEmbeddedMap($mapping);
                break;
            default:
                throw MappingException::invalidCollectionType($class->name, $type);
        }
    }

    private function addLinkMapping(ClassMetadata $class, \SimpleXMLElement $embed, $type) {
        $attributes = $embed->attributes();
        $mapping    = [
            'cascade'   => isset($embed->cascade) ? $this->_getCascadeMappings($embed->cascade) : [],
        ];

        $this->_copyCommonPropertyAttributesToMapping($attributes, $mapping);

        if (isset($attributes['orphan-removal'])) {
            $mapping['orphanRemoval'] = ((string)$attributes['orphan-removal'] === 'true');
        }

        if (isset($attributes['parent-property'])) {
            $mapping['parentProperty'] = (string)$attributes['parent-property'];
        }
        if (isset($attributes['child-property'])) {
            $mapping['childProperty'] = (string)$attributes['child-property'];
        }

        switch ($type) {
            case 'one':
                $class->mapLink($mapping);
                break;
            case 'list':
                $class->mapLinkList($mapping);
                break;
            case 'set':
                $class->mapLinkSet($mapping);
                break;
            case 'map':
                $class->mapLinkMap($mapping);
                break;
            default:
                throw MappingException::invalidCollectionType($class->name, $type);
        }
    }

    private function addRelatedToMapping(ClassMetadata $class, \SimpleXMLElement $embed, $indirect = true) {
        $attributes = $embed->attributes();
        $mapping    = [
            'indirect'  => $indirect,
        ];
        $this->_copyCommonPropertyAttributesToMapping($attributes, $mapping);
        if (isset($attributes['oclass'])) {
            $mapping['oclass'] = (string)$attributes['oclass'];
        }
        if (isset($attributes['direction'])) {
            $mapping['direction'] = (string)$attributes['direction'];
        }
        $class->mapRelatedToLinkBag($mapping);
    }

    /**
     * Gathers a list of cascade options found in the given cascade element.
     *
     * @param SimpleXMLElement $cascadeElement The cascade element.
     *
     * @return array The list of cascade options.
     */
    private function _getCascadeMappings(SimpleXMLElement $cascadeElement) {
        $cascades = [];
        /* @var $action SimpleXmlElement */
        foreach ($cascadeElement->children() as $action) {
            // According to the JPA specifications, XML uses "cascade-persist"
            // instead of "persist". Here, both variations
            // are supported because both YAML and Annotation use "persist"
            // and we want to make sure that this driver doesn't need to know
            // anything about the supported cascading actions
            $cascades[] = str_replace('cascade-', '', $action->getName());
        }

        return $cascades;
    }


    private function readFields(ClassMetadata $metadata, \SimpleXMLElement $xmlRoot) {
        foreach ($xmlRoot->{'field'} as $field) {
            $mapping = [
                'type'     => 'string',
                'nullable' => false,
            ];

            $booleanAttributes = ['nullable'];
            foreach ($field->attributes() as $key => $value) {
                $mapping[$key] = (string)$value;
                if (in_array($key, $booleanAttributes)) {
                    $mapping[$key] = ('true' === $mapping[$key]) ? true : false;
                }
            }

            $this->addFieldMapping($metadata, $mapping);
        }
    }

    private function addFieldMapping(ClassMetadata $metadata, &$mapping) {
        $metadata->mapField($mapping);
    }


}