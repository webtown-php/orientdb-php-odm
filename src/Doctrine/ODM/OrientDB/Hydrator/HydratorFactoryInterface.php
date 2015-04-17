<?php

namespace Doctrine\ODM\OrientDB\Hydrator;


use Doctrine\ODM\OrientDB\DocumentManager;

interface HydratorFactoryInterface
{
    /**
     * Injects the DocumentManager
     *
     * @param DocumentManager $dm
     */
    public function setDocumentManager(DocumentManager $dm);

    /**
     * Gets the hydrator object for the given document class.
     *
     * @param string $className
     *
     * @return \Doctrine\ODM\OrientDB\Hydrator\HydratorInterface $hydrator
     */
    public function getHydratorFor($className);

    /**
     * Hydrate array of OrientDB document data into the given document object.
     *
     * @param object $document The document object to hydrate the data into.
     * @param array  $data     The array of document data.
     * @param array  $hints    Any hints to account for during reconstitution/lookup of the document.
     *
     * @return array $values The array of hydrated values.
     */
    public function hydrate($document, $data, array $hints = []);
}