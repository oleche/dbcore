dbcore
======
[![CircleCI](https://dl.circleci.com/status-badge/img/gh/oleche/dbcore/tree/master.svg?style=svg)](https://dl.circleci.com/status-badge/redirect/gh/oleche/dbcore/tree/master) [![Latest Stable Version](https://poser.pugx.org/geekcow/dbcore/v/stable)](https://packagist.org/packages/geekcow/dbcore) [![Total Downloads](https://poser.pugx.org/geekcow/dbcore/downloads)](https://packagist.org/packages/geekcow/dbcore)
# DBCore Engine Usage Documentation

DBCore is a simple, lightweight ORM (Object-Relational Mapping) engine designed for PHP applications. It provides an easy way to interact with databases using PHP objects, abstracting the direct database queries and operations.

## Getting Started

### Installation

To use DBCore in your project, you need to install it via Composer. Run the following command in your project directory:

```bash
composer require geekcow/dbcore
```

Ensure that your project is set up to autoload Composer dependencies. This is usually done by including the Composer-generated `autoload.php` file in your project:

```php
require 'vendor/autoload.php';
```

### Configuration

DBCore requires a configuration file named `config.ini` in your project directory. This file should contain your database connection settings. A sample configuration might look like this:

```ini
[database]
host = "localhost"
username = "your_username"
password = "your_password"
dbname = "your_database"
port = 3306
```

Make sure to adjust the settings according to your database server.

## Basic Usage

### Defining Entities

Entities in DBCore represent tables in your database. To define an entity, extend the `Entity` class and define your table schema within the class. For example:

```php
class User extends Entity {
    private $name_of_the_table = [
        'id' => ['type' => 'int', 'unique' => true, 'pk' => true],
        'username' => ['type' => 'string', 'length' => 32, 'unique' => true],
        'email' => ['type' => 'string', 'length' => 255]
    ];

    public function __construct() {
        parent::__construct($this->name_of_the_table, get_class($this));
    }
}
```

### Performing Database Operations


Once your entity is defined, you can perform various database operations such as fetch, insert, update, and delete.


#### Fetching Data

To fetch all records:

```php
$user = new User();
$result = $user->fetch();
```

To fetch a specific record by ID:

```php
$user->fetch_id(['id' => '1']);
```

#### Inserting Data

To insert a new record:

```php
$user = new User();
$user->columns['username'] = 'john_doe';
$user->columns['email'] = 'john@example.com';
$id = $user->insert();
```

#### Updating Data

To update an existing record:

```php
$user->fetch_id(['id' => '1']);
$user->columns['email'] = 'new_email@example.com';
$user->update();
```

#### Deleting Data

To delete a record:

```php
$user->fetch_id(['id' => '1']);
$user->delete();
```

## Advanced Usage

## Query Builders

DBCore provides a set of query builder classes to simplify the construction of SQL queries. These builders support a fluent interface, allowing you to chain method calls to incrementally build a query.

### QuerySelectBuilder

The `QuerySelectBuilder` is used to construct SELECT queries. It supports selecting columns, specifying conditions, ordering, and limiting results.

Example usage:

```php
<?php

    // Step 1: Instantiate the QuerySelectBuilder class
    $selectBuilder = new QuerySelectBuilder();
    
    // Step 2: Specify the table
    $selectBuilder->withTable('users');
    
    // Step 3: Specify the columns
    $selectBuilder->withColumns(['id', 'username', 'email']);
    
    // Step 4: (Optional) For counting rows, uncomment the next line
    // $selectBuilder->forCount(true);
    
    // Step 5: Specify grouping criteria (if needed)
    // $selectBuilder->withGroup('department_id');
    
    // Step 6: Add JOIN clause (if needed)
    // For demonstration, assuming we're not adding a JOIN clause here
    
    // Step 7: Generate the SQL query string
    $sqlQuery = $selectBuilder->toSql();
    
    echo $sqlQuery;
```

### QueryInsertBuilder

The `QueryInsertBuilder` is used to create INSERT queries. It allows specifying the table and the data to insert.


### QueryUpdateBuilder

The `QueryUpdateBuilder` is designed for constructing UPDATE queries. It enables specifying the table, the data to update, and conditions.


### QueryDeleteBuilder

The `QueryDeleteBuilder` facilitates the creation of DELETE queries. It supports specifying the table and conditions for deletion.


These builders abstract the complexity of query syntax, making your code cleaner and easier to maintain.

For more advanced features and usage, refer to the specific methods and properties within the DBCore engine's source code. The engine is designed to be extendable, allowing for customization to fit more complex application requirements.

## Contributing

Contributions to DBCore are welcome. To contribute:

1. Fork the repository.
2. Create your feature branch (`git checkout -b my-new-feature`).
3. Commit your changes (`git commit -am 'Add some feature'`).
4. Push to the branch (`git push origin my-new-feature`).
5. Create a new Pull Request.

Your contributions will help make DBCore a better ORM engine for the PHP community.
```