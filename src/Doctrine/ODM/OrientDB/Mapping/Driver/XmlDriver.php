<?php

namespace Doctrine\ODM\OrientDB\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\FileDriver;
use SimpleXMLElement;

class XmlDriver extends FileDriver
{
    const DEFAULT_FILE_EXTENSION = '.dcm.xml';

    /**
     * {@inheritDoc}
     */
    public function __construct($locator, $fileExtension = self::DEFAULT_FILE_EXTENSION) {
        parent::__construct($locator, $fileExtension);
    }

    /**
     * {@inheritDoc}
     */
    protected function loadMappingFile($file)
    {
        $result = [];
        $xmlElement = simplexml_load_file($file);

        foreach (['document', 'embedded-document'] as $type) {
            if (isset($xmlElement->$type)) {
                foreach ($xmlElement->$type as $documentElement) {
                    $documentName = (string) $documentElement['name'];
                    $result[$documentName] = $documentElement;
                }
            }
        }

        return $result;
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string        $className
     * @param ClassMetadata $metadata
     *
     * @return void
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata) {
        /* @var $metadata ClassMetadata */
        /* @var $xmlRoot SimpleXMLElement */
        $xmlRoot = $this->getElement($className);
    }
}