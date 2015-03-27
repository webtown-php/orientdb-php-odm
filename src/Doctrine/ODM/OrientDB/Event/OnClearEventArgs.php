<?php

namespace Doctrine\ODM\OrientDB\Event;

use Doctrine\ODM\OrientDB\DocumentManager;

class OnClearEventArgs extends \Doctrine\Common\EventArgs
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var string
     */
    private $documentClass;

    /**
     * @param DocumentManager $dm
     * @param string|null     $documentClass Optional entity class.
     */
    public function __construct(DocumentManager $dm, $documentClass = null) {
        $this->dm            = $dm;
        $this->documentClass = $documentClass;
    }

    /**
     * Retrieves associated DocumentManager.
     *
     * @return DocumentManager
     */
    public function getDocumentManager() {
        return $this->dm;
    }

    /**
     * Name of the document class that is cleared, or empty if all are cleared.
     *
     * @return string|null
     */
    public function getDocumentClass() {
        return $this->documentClass;
    }

    /**
     * Checks if event clears all documents.
     *
     * @return bool
     */
    public function clearsAllDocuments() {
        return ($this->documentClass === null);
    }
}
