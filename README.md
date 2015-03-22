# OrientDB PHP Library

[![Build Status](https://secure.travis-ci.org/stuartcarnie/orientdb-odm.png?branch=master)](http://secure.travis-ci.org/stuartcarnie/orientdb-odm)
[![Total Downloads](https://poser.pugx.org/stuartcarnie/orientdb-odm/downloads.png)](https://packagist.org/packages/stuartcarnie/orientdb-odm)
[![Latest Stable Version](https://poser.pugx.org/stuartcarnie/orientdb-odm/v/stable.png)](https://packagist.org/packages/stuartcarnie/orientdb-odm)

## What's OrientDB?

A set of tools to use and manage any OrientDB instance from PHP.

Orient includes:

* the HTTP protocol binding
* the query builder
* the data mapper ( Object Graph Mapper )

If you don't know [OrientDB](http://www.orientechnologies.com/) take a look at its [Documentation](http://www.orientechnologies.com/docs/last/).


## Tests

The test suite can be launched simply by executing `phpunit` from the root directory of the repository.

By default the suite does not perform integration tests to verify the correct behaviour of our implementation against a running instance of OrientDB.
Since integration tests are marked using the [@group](http://www.phpunit.de/manual/current/en/appendixes.annotations.html#appendixes.annotations.group)
annotation, they can be enabled by default via `phpunit.xml` by adding a comment to the `integration` group in the list of excluded groups or,
if you just want to execute them on a single execution basis, first load fixtures with this script

```
php ./test/Integration/fixtures/load.php
```

followeb by launching the suite with the additional `--group` argument:

```
phpunit --group __nogroup__,integration
```

## Requirements

These are the requirements in order to use the library:

* PHP >= 5.5.x
* OrientDB >= 2.x

In order to launch the test suite PHPUnit 3.6 is required.


## Tracker & software lifecycle

See: https://github.com/stuartcarnie/orientdb-odm/issues


## Further documentation

If you want to take a look at a fancy PHPDoc documentation you can use doxygen:

```
sudo apt-get install doxygen
```

and then use the script provided under the docs directory:

```
doxygen docs/orient.d
```

which will generate technical documentation under the folder docs/html.
