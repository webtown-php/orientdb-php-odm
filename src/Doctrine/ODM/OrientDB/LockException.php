<?php

namespace Doctrine\ODM\OrientDB;

class LockException extends ODMOrientDbException
{
    private $documents;

    public function __construct($msg, $documents = []) {
        parent::__construct($msg);
        $this->documents = $documents;
    }

    /**
     * @param object[] $documents
     *
     * @return self
     */
    public static function lockFailed($documents) {
        return new self("an optimistic lock for one or more documents failed", $documents);
    }

    /**
     * Gets the documents that caused the exception.
     *
     * @return object[]
     */
    public function getDocuments() {
        return $this->documents;
    }
}