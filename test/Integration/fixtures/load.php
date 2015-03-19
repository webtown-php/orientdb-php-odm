<?php
require_once 'vendor/autoload.php';

use Guzzle\Http\Client;


define('DB_NAME', 'ODMTest');

class Fixtures
{
    private $client;
    public $dbname;
    public $contentType;

    public function __construct($dbname, $dbuser, $dbpass) {
        $this->dbname      = $dbname;
        $this->contentType = ['Content-Type' => 'application/json'];

        $this->client = new Client('http://127.0.0.1:2480');
        $this->client->setDefaultOption('auth', [$dbuser, $dbpass]);

    }

    function clean() {
        $res  = $this->client->get('/listDatabases')->send();
        $body = json_decode($res->getBody(true));
        if (in_array($this->dbname, $body->databases)) {
            $res = $this->client->delete(sprintf("/database/%s", $this->dbname))->send();

            $body = json_decode($res->getBody(true));
        }
        $res  = $this->client->post(sprintf('/database/%s/plocal/graph', $this->dbname))->send();
        $body = json_decode($res->getBody(true));

        return $this;
    }

    function create_classes() {
        $classes = [
            'Address'          => [
                'p' => [
                    'city' => 'STRING',
                ]
            ],
            'Country'          => null,
            'City'             => null,
            'Phone'            => [
                'p' => [
                    'phone' => 'STRING'
                ]
            ],
            'PhoneLink'        => [
                'p' => [
                    'phone' => 'STRING'
                ]
            ],
            'Profile'          => [
                'p' => [
                    'name'      => 'STRING',
                    'followers' => [
                        'type'  => 'LINKMAP',
                        'class' => 'Profile',
                    ],
                    'phones'    => [
                        'type'  => 'EMBEDDEDLIST',
                        'class' => 'Phone',
                    ],
                ]
            ],
            'Company'          => null,
            'Comment'          => null,
            'Post'             => [
                'p' => [
                    'comments' => [
                        'type'  => 'LINKLIST',
                        'class' => 'Comment',
                    ],
                ]
            ],

            'MapPoint'         => [
                'p' => [
                    'x' => 'FLOAT',
                    'y' => 'FLOAT',
                ]
            ],
            'EmailAddress'     => [
                'p' => [
                    'type'  => 'STRING',
                    'email' => 'STRING',
                ]
            ],
            'EmailAddressLink' => [
                'p' => [
                    'type'  => 'STRING',
                    'email' => 'STRING',
                ]
            ],
            'Person'           => [
                'p' => [
                    'name'   => 'STRING',
                    'email'  => [
                        'type'  => 'EMBEDDED',
                        'class' => 'EmailAddress',
                    ],
                    'emails' => [
                        'type'  => 'EMBEDDEDLIST',
                        'class' => 'EmailAddress',
                    ],
                    'phones' => [
                        'type'  => 'EMBEDDEDMAP',
                        'class' => 'Phone',
                    ]

                ]
            ],
            'PersonLink'       => [
                'p' => [
                    'name'   => 'STRING',
                    'email'  => [
                        'type'  => 'LINK',
                        'class' => 'EmailAddressLink',
                    ],
                    'emails' => [
                        'type'  => 'LINKLIST',
                        'class' => 'EmailAddressLink',
                    ],
                    'phones' => [
                        'type'  => 'LINKMAP',
                        'class' => 'PhoneLink',
                    ]

                ],
            ],
            'TypedCollections' => [
                'p' => [
                    'stringList' => [
                        'type'  => 'EMBEDDEDLIST',
                        'class' => 'string',
                    ],
                    'intSet'     => [
                        'type'  => 'EMBEDDEDSET',
                        'class' => 'integer',
                    ],
                    'stringMap'  => [
                        'type'  => 'EMBEDDEDMAP',
                        'class' => 'string',
                    ],

                ],
            ],
            'PersonV'          => [
                'extends' => 'V',
                'p'       => [
                    'name' => 'STRING',
                ],
            ],
            'FollowedE'         => [
                'extends' => 'E',
            ],
            'LikedE'           => [
                'extends' => 'E',
            ],
            'PostV'            => [
                'extends' => 'V',
                'p'       => [
                    'title' => 'STRING',
                ]
            ],
        ];

        foreach ($classes as $class => $defn) {
            $query = "CREATE CLASS " . $class;
            if (isset($defn['extends'])) {
                $query .= " EXTENDS {$defn['extends']}";
            }

            $result = $this->client
                ->post('/command/' . $this->dbname . '/sql', $this->contentType, $query)
                ->send()
                ->json();

            $this->{$class} = $result['result'][0]['value'];
            $properties     = isset($defn['p']) ? $defn['p'] : null;
            if ($properties) {
                foreach ($properties as $name => $md) {
                    if (is_string($md)) {
                        $sql = sprintf('CREATE PROPERTY %s.%s %s', $class, $name, $md);
                    } else {
                        $sql = sprintf('CREATE PROPERTY %s.%s %s', $class, $name, $md['type']);

                        if (isset($md['class'])) {
                            $sql .= ' ' . $md['class'];
                        }
                    }

                    $this->client
                        ->post(sprintf("/command/%s/sql", $this->dbname), $this->contentType, $sql)
                        ->send();
                }

            }
        }

        return $this;

    }

    function load_fixtures() {

        //Insert  City
        $this->client->post('/document/' . $this->dbname, $this->contentType, '{"@class": "City", "name": "Rome" }')
                     ->send();


        //Insert  Address
        for ($i = 0; $i < 40; $i++) {
            $this->client->post('/document/' . $this->dbname, $this->contentType, sprintf('{"@class": "Address", "street" : "New street %d, Italy", "city":"#' . $this->City . ':0"}', $i))
                         ->send();
        }

        //Insert countries
        $countries = ['France', 'Italy', 'Spain', 'England', 'Ireland', 'Poland', 'Bulgaria', 'Portogallo', 'Belgium', 'Suisse'];
        foreach ($countries as $country) {
            $this->client->post('/document/' . $this->dbname, $this->contentType, '{"@class": "Country", "name": "' . $country . '" }')
                         ->send();
        }

        //Insert Profile
        $profiles = ['David', 'Alex', 'Luke', 'Marko', 'Rexter', 'Gremlin', 'Thinkerpop', 'Frames'];
        foreach ($profiles as $k => $profile) {
            $data             = new \stdClass();
            $data->{'@class'} = "Profile";
            $data->name       = $profile;
            $phones           = [];
            for ($i = 0; $i < 2; $i++) {
                $phone             = new \stdClass();
                $phone->{'@type'}  = 'd';
                $phone->{'@class'} = 'Phone';
                $phone->phone      = "555-201-$i" . str_repeat($k, 3);
                $phones []         = $phone;
            }
            $data->phones = $phones;
            $data         = json_encode($data);
            $res          = $this->client->post('/document/' . $this->dbname, $this->contentType, $data)->send();
            $body         = $res->getBody(true);
        }

        //Insert Comment
        $templateComment = '{"@class": "Comment", "body": "comment number %d" }';
        for ($i = 0; $i <= 5; $i++) {
            $this->client
                ->post('/document/' . $this->dbname, $this->contentType, sprintf($templateComment, $i, $i))
                ->send();
        }

        //Insert Post
        $templatePost = '{"@class": "Post", "id":"%d","title": "%d", "body": "Body %d", "comments":["#' . $this->Comment . ':3"] }';
        for ($i = 0; $i <= 5; $i++) {
            $this->client
                ->post('/document/' . $this->dbname, $this->contentType, sprintf($templatePost, $i, $i, $i))
                ->send();
        }
        $this->client
            ->post('/document/' . $this->dbname, $this->contentType, '{"@class": "Post", "id":"6","title": "titolo 6", "body": "Body 6", "comments":["#' . $this->Comment . ':2"] }')
            ->send();

        //Insert MapPoint
        $this->client
            ->post('/document/' . $this->dbname, $this->contentType, '{"@class": "MapPoint", "x": "42.573968", "y": "13.203125" }')
            ->send();

        $templatePerson = '{"@class": "PersonV", "name": "%s"}';
        foreach (['George', 'Cameron', 'Sydney'] as $name) {
            $this->client
                ->post('/document/' . $this->dbname, $this->contentType, sprintf($templatePerson, $name))
                ->send();
        }

        $templatePost = '{"@class": "PostV", "title": "%s"}';
        foreach (['Hello World', 'Welcome World', 'Another Post'] as $title) {
            $this->client
                ->post('/document/' . $this->dbname, $this->contentType, sprintf($templatePost, $title))
                ->send();
        }

        $likeE = 'CREATE EDGE LikedE FROM %s to %s';
        $this->client
            ->post(sprintf('/command/%s/sql', $this->dbname), $this->contentType, sprintf($likeE, '#26:0', '#29:0'))
            ->send();
        $followsE = 'CREATE EDGE FollowedE FROM %s to %s';
        $this->client
            ->post(sprintf('/command/%s/sql', $this->dbname), $this->contentType, sprintf($followsE, '#26:0', '#26:1'))
            ->send();
        $this->client
            ->post(sprintf('/command/%s/sql', $this->dbname), $this->contentType, sprintf($followsE, '#26:2', '#26:1'))
            ->send();
        $this->client
            ->post(sprintf('/command/%s/sql', $this->dbname), $this->contentType, sprintf($followsE, '#26:1', '#26:0'))
            ->send();

        return $this;
    }

}

$fixtures = new Fixtures('ODMTest', 'root', 'password');
$fixtures
    ->clean()
    ->create_classes()
    ->load_fixtures();

