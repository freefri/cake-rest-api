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
        'routePath2' => '/api/v2',
    ],
];
```

The following configuration can be used:
- `tablePrefix`: (optional) in case you want to add a prefix for you database tables, e.g. `myplugin_users`. Can be empty string.
- `routePath`: Definition for the beginning of the path for all routes in the plugin
- `routePath2`: (optional) a second path prefix under which all the plugin routes are additionally registered, e.g. to expose the same routes under both `/api/v1` and `/api/v2`. When empty or not set, only `routePath` is used.

## Swagger
In order to make swagger UI with openapi description available, a new controller `SwaggerJsonController` must be
created, with the corresponding route definition. The method `getContent` can be overwritten in this controller
in order to add customization for the main spec info (title, description, version, etc.). Swagger generation can be
configured as described in the Configuration section above.

In any controller test the function `$this->skipNextRequestInSwagger()` can be used to do not add the next request.

### Swagger configuration keys

All of the following live under the `Swagger` key in `config/app.php` (read via `Configure::read('Swagger.*')`). Every key is optional and falls back to a sensible default when not set.

- `readerClass`: Fully qualified class name of the reader used to build the spec. Default: `\RestApi\Lib\Swagger\FileReader\SwaggerReader`.
- `apiVersion`: Version string shown under `info.version` in the generated spec. Default: an auto-generated, date-based version (e.g. `9.24.121530`).
- `jsonDir`: Directory where the generated json spec files are stored and read from. Default: `ROOT/<swaggerPath>/`.
- `fullFileDir`: Directory where the single combined (full) json spec is written. When `null`, falls back to the `SWAGGER_RELATIVE_FILE_DIR` env variable (appended to `ROOT`); when `false` or empty the full file is not generated; when `true` (or `'1'`) the default `jsonDir` is used.
- `fullFileName`: File name for the combined full json spec. When empty, falls back to the `SWAGGER_FULL_FILE_NAME` env variable, then to the built-in default (`SwaggerReader::FULL_SWAGGER_JSON`).
- `identifyEntities`: Boolean. When truthy, entities expose their class name as a virtual field in the serialized output (used to identify entity types in the spec).
- `displayErrorResponses`: Boolean. When falsy (default), non-success responses (status codes outside 200–399) are removed from the spec; set truthy to keep error responses.
- `displayMethodsWithoutOk`: Boolean. When falsy (default), methods that have no success (200–399) response are removed from the spec; set truthy to keep them.
- `addRoutePrefix`: String prepended to every route path collected for the spec.
- `acceptLanguage`: Description (or value) for the `Accept-Language` header in generated requests. When `false` the header is omitted; when empty a default descriptive string is used.

### Swagger environment variables

Some Swagger behaviour is configured via environment variables (`getenv`) instead of `config/app.php`:

- `SWAGGER_NAMESPACE_TO_REMOVE` (and `SWAGGER_NAMESPACE_TO_REMOVE_1` … `SWAGGER_NAMESPACE_TO_REMOVE_20`): Namespace prefixes to strip from generated type/schema names. When building a type name the entity class name has `Model\Entity\` removed and every remaining `\` replaced with `Ns`, so e.g. `RestApi\Model\Entity\LogEntry` becomes `RestApiNsLogEntry`. Setting `SWAGGER_NAMESPACE_TO_REMOVE=RestApi` strips the `RestApi\` prefix first, yielding just `LogEntry`. Note this only removes leading namespace prefixes, e.g. `SWAGGER_NAMESPACE_TO_REMOVE=Course` turns `CourseNsRanking` (from `Course\Ranking`) into `Ranking`. Up to 21 values can be configured (the unsuffixed key plus suffixes `_1` through `_20`).
- `SWAGGER_RELATIVE_FILE_DIR`: Fallback directory for the combined full json spec, appended to `ROOT`. Only used when `Swagger.fullFileDir` is `null`.
- `SWAGGER_FULL_FILE_NAME`: Fallback file name for the combined full json spec. Only used when `Swagger.fullFileName` is empty (before falling back to the built-in `FULLswagger.json`).

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
