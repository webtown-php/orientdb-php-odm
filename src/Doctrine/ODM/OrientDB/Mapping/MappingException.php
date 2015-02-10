<?php

namespace Doctrine\ODM\OrientDB\Mapping;


use Doctrine\ODM\OrientDB\Types\Rid;

/**
 * Class MappingException
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class MappingException extends \Exception
{
    public static function missingRid($class) {
        return new self(sprintf('the identifier mapping for %s could not be found.', $class));
    }

    public static function noClusterForRid(Rid $rid) {
        return new self(sprintf('there is no cluster for %s.', $rid->getValue()));
    }

    public static function noMappingForProperty($property, $class) {
        return new self(sprintf('the %s class has no mapping for %s property.', $class, $property));
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
     * @return MappingException
     */
    public static function simpleReferenceRequiresTargetDocument($className, $fieldName)
    {
        return new self("target document must be specified for simple reference: $className::$fieldName");
    }

    /**
     * @param $name
     *
     * @return MappingException
     */
    public static function typeExists($name)
    {
        return new self('type ' . $name . ' already exists.');
    }

    /**
     * @param $name
     *
     * @return MappingException
     */
    public static function typeNotFound($name)
    {
        return new self('type to be overwritten ' . $name . ' does not exist.');
    }
}