# UPGRADE FROM 1.x to 2.0

# QueryBuilder - Count with DBAL QueryBuilder

Some changes when using the "count" feature with a **DBAL** query builder :

* The default behavior is no longer `count_by_sub_request` but is now `count_by_select_all` (new behavior that performs a `SELECT count(*) FROM ...`).
* When the `count_by_sub_request` behavior is used, the `connection` option (an instance of `Doctrine\DBAL\Connection`) is now required.

Refer to the [full documentation](doc/count.md) for available options.
