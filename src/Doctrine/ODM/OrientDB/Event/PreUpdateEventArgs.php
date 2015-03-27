<?php

namespace Doctrine\ODM\OrientDB\Event;
use Doctrine\ODM\OrientDB\DocumentManager;

/**
 * Class that holds event arguments for a preInsert/preUpdate event.
 */
class PreUpdateEventArgs extends LifecycleEventArgs
{
    /**
     * @var array
     */
    private $changeSet;

    /**
     * Constructor.
     *
     * @param object                 $entity
     * @param DocumentManager $dm
     * @param array                  $changeSet
     */
    public function __construct($entity, DocumentManager $dm, array &$changeSet)
    {
        parent::__construct($entity, $dm);

        $this->changeSet = &$changeSet;
    }

    /**
     * Retrieves entity changeset.
     *
     * @return array
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }

    /**
     * Checks if field has a changeset.
     *
     * @param string $field
     *
     * @return boolean
     */
    public function hasChangedField($field)
    {
        return isset($this->changeSet[$field]);
    }

    /**
     * Gets the old value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getOldValue($field)
    {
        $this->assertValidField($field);

        return $this->changeSet[$field][0];
    }

    /**
     * Gets the new value of the changeset of the changed field.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function getNewValue($field)
    {
        $this->assertValidField($field);

        return $this->changeSet[$field][1];
    }

    /**
     * Sets the new value of this field.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return void
     */
    public function setNewValue($field, $value)
    {
        $this->assertValidField($field);

        $this->changeSet[$field][1] = $value;
    }

    /**
     * Asserts the field exists in changeset.
     *
     * @param string $field
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function assertValidField($field)
    {
        if ( ! isset($this->changeSet[$field])) {
            throw new \InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the document "%s" in PreUpdateEventArgs.',
                $field,
                get_class($this->getDocument())
            ));
        }
    }
}
