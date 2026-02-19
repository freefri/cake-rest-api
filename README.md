# RestApi package for CakePHP

Rest API for CakePHP 5.x

## Configuration

- Some configuration can be done using env variables (search for `env(` in the project to find usages).

- Some configuration when working with plugins can be done from the main configuration (`config/app.php` file).

  - Using the key `Swagger` is optional, but can be helpful to customize some directories (search for `read('Swagger` for details)
  - As sibling from the main `App` configuration use the namespace of the plugin followed by the word `Plugin`.

For example, if your plugin namespace is called Example, create the following config file:

```
return [
    'debug' => false,
    'App' => [ ... ],
    'Swagger' => [ ... ]
    'ExamplePlugin' => [
        'tablePrefix' => 'example_',
        'routePath' => '/api/v1',
    ],
];
```

The following configuration can be used:
- `tablePrefix`: (optional) in case you want to add a prefix for you database tables, e.g. `myplugin_users`. Can be empty string.
- `routePath`: Definition for the beginning of the path for all routes in the plugin

## Swagger
In order to make swagger UI with openapi description available, a new controller `SwaggerJsonController` must be
created, with the corresponding route definition. The method `getContent` can be overwritten in this controller
in order to add customization for the main spec info (title, description, version, etc.). Swagger generation can be
configured as described in the Configuration section above.

In any controller test the function `$this->skipNextRequestInSwagger()` can be used to do not add the next request.

## Logs in database

Logs in database can be easily enabled.

Just add to the `Log` section in  `config/app.php` the className `\RestApi\Lib\DatabaseLog::class` and add a migration with phinx:

```
$this->table('log_entries', ['collation' => 'utf8mb4_general_ci', 'id' => false])
    ->addColumn('id', 'biginteger', [
        'autoIncrement' => true,
        'default' => null,
        'limit' => null,
        'null' => false,
        'signed' => false,
    ])
    ->addPrimaryKey(['id'])
    ->addColumn('type', 'string', [
        'default' => null,
        'limit' => 50,
        'null' => true,
    ])
    ->addColumn('title', 'string', [
        'default' => null,
        'limit' => 30,
        'null' => true,
    ])
    ->addColumn('message', 'text', [
        'default' => null,
        'limit' => null,
        'null' => true,
    ])
    ->addColumn('environment', 'string', [
        'default' => null,
        'limit' => 100,
        'null' => true,
    ])
    ->addColumn('server', 'text', [
        'default' => null,
        'limit' => null,
        'null' => true,
    ])
    ->addColumn('created', 'datetime', [
        'default' => null,
        'limit' => null,
        'null' => true,
    ])
    ->addIndex(
        [
            'created',
        ]
    )
    ->create();
```

## License
The source code for the site is licensed under the **MIT license**, which you can find in the [LICENSE](../LICENSE/) file.
