# QueryBuilder - Count

Count the number of results found.

## With DBAL QueryBuilder

```php
use Ecommit\DoctrineUtils\Paginator\DoctrinePaginatorBuilder;

$count = DoctrinePaginatorBuilder::countQueryBuilder([
    'query_builder' => $queryBuilder,
    //Options - See below
]);
```

**Available options :**

| Option             | Type | Required | Default value | Description                                                                                                                                                                                                                                                                                                           |
|--------------------| --- | --- | --- |-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **query_builder**  | `Doctrine\DBAL\Query\QueryBuilder` | Yes |  | QueryBuilder                                                                                                                                                                                                                                                                                                          |
| **behavior**       | String | No | `count_by_select_all` | Method used to count results. Available values: <ul><li>`count_by_alias`: Use a alias (`SELECT count(alias) FROM ...`) *(`alias` option is required)*</li><li>`count_by_sub_request` : Use a sub request *(`connection` option is required)*</li><li>`count_by_select_all`: Use `SELECT count(*) FROM ...`)</li></ul> |
| **alias**          | String | Only if `behavior = count_by_alias` | | Can only be used when `behavior = count_by_alias`                                                                                                                                                                                                                                                                     |
| **distinct_alias** | Bool | No | `true` | Use `DISTINCT` (`SELECT count(DISTINCT alias) FROM ...`). Can only be used when `behavior = count_by_alias`                                                                                                                                                                                                           |
| **connection**     | `Doctrine\DBAL\Connection` | Only if `behavior = count_by_sub_request` | | Can only be used when `behavior = count_by_sub_request`                                                                                                                                                                                                                                                                     |


## With ORM QueryBuilder

```php
use Ecommit\DoctrineUtils\Paginator\DoctrinePaginatorBuilder;

$count = DoctrinePaginatorBuilder::countQueryBuilder([
    'query_builder' => $queryBuilder,
    //Options - See below
]);
```

**Available options :**

| Option | Type | Required | Default value | Description |
| --- | --- | --- | --- | --- |
| **query_builder** | `Doctrine\ORM\QueryBuilder` | Yes |  | QueryBuilder |
| **behavior** | String | No | `orm` | Method used to count results. Available values: <ul><li>`count_by_alias`: Use a alias (`SELECT count(alias) FROM ...`) *(`alias` option is required)*</li><li>`count_by_sub_request` : Use a sub request</li><li>`orm` : Use Doctrine engine</li></ul> |
| **alias** | String | Only if `behavior = count_by_alias` | | Can only be used when `behavior = count_by_alias` |
| **distinct_alias** | Bool | No | `true` | Use `DISTINCT` (`SELECT count(DISTINCT alias) FROM ...`). Can only be used when `behavior = count_by_alias` |
| **simplified_request** | Bool | No | `true` | Remove unnecessary instructions (eg: `ORDER BY`) for counting. Can only be used when `behavior = orm` |
