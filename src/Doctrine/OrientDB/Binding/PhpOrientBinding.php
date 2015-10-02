<?php

namespace Doctrine\OrientDB\Binding;

use PhpOrient\PhpOrient;
use PhpOrient\Protocols\Binary\Data\ID;
use PhpOrient\Protocols\Binary\Data\Record;
use PhpOrient\Protocols\Common\ClusterMap;

class PhpOrientBinding implements BindingInterface
{
    /**
     * @var PhpOrient
     */
    private $client;

    /**
     * @var ClusterMap
     */
    private $clusterMap;

    public function __construct(BindingParameters $parameters) {
        $this->client = $client = new PhpOrient($parameters->getHost(), $parameters->getPort());
        $this->clusterMap = $client->dbOpen($parameters->getDatabase(), $parameters->getUsername(), $parameters->getPassword());
    }

    /**
     * @inheritdoc
     */
    public function getServerInfo() {
        // TODO: Implement and/or cleanup getServerInfo() method.
        /** @var Record $res */
        $res = $this->client->recordLoad(new ID(0,1))[0];
        $c = $res->classes;

        return $this->clusterMap;
    }

    /**
     * @inheritdoc
     */
    public function getDatabaseInfo() {
        // TODO: Implement getDatabaseInfo() method.
        $res = $this->client->recordLoad(new ID(0,1));
        $this->clusterMap;
    }

    /**
     * @inheritdoc
     */
    public function getClusterMap() {
        // TODO: Implement getClusterMap() method.
    }

    /**
     * @inheritdoc
     */
    public function command($query, $language = BindingInterface::LANGUAGE_SQLPLUS) {
        // TODO: Implement command() method.
    }

    /**
     * @inheritdoc
     */
    public function query($query, $limit = null, $fetchPlan = null, $language = BindingInterface::LANGUAGE_SQLPLUS) {
        // TODO: Implement query() method.
    }

    /**
     * @inheritdoc
     */
    public function getDatabaseName() {
        // TODO: Implement getDatabaseName() method.
    }

    /**
     * @inheritdoc
     */
    public function databaseExists($database = null) {
        // TODO: Implement databaseExists() method.
    }

    /**
     * @inheritdoc
     */
    public function deleteDatabase($database) {
        // TODO: Implement deleteDatabase() method.
    }

    /**
     * @inheritdoc
     */
    public function listDatabases() {
        // TODO: Implement listDatabases() method.
    }

    /**
     * @inheritdoc
     */
    public function createDatabase($database, $storage = 'memory', $type = 'document') {
        // TODO: Implement createDatabase() method.
    }

    /**
     * @inheritdoc
     */
    public function getDocument($rid, $fetchPlan = null) {
        // TODO: Implement getDocument() method.
    }

    /**
     * @inheritdoc
     */
    public function documentExists($rid) {
        // TODO: Implement documentExists() method.
    }

    /**
     * @inheritdoc
     */
    public function sqlBatch($cmd, $transaction = true) {
        // TODO: Implement sqlBatch() method.
    }
}