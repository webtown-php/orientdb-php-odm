<?php

/**
 * QueryBuilderTest class
 *
 * @package    
 * @subpackage 
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 */

namespace Orient\Test\Integration;

use Orient\Test\PHPUnit\TestCase;
use Orient\Query;
use Orient\Http\Client\Curl;
use Orient\Foundation\Binding;

class QueryBuilderTest extends TestCase
{
    const _200 = 'HTTP/1.1 200 OK';
    const _201 = 'HTTP/1.1 201 Created';
    const _204 = 'HTTP/1.1 204 OK';
    const _401 = 'HTTP/1.1 401 Unauthorized';
    const _404 = 'HTTP/1.1 404 Not Found';
    const _500 = 'HTTP/1.1 500 Internal Server Error';


  public function setup()
  {
      $this->driver = new Curl();
      $dbName       = 'demo';
      $this->orient = new Binding($this->driver, '127.0.0.1', '2480', 'admin', 'admin', $dbName);
      $this->query  = new Query();
  }
  
  public function testASimpleSelect()
  {
      $this->query->from(array('address'));
      
      $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
      
      $this->query->select(array('@version','street'));
      
      $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
  }

  public function testTheRangeOfASelect()
  {    
    $this->query->from(array('Address'))->range('12:1');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

    $this->query->range(null, '12');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

    $this->query->range('10.0');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

    $this->query->range('10.1', false);

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

    $this->query->range('10.1', '10.2');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('Test the real range count');
  }

  public function testLimitingASelect()
  {
    $this->query->from(array('Address'))->limit(20);

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

    $this->query->from(array('Address'))->limit('20');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

    $this->query->from(array('Address'))->limit('a');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('count the results with/without limit');
  }

  public function testSelectingByRIDs()
  {
    $this->query->from(array('12:0', '12:1'));

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('look at the resultins RID');
  }

  public function testOrderingTheQuery()
  {
    $this->query->from(array('12:0', '12:1'))->orderBy('rid ASC')->orderBy('street DESC');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('Test first result has higher rid');
  }

  public function testDoingAComplexSelect()
  {
    $this->query->limit(10);
    $this->query->limit(20);
    $this->query->from(array('12:2', '12:4'), false);
    $this->query->select(array('rid', 'street'));
    $this->query->select(array('type'));
    $this->query->range('12:2');
    $this->query->range(null, '12:4');
    $this->query->orderBy('street ASC');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
  }

  public function testInsertARecord()
  {
    $this->query->insert()
                ->fields(array('street', 'type', 'city'))
                ->values(array('5th avenue', 'villa', '#13:0'))
                ->into('Address');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('count before and after');
  }
  
  /**
   * @depends testInsertARecord
   */
  public function testADelete()
  {
    $this->query->delete('Address')
                ->where('street = ?', '5th avenue')
                ->andWhere('city.rid = ?', "13:0")
                ->orWhere('type <> "villa"');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('count before and after');
  }

  public function testGrantingACredential()
  {
    $this->query->grant('READ')
                ->to('reader')
                ->on('Address');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
  }

  public function testRevokingACredential()
  {
    $this->query->revoke('READ')
                ->to('reader')
                ->on('Address');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
  }

  public function testCreateAnIndex()
  {
    $this->query->index('index_name_2','unique');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

    $this->query = new Query();
    $this->query->index('in','unique', 'OGraphEdge');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('count index size');
  }

  public function testCountingAnIndexSize()
  {
    $this->query->indexCount('index_name_2');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
  }

  public function testExecutingAIndexLookup()
  {
    $this->query->lookup('index_name_2');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

    $this->query->where('key = ?', 2);

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

    $this->query->where('fakekey = ?', 2);

    $this->assertStatusCode(self::_500, $this->orient->command($this->query->getRaw()));

    $this->query = new Query();
    $this->query->from(array('index:index_name_2'))->between('key','10.0','10.1');
    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
  }

  public function testAddingAnEntryToAnIndex()
  {
    $this->query->indexPut('index_name_2', 'k', '12:0');

    $this->assertStatusCode(self::_204, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('count before and after');
  }

  public function testRemovingAnEntryToAnIndex()
  {
    $this->query->indexRemove('index_name_2', 'k');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('count before and after');
  }

  public function testDroppingAnIndex()
  {
    $this->query->unindex('index_name_2');

    $this->assertStatusCode(self::_204, $this->orient->command($this->query->getRaw()));

    $this->query->unindex('in','OGraphEdge');

    $this->assertStatusCode(self::_204, $this->orient->command($this->query->getRaw()));
    
    $this->markTestIncomplete('count before and after');
  }

  public function testFindingAReference()
  {
    $this->query->findReferences('12:0');

    $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
  }

  public function testCreatingAClass()
  {
      $this->time   = microtime();
      $class        = 'MyCustomTestClass' . $this->time;
      $this->query->create($class);

      $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

      return $class;
  }

  /**
   * @depends testCreatingAClass
   */
  public function testAlteringAClass($class)
  {
      $this->query->alter($class, 'SUPERCLASS', 'OUser');

      $this->assertStatusCode(self::_204, $this->orient->command($this->query->getRaw()));

      return $class;
  }

  /**
   * @depends testAlteringAClass
   */
  public function testCreatingAProperty($class)
  {
      $this->query->create($class, 'customTestProperty', 'string');

      $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));

      return $class;
  }

  /**
   * @depends testCreatingAProperty
   */
  public function testAlteringAProperty($class)
  {
      $this->query->alterProperty($class, 'customTestProperty', 'notnull', 'false');

      $this->assertStatusCode(self::_204, $this->orient->command($this->query->getRaw()));
      
      $this->query->alterProperty($class, 'customTestProperty', 'notnull', 'true');

      $this->assertStatusCode(self::_204, $this->orient->command($this->query->getRaw()));

      return $class;
  }

  /**
   * @depends testAlteringAProperty
   */
  public function testDroppingAProperty($class)
  {
      $this->query->drop($class, 'customTestProperty');

      $this->assertStatusCode(self::_204, $this->orient->command($this->query->getRaw()));

      return $class;
  }

  /**
   * @depends testAlteringAClass
   */
  public function testDroppingClass($class)
  {
      $this->query->drop($class);

      $this->assertStatusCode(self::_204, $this->orient->command($this->query->getRaw()));

      return $class;
  }

  public function testLinkingTwoObjects()
  {
      $this->query->link('Company', 'id', 'in', true)->to('ORole', 'id');

      $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
  }

  public function testUpdating()
  {
      $this->query->update('Profile')->set(array('nick' => 'Luca'));
      $this->query->where('@version = ?', 45);
      $this->query->orWhere('@rid = ?', '12:0');

      $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
      
      $this->markTestIncomplete('select the updated record and verify updates');
  }

  public function testAddingALink()
  {
      $this->query->add(array('comments' => '26:0'), 'post');
      $this->query->where('@rid = ?', '25:1');
      
      $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
      
      $this->markTestIncomplete('verify link presence');
  }

  /**
   * @depends testAddingALink
   */
  public function testRemovingALink()
  {
      $this->query->remove(array('comments' => '26:0'), 'post');
      $this->query->where('@rid = ?', '25:1');
      
      $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
      
      $this->markTestIncomplete('verifying link presence');
  }

  public function testPuttingALink()
  {
      $this->query->put(array('knows' => array('Johnny' => '8:1')), 'account');
      $this->query->where('@rid = ?', '8:0');
      
      $this->assertStatusCode(self::_200, $this->orient->command($this->query->getRaw()));
      
      $this->markTestIncomplete('verifying link presence');
  }
  
  protected function countResults(\Orient\Http\Response $response)
  {
    $response = json_decode($response->getBody());
    $property = 'count(*)';
    
    if (property_exists($response->result[0], $property))
    {
      return $response->result[0]->$property;
    }
    
    throw new \Exception('Unable to retrieve a count from the given response.');
  }
}