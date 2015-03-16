<?php

namespace Doctrine\ODM\OrientDB;


use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;

class OrientDBInvalidArgumentException extends \InvalidArgumentException
{
    /**
     * @param array  $assoc
     * @param object $entry
     *
     * @return self
     */
    static public function detachedDocumentFoundThroughRelationship(array $assoc, $entry)
    {
        return new self("A detached document of type " . $assoc['targetCLass'] . " (" . self::objToStr($entry) . ") "
            . " was found through the relationship '" . $assoc['sourceClass'] . "#" . $assoc['fieldName'] . "' "
            . "during cascading a persist operation.");
    }

    /**
     * @param array  $assoc
     * @param object $entry
     *
     * @return self
     */
    static public function newDocumentFoundThroughRelationship(array $assoc, $entry)
    {
        return new self("A new document was found through the relationship '"
            . $assoc['sourceClass'] . "#" . $assoc['fieldName'] . "' that was not"
            . " configured to cascade persist operations for document: " . self::objToStr($entry) . "."
            . " To solve this issue: Either explicitly call DocumentManager#persist()"
            . " on this unknown document or configure cascade persist "
            . " this association in the mapping for example @ManyToOne(..,cascade={\"persist\"})."
            . (method_exists($entry, '__toString') ?
                "":
                " If you cannot find out which entity causes the problem"
                ." implement '" . $assoc['targetClass'] . "#__toString()' to get a clue."));
    }

    /**
     * @param ClassMetadata $targetClass
     * @param array         $assoc
     * @param mixed         $actualValue
     *
     * @return self
     */
    public static function invalidAssociation(ClassMetadata $targetClass, $assoc, $actualValue)
    {
        $expectedType = 'Doctrine\Common\Collections\Collection|array';

        if (($assoc['association'] & ClassMetadata::TO_ONE) > 0) {
            $expectedType = $targetClass->getName();
        }

        return new self(sprintf(
            'Expected value of type "%s" for association field "%s#$%s", got "%s" instead.',
            $expectedType,
            $assoc['sourceClass'],
            $assoc['fieldName'],
            is_object($actualValue) ? get_class($actualValue) : gettype($actualValue)
        ));
    }

    protected static function objToStr($obj) {
        return method_exists($obj, '__toString') ? (string)$obj : get_class($obj) . '@' . spl_object_hash($obj);
    }
}