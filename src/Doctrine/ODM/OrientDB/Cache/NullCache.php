<?php

namespace Doctrine\ODM\OrientDB\Cache;

use Doctrine\Common\Cache\CacheProvider;

/**
 * /dev/null cache
 */
class NullCache extends CacheProvider {

    /**
     * @inheritdoc
     */
    protected function doFetch($id) {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function doContains($id) {
        return false;
    }

    /**
     * @inheritdoc
     */
    protected function doSave($id, $data, $lifeTime = 0) {
    }

    /**
     * @inheritdoc
     */
    protected function doDelete($id) {
    }

    /**
     * @inheritdoc
     */
    protected function doFlush() {
    }

    /**
     * @inheritdoc
     */
    protected function doGetStats() {
        return null;
    }
}