<?php

namespace Doctrine\ODM\OrientDB\Tests\Integration;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Tools\SchemaTool;
use Doctrine\OrientDB\Binding\BindingInterface;
use PHPUnit\TestCase;

abstract class AbstractIntegrationTest extends TestCase
{
    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var SchemaTool
     */
    protected $schemaTool;

    /**
     * The names of the model sets used in this testcase.
     *
     * @var array
     */
    protected $usedModelSets = [];

    /**
     * @var string[]
     */
    protected static $_classesCreated = [];

    /**
     * @var BindingInterface
     */
    protected static $_b;

    /**
     * @var bool
     */
    protected static $_newTestRun = true;

    /**
     * List of model sets and their classes.
     *
     * @var array
     */
    protected static $_modelSets = [
        'cms'      => [
            'Doctrine\ODM\OrientDB\Tests\Models\CMS\CmsUser',
            'Doctrine\ODM\OrientDB\Tests\Models\CMS\CmsPhonenumber',
            //            'Doctrine\ODM\OrientDB\Tests\Models\CMS\CmsAddress',
            //            'Doctrine\ODM\OrientDB\Tests\Models\CMS\CmsEmail',
            //            'Doctrine\ODM\OrientDB\Tests\Models\CMS\CmsGroup',
            'Doctrine\ODM\OrientDB\Tests\Models\CMS\CmsArticle',
            //            'Doctrine\ODM\OrientDB\Tests\Models\CMS\CmsComment',
        ],
        'embedded' => [
            'Doctrine\ODM\OrientDB\Tests\Models\Embedded\Person',
            'Doctrine\ODM\OrientDB\Tests\Models\Embedded\EmailAddress',
            'Doctrine\ODM\OrientDB\Tests\Models\Embedded\Phone',
        ],
        'linked' => [
            'Doctrine\ODM\OrientDB\Tests\Models\Linked\Person',
            'Doctrine\ODM\OrientDB\Tests\Models\Linked\EmailAddress',
            'Doctrine\ODM\OrientDB\Tests\Models\Linked\Phone',
        ],
        'graph' => [
            'Doctrine\ODM\OrientDB\Tests\Models\Graph\Edge',
            'Doctrine\ODM\OrientDB\Tests\Models\Graph\LikedE',
            'Doctrine\ODM\OrientDB\Tests\Models\Graph\PersonV',
            'Doctrine\ODM\OrientDB\Tests\Models\Graph\PostV',
            'Doctrine\ODM\OrientDB\Tests\Models\Graph\Vertex',
        ],
        'standard' => [
            'Doctrine\ODM\OrientDB\Tests\Models\Standard\Country',
            'Doctrine\ODM\OrientDB\Tests\Models\Standard\Address',
            'Doctrine\ODM\OrientDB\Tests\Models\Standard\City',
            'Doctrine\ODM\OrientDB\Tests\Models\Standard\Comment',
            'Doctrine\ODM\OrientDB\Tests\Models\Standard\MapPoint',
            'Doctrine\ODM\OrientDB\Tests\Models\Standard\Post',
            'Doctrine\ODM\OrientDB\Tests\Models\Standard\Profile',
            'Doctrine\ODM\OrientDB\Tests\Models\Standard\ORole',
        ],
    ];

    protected static $_usedModelSets = [];

    /**
     * @param string $setName
     *
     * @return void
     */
    protected function useModelSet($setName) {
        $this->usedModelSets[$setName]  = true;
        self::$_usedModelSets[$setName] = true;
    }

    public static function setUpBeforeClass() {
        if (self::$_newTestRun) {
            self::$_newTestRun = false;

            $b = self::$_b = self::createHttpBinding(['odb.database' => TEST_ODB_DATABASE_NEW]);
            if ($b->databaseExists(TEST_ODB_DATABASE_NEW)) {
                $b->deleteDatabase(TEST_ODB_DATABASE_NEW);
            }
            $b->createDatabase(TEST_ODB_DATABASE_NEW, 'plocal', 'graph');
        }
    }


    /**
     * Creates a connection to the test database, if there is none yet, and
     * creates the necessary tables.
     */
    protected function setUp() {
        if (!$this->dm) {
            $this->dm         = $this->createDocumentManager(['binding' => self::$_b]);
            $this->schemaTool = new SchemaTool($this->dm);
        }

        $prime   = [];
        $classes = [];

        foreach ($this->usedModelSets as $setName => $bool) {
            foreach (static::$_modelSets[$setName] as $className) {
                $prime[$setName][] = $this->dm->getClassMetadata($className);
            }

            if (!isset(static::$_classesCreated[$setName])) {
                $classes = array_merge($classes, $prime[$setName]);
                static::$_classesCreated[$setName] = true;
            }
        }

        if ($classes) {
            $this->schemaTool->createSchema($classes);
        }
    }

    public static function tearDownAfterClass() {
        $b = self::$_b;

        if (isset(self::$_usedModelSets['cms'])) {
            $b->command('DELETE FROM CmsUser');
            $b->command('DELETE FROM CmsArticle');
            $b->command('DELETE FROM CmsPhoneNumber');
        }

        if (isset(self::$_usedModelSets['embedded'])) {
            $b->command('DELETE FROM EmbeddedEmailAddress');
            $b->command('DELETE FROM EmbeddedPerson');
            $b->command('DELETE FROM EmbeddedPhone');
        }

        if (isset(self::$_usedModelSets['linked'])) {
            $b->command('DELETE FROM LinkedEmailAddress');
            $b->command('DELETE FROM LinkedPerson');
            $b->command('DELETE FROM LinkedPhone');
        }

        if (isset(self::$_usedModelSets['standard'])) {
            $b->command('DELETE FROM Country');
            $b->command('DELETE FROM Address');
            $b->command('DELETE FROM City');
            $b->command('DELETE FROM MapPoint');
            $b->command('DELETE FROM Comment');
            $b->command('DELETE FROM Post');
            $b->command('DELETE FROM Profile');

        }

        self::$_usedModelSets = [];
    }

}