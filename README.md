# MysqlWrapper

**MysqlWrapper** is a PHP library designed to simplify database operations with MySQL. This package provides a straightforward and elegant interface for performing common database queries, including insert, update, delete, and select operations.

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
    - [DbConnection](#dbconnection)
    - [DeleteQuery](#deletequery)
    - [InsertQuery](#insertquery)
    - [UpdateQuery](#updatequery)
    - [SelectQuery](#selectquery)
- [Contributing](#contributing)
- [License](#license)

## Installation

Note: This software is still considered in beta. Use at your own risk 

You can install the package via Composer. Run the following command:

```bash
composer require patrick-hull/mysqlwrapper
```

## Usage

### DbConnection

The `DbConnection` class is responsible for establishing a connection to the MySQL database.

```php
use PatrickHull\MysqlWrapper\DbConnection;

$dbConnection = new DbConnection('server_address', 'username', 'password');
$link = $dbConnection->connect();
```

### DeleteQuery

The `DeleteQuery` class allows you to delete records from a specified database table based on criteria.

```php
use PatrickHull\MysqlWrapper\DeleteQuery;

$deleteQuery = new DeleteQuery($link);
$deleteQuery->database = 'your_database';
$deleteQuery->table = 'your_table';
$deleteQuery->criteria = ['column_name' => 'value'];

$result = $deleteQuery->Execute();
```

### InsertQuery

The `InsertQuery` class enables you to insert new records into a specified database table.

```php
use PatrickHull\MysqlWrapper\InsertQuery;

$insertQuery = new InsertQuery($link);
$insertQuery->database = 'your_database';
$insertQuery->table = 'your_table';
$insertQuery->data = ['column1' => 'value1', 'column2' => 'value2'];

$result = $insertQuery->Execute();
```

### UpdateQuery

The `UpdateQuery` class is used to update existing records in a database table based on specified criteria.

```php
use PatrickHull\MysqlWrapper\UpdateQuery;

$updateQuery = new UpdateQuery($link);
$updateQuery->database = 'your_database';
$updateQuery->table = 'your_table';
$updateQuery->data = ['column1' => 'new_value'];
$updateQuery->criteria = ['column_name' => 'value'];

$result = $updateQuery->Execute();
```

### SelectQuery

The `SelectQuery` class allows you to retrieve data from a database table with various filtering options.

```php
use PatrickHull\MysqlWrapper\SelectQuery;

$selectQuery = new SelectQuery($link);
$selectQuery->database = 'your_database';
$selectQuery->table = 'your_table';
$selectQuery->criteria = ['column_name' => 'value'];
$selectQuery->limit = 10;

$result = $selectQuery->Execute();
```
In the SelectQuery class, you are also able to leverage Redis as a caching solution. See an example below:

```php
use PatrickHull\MysqlWrapper\SelectQuery;

$selectQuery = new SelectQuery($link);
$selectQuery->database = 'your_database';
$selectQuery->table = 'your_table';
$selectQuery->criteria = ['column_name' => 'value'];
$selectQuery->redis = true;
$selectQuery->redis_pw = "password";
$selectQuery->cache_duration = "200" //Number of Seconds the cache is live
$selectQuery->limit = 10;

$result = $selectQuery->Execute();
```



## Contributing

Contributions are welcome! Please submit a pull request or open an issue to discuss potential improvements.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---