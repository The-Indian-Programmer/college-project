# References

- Project requirements: supplied **Online Examination System Detailed Report, 64 pages**, retained in `output/pdf` of the parent documentation directory.
- PHP manual, [PDO](https://www.php.net/pdo), native prepared statements and exception handling.
- PHP manual, [Transactions and auto-commit](https://www.php.net/manual/en/pdo.transactions.php), transaction boundaries and rollback.
- PHP manual, [session_set_cookie_params](https://www.php.net/session-set-cookie-params), HttpOnly, Secure and SameSite cookie configuration before session start.
- MySQL manual, [InnoDB locking reads](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html), SELECT FOR UPDATE inside transactions.

The implementation uses those platform mechanisms to enforce the report's rules; it does not claim that documentation alone verifies runtime behavior. See verification.md for executed checks.
