<?php

namespace Doctrine\ODM\OrientDB\Hydrator;

/**
 * An interface for defining a hy
 */
interface HydratorInterface
{
    /**
     * Hydrate array of MongoDB document data into the given document object.
     *
     * @param object $document The document object to hydrate the data into.
     * @param array  $data     The array of document data.
     * @param array  $hints    Any hints to account for during reconstitution/lookup of the document.
     *
     * @return array $values The array of hydrated values.
     */
    function hydrate($document, $data, array $hints = []);
}