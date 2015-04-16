<?php

/**
 * QueryBuilderTest class
 *
 * @package    Doctrine\OrientDB
 * @subpackage Test
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     Daniele Alessandri <daniele.alessandri@gmail.com>
 */

namespace Doctrine\OrientDB\Tests\Integration;

use Doctrine\OrientDB\Binding\HttpBinding;
use Doctrine\OrientDB\Binding\HttpBindingResultInterface;
use Doctrine\OrientDB\Query\CommandInterface;
use Doctrine\OrientDB\Query\QueryBuilder as QB;
use PHPUnit\TestCase;

/**
 * @group integration
 */
class QueryBuilderTest extends TestCase
{
    public $profile_id;
    public $address_id;
    public $post_id;
    public $comment_id;

    /**
     * @var HttpBinding
     */
    public $binding;

    public function setup() {
        $this->binding    = self::createHttpBinding();
        $this->profile_id = $this->binding->getClass('Profile')->getData()->clusters[0];
        $this->address_id = $this->binding->getClass('Address')->getData()->clusters[0];
        $this->post_id    = $this->binding->getClass('Post')->getData()->clusters[0];
        $this->comment_id = $this->binding->getClass('Comment')->getData()->clusters[0];
    }

    public function testSelect() {
        $query = QB::select();

        $this->assertHttpStatus(200, $this->doQuery($query->from(['address'])));
        $this->assertHttpStatus(200, $this->doQuery($query->select(['@version', 'street'])));
    }

    public function testSelectBetween() {
        $query = QB::select();

        $this->assertHttpStatus(200, $this->doQuery($query->from(['Profile'])
                                                          ->between('@rid', '#' . $this->profile_id . ':0', '#' . $this->profile_id . ':5')));
    }

    public function testSelectLimit() {
        $query = QB::select();

        $result = $this->doQuery($query->from(['Address'])->limit(20));
        $this->assertHttpStatus(200, $result);
        $this->assertSame(20, $this->getResultCount($result));

        $query = QB::select();
        $query->from(array('Address'))->limit(30);
        $query->limit(20);

        $result = $this->doQuery($query);
        $this->assertHttpStatus(200, $result);
        $this->assertSame(20, $this->getResultCount($result));

        $query  = QB::select();
        $result = $this->doQuery($query->from(array('Address'))->limit('a'));

        $this->assertHttpStatus(200, $result);
        $this->assertGreaterThan(21, $this->getResultCount($result));
    }

    public function testSelectByRID() {
        $query = QB::select();
        $query->from(array($this->profile_id . ':1'));

        $result = $this->doQuery($query);
        $this->assertHttpStatus(200, $result);
        $this->assertFirstRid($this->profile_id . ':1', $result);
    }

    public function testSelectOrderBy() {
        $query = QB::select();
        $query->from(array($this->profile_id . ':0', $this->profile_id . ':1'))
              ->orderBy('@rid ASC')
              ->orderBy('street DESC');

        $result = $this->doQuery($query);
        $this->assertHttpStatus(200, $result);
        $this->assertFirstRid($this->profile_id . ':0', $result);

        $query->orderBy('@rid DESC', false);

        $result = $this->doQuery($query);
        $this->assertHttpStatus(200, $result);
        $this->assertFirstRid($this->profile_id . ':1', $result);
    }

    public function testSelectComplex() {
        $query = QB::select();
        $query->limit(10)
              ->limit(20)
              ->from(array('11:2', '11:4'), false)
              ->select(array('rid', 'street'))
              ->select(array('type'))
              ->orderBy('street ASC');

        $this->assertHttpStatus(200, $this->doQuery($query));
    }

    /**
     * @param             $query
     * @param HttpBinding $binding
     *
     * @return HttpBindingResultInterface
     */
    protected function doQuery(CommandInterface $query, HttpBinding $binding = null) {
        $binding = $binding ?: $this->binding;
        $result  = $binding->command($query->getRaw());

        return $result;
    }

    protected function assertFirstRid($rid, HttpBindingResultInterface $result) {
        $records = $result->getResult();
        $this->assertSame("#$rid", $records[0]->{'@rid'}, "The first RID of the results is $rid");
    }

    protected function getResultCount(HttpBindingResultInterface $result) {
        $response = json_decode($result->getInnerResponse()->getBody());

        if (array_key_exists(0, $response->result) && property_exists($response->result[0], 'count')) {
            return $response->result[0]->count;
        }

        if (array_key_exists(0, $response->result) && property_exists($response->result[0], 'size')) {
            return $response->result[0]->size;
        }

        if (property_exists($response, 'result')) {
            return count($response->result);
        }

        throw new \Exception('Unable to retrieve a count from the given response.');
    }
}
