<?php
require_once 'vendor/autoload.php';

use Guzzle\Http\Client;
use Guzzle\Http\Exception\BadResponseException;


define('DB_NAME', 'ODMTest');

class Fixtures
{
    private $client;
    public $dbname;
    public $contentType;

    public function __construct($dbname, $dbuser, $dbpass) {
        $this->dbname = $dbname;
        $this->contentType = array('Content-Type' => 'application/json');

        $this->client = new Client('http://127.0.0.1:2480');
        $this->client->setDefaultOption('auth', [$dbuser,$dbpass]);

    }

    function clean()
    {
        $res = $this->client->get('/listDatabases')->send();
        $body = json_decode($res->getBody(true));
        if (in_array($this->dbname, $body->databases)) {
            $res = $this->client->delete(sprintf("/database/%s", $this->dbname))->send();
            
            $body = json_decode($res->getBody(true));
        }
        $res = $this->client->post(sprintf('/database/%s/plocal/graph', $this->dbname))->send();
        $body = json_decode($res->getBody(true));

        return $this;
    }

    function create_classes()
    {
        $classes = [
            'Address'  => '{"city": {"propertyType": "STRING"}}',
            'Country'  => null,
            'City'     => null,
            'Phone'    => '{"phone": {"propertyType": "STRING"}}',
            'Profile'  => '{ "name": {"propertyType": "STRING"} , "followers": {"propertyType": "LINKMAP","linkedClass": "Profile"}, "phones": {"propertyType":"EMBEDDEDLIST", "linkedClass":"Phone"} }',
            'Company'  => null,
            'Comment'  => null,
            'Post'     => '{"comments": {"propertyType": "LINKLIST","linkedClass": "Comment"}}',
            'MapPoint' => '{"x": {"propertyType": "FLOAT"}, "y":{"propertyType": "FLOAT"} }',
        ];

        foreach ($classes as $class => $properties) {
            $query = "CREATE CLASS " . $class;

            $result = $this->client->post('/command/' . $this->dbname . '/sql/' .$query, $this->contentType)->send()->json();
            $this->{$class} = $result['result'][0]['value'];

            if ($properties) {
                $this->client->post(sprintf("/property/%s/%s",$this->dbname,$class), $this->contentType, $properties)->send();
            }
        }

        return $this;

    }

    function load_fixtures()
    {

        //Insert  City
        $this->client->post('/document/'. $this->dbname , $this->contentType, '{"@class": "City", "name": "Rome" }')->send();


        //Insert  Address
        for ($i = 0 ; $i < 40 ; $i++) {
            $this->client->post('/document/'. $this->dbname , $this->contentType, sprintf('{"@class": "Address", "street" : "New street %d, Italy", "city":"#'.$this->City.':0"}',$i))->send();
        }

        //Insert countries
        $countries = array('France', 'Italy', 'Spain', 'England', 'Ireland', 'Poland', 'Bulgaria', 'Portogallo', 'Belgium', 'Suisse');
        foreach ($countries as $country) {
            $this->client->post('/document/'. $this->dbname , $this->contentType, '{"@class": "Country", "name": "' . $country . '" }')->send();
        }

        //Insert Profile
        $profiles = array('David','Alex','Luke','Marko','Rexter','Gremlin', 'Thinkerpop', 'Frames');
        foreach ($profiles as $k => $profile) {
            $data = new \stdClass();
            $data->{'@class'} = "Profile";
            $data->name = $profile;
            $phones = [];
            for ($i=0; $i<2; $i++) {
                $phone = new \stdClass();
                $phone->{'@type'} = 'd';
                $phone->{'@class'} = 'Phone';
                $phone->phone = "555-201-$i" . str_repeat($k, 3);
                $phones []= $phone;
            }
            $data->phones = $phones;
            $data = json_encode($data);
            $res = $this->client->post('/document/'. $this->dbname , $this->contentType, $data)->send();
            $body = $res->getBody(true);
        }

        //Insert Comment
        $templateComment = '{"@class": "Comment", "body": "comment number %d" }';
        for ($i = 0 ; $i <= 5 ; $i++) {
            $this->client->post('/document/'. $this->dbname , $this->contentType, sprintf($templateComment,$i,$i))->send();
        }

        //Insert Post
        $templatePost = '{"@class": "Post", "id":"%d","title": "%d", "body": "Body %d", "comments":["#'.$this->Comment.':3"] }';
        for ($i = 0 ; $i <= 5 ; $i++) {
            $this->client->post('/document/'. $this->dbname , $this->contentType, sprintf($templatePost,$i,$i,$i))->send();
        }
        $this->client->post('/document/'. $this->dbname , $this->contentType, '{"@class": "Post", "id":"6","title": "titolo 6", "body": "Body 6", "comments":["#'.$this->Comment.':2"] }')->send();

        //Insert MapPoint
        $this->client->post('/document/'. $this->dbname , $this->contentType, '{"@class": "MapPoint", "x": "42.573968", "y": "13.203125" }')->send();

        return $this;
    }

}

$fixtures = new Fixtures('ODMTest','root','password');
$fixtures
    ->clean()
    ->create_classes()
    ->load_fixtures()
    ;

