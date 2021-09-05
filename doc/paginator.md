# QueryBuilder - Count

Create a paginator.

## With DBAL QueryBuilder

```php
use Ecommit\DoctrineUtils\Paginator\DoctrineDBALPaginator;

$paginator = new DoctrineDBALPaginator([
    'query_builder' => $queryBuilder,
    //Options - See below
]);
```

**Returns** : `Ecommit\DoctrineUtils\Paginator\DoctrineDBALPaginator` object

**Available options :**

| Option | Type | Required | Default value | Description |
| --- | --- | --- | --- | --- |
| **query_builder** | `Doctrine\DBAL\Query\QueryBuilder` | Yes | | QueryBuilder |
| **page** | Integer | No | 1 | Current page |
| **max_per_page** | Integer | No | 100 | Max elements per page |
| **count** | Integer or array | No | [ ] | <ul><li>If integer : Manual value of the number of results found</li><li>If array: [Count options](count.md)</li></ul> |
| **by_identifier** | String or null | No | null | If not null, perform a query with `DISTINCT` (with this identifier value) to find all ids of the rows in from on the current page. And Perform a WHERE IN query to get all results for the current page. |


**Available methods :** See [API documentation](https://github.com/e-commit/paginator/blob/master/src/PaginatorInterface.php)


## With ORM QueryBuilder

```php
use Ecommit\DoctrineUtils\Paginator\DoctrineORMPaginator;

$paginator = new DoctrineORMPaginator([
    'query_builder' => $queryBuilder,
    //Options - See below
]);
```

**Returns** : `Ecommit\DoctrineUtils\Paginator\DoctrineORMPaginator` object

**Available options :**

| Option | Type | Required | Default value | Description |
| --- | --- | --- | --- | --- |
| **query_builder** | `Doctrine\ORM\QueryBuilder` | Yes | | QueryBuilder |
| **page** | Integer | No | 1 | Current page |
| **max_per_page** | Integer | No | 100 | Max elements per page |
| **count** | Integer or array | No | [ ] | <ul><li>If integer : Manual value of the number of results found</li><li>If array: [Count options](count.md)</li></ul> |
| **by_identifier** | String or null | No | null | If not null, perform a query with `DISTINCT` (with this identifier value) to find all ids of the rows in from on the current page. And Perform a WHERE IN query to get all results for the current page. |
| **simplified_request** | Bool or null | No | <ul><li>`true` if `by_identifier` option is null</li><li>`null` if `by_identifier` option is not null</li></ul> | Remove unnecessary instructions. *Used Only if `by_identifier` option is null* |
| **fetch_join_collection** | Bool or null | No | <ul><li>`false` if `by_identifier` option is null</li><li>`null` if `by_identifier` option is not null</li></ul> | See [Doctrine documentation](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/tutorials/pagination.html). *Used Only if `by_identifier` option is null* |


**Available methods :** See [API documentation](https://github.com/e-commit/paginator/blob/master/src/PaginatorInterface.php)
