<?php

namespace Doctrine\ODM\OrientDB\Mapping;


use Doctrine\OrientDB\Types\Rid;

/**
 * Class MappingException
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class MappingException extends \Exception
{
    public static function missingRidProperty($class) {
        return new self(sprintf('the RID field mapping for %s could not be found.', $class));
    }

    public static function missingOClass($class) {
        return new self(sprintf('missing OrientDB class for %s', $class));
    }

    public static function noClusterForRid($rid) {
        return new self(sprintf('there is no cluster for %s.', $rid));
    }

    public static function noMappingForProperty($property, $document) {
        return new self(sprintf('the %s document has no mapping for %s property.', $document, $property));
    }

    public static function duplicateOrientClassMapping($oclass, $existing, $new) {
        return new self(
            sprintf('attempting to map OrientDB class %s to %s, which is already mapped to %s',
                $oclass, $new, $existing));
    }

    /**
     * @param string $listenerName
     * @param string $className
     *
     * @return self
     */
    public static function documentListenerClassNotFound($listenerName, $className) {
        return new self(sprintf('document listener "%s" declared on "%s" not found.', $listenerName, $className));
    }

    /**
     * @param string $listenerName
     * @param string $methodName
     * @param string $className
     *
     * @return self
     */
    public static function documentListenerMethodNotFound($listenerName, $methodName, $className) {
        return new self(sprintf('document listener "%s" declared on "%s" has no method "%s".', $listenerName, $className, $methodName));
    }

    /**
     * @param string $listenerName
     * @param string $methodName
     * @param string $className
     *
     * @return self
     */
    public static function duplicateDocumentListener($listenerName, $methodName, $className) {
        return new self(sprintf('document listener "%s#%s()" in "%s" was already declared, but it must be declared only once.', $listenerName, $methodName, $className));
    }

    public static function relatedToRequiresDirection($class, $fieldName) {
        return new self(sprintf('direction is required for %s::%s', $class, $fieldName));
    }

    /**
     * @param string $document
     *
     * @return MappingException
     */
    public static function missingFieldName($document) {
        return new self("The field or association mapping misses the 'fieldName' attribute in document '$document'.");
    }

    /**
     * @param $document
     * @param $type
     *
     * @return MappingException
     */
    public static function invalidCollectionType($document, $type) {
        return new self("The document '$document' specified an invalid collection type '$type'");
    }

    /**
     * Exception for reflection exceptions - adds the document name,
     * because there might be long classnames that will be shortened
     * within the stacktrace
     *
     * @param string               $document The document's name
     * @param \ReflectionException $previousException
     *
     * @return self
     */
    public static function reflectionFailure($document, \ReflectionException $previousException) {
        return new self('an error occurred in ' . $document, 0, $previousException);
    }

    /**
     * Exception when a class has multiple document annotations
     *
     * @param $className
     *
     * @return MappingException
     */
    public static function duplicateDocumentAnnotation($className) {
        return new self(sprintf('class %s has multiple document annotations', $className));
    }

    /**
     * @param string $className
     *
     * @return MappingException
     */
    public static function classIsNotAValidEntityOrMappedSuperClass($className) {
        if (false !== ($parent = get_parent_class($className))) {
            return new self(sprintf(
                'class "%s" sub class of "%s" is not a valid entity or mapped super class.',
                $className, $parent
            ));
        }

        return new self(sprintf(
            'class "%s" is not a valid entity or mapped super class.',
            $className
        ));
    }

    /**
     * @param string $entity    The entity's name.
     * @param string $fieldName The name of the field that was already declared.
     *
     * @return MappingException
     */
    public static function duplicateFieldMapping($entity, $fieldName) {
        return new self('property "' . $fieldName . '" in "' . $entity . '" was already declared, but it must be declared only once');
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return MappingException
     */
    public static function simpleReferenceRequiresTargetDocument($className, $fieldName) {
        return new self("target document must be specified for simple reference: $className::$fieldName");
    }

    /**
     * @param $name
     *
     * @return MappingException
     */
    public static function typeExists($name) {
        return new self('type ' . $name . ' already exists.');
    }

    /**
     * @param $name
     *
     * @return MappingException
     */
    public static function typeNotFound($name) {
        return new self('type to be overwritten ' . $name . ' does not exist.');
    }
}