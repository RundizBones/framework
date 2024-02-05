<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Libraries;


/**
 * Database class that is working on PDO.
 * 
 * Usage:
 * <pre>
 * $Pdo = $this->Db->PDO();
 * $sth = $Pdo->prepare('SELECT * FROM `table`');
 * $sth->execute();
 * $result = $sth->fetchAll();
 * print_r($result);
 * 
 * // close connection
 * $sth = null;
 * $this->Db->disconnect();
 * </pre>
 * 
 * The advantage of PDO is many but one of them is it is support more drivers.
 * 
 * @link https://www.php.net/manual/en/book.pdo.php PDO document.
 * @link https://www.php.net/manual/en/pdo.drivers.php PDO drivers.
 * @link https://websitebeaver.com/php-pdo-vs-mysqli PDO vs MySQLi
 * @link https://code.tutsplus.com/tutorials/pdo-vs-mysqli-which-should-you-use--net-24059 PDO vs MySQLi
 * @since 0.1
 */
class Db
{


    /**
     * @var \Rdb\System\Config
     */
    protected $Config;


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * @var mixed Store current connection key that is in use.
     */
    protected $currentConnectionKey;


    /**
     * @var array The array of PDO connections.
     */
    protected $PDO = [];


    /**
     * @var \PDOStatement
     */
    protected $Sth;


    /**
     * DB class constructor.
     * 
     * You can load this class via framework's `Container` object named `Db`. Example: `$Db = $Container->get('Db');`.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;

        if ($Container->has('Config')) {
            $this->Config = $Container->get('Config');
            $this->Config->setModule('');
        } else {
            $this->Config = new \Rdb\System\Config();
        }

        $this->connect();
    }// __construct


    /**
     * Class de-constructor.
     */
    public function __destruct()
    {
        $this->disconnectAll();
    }// __destruct


    /**
     * Alter DB table structure. 
     * 
     * It can be create a table if not exists.<br>
     * Do not use this method for insertion or update.
     * 
     * @link https://developer.wordpress.org/reference/functions/dbdelta/ Source code copied from here.
     * @since 1.1.7
     * @param string $query A single SQL statement that should contain `CREATE TABLE`. Example:<pre>
     * CREATE TABLE `my_table` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'Identity',
            `name` varchar(100) DEFAULT NULL COMMENT 'The name.',
            PRIMARY KEY (`id`)
        ) DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='Demo table';
     * </pre>
     * @return array Return associative array where values are strings containing the results of the various update queries.
     * @throws \Exception Throw the exception if PDO errors.
     */
    public function alterStructure(string $query)
    {
        // normalize new line.
        $query = str_replace(["\r\n", "\r", PHP_EOL], "\n", $query);

        $cqueries   = []; // Creation queries.
        $for_update = [];

        if (preg_match('/CREATE TABLE( IF NOT EXISTS)? ([^ ]*)/', $query, $matches)) {
            $cqueries[trim($matches[2], '`')] = $query;
            $for_update[trim($matches[2], '`')] = 'Created table ' . trim($matches[2], '`');
        }
        unset($matches);

        $text_fields = array('tinytext', 'text', 'mediumtext', 'longtext');
        $blob_fields = array('tinyblob', 'blob', 'mediumblob', 'longblob');
        $int_fields = array('tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint');

        $db_version = $this->PDO()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $db_server_info = $this->PDO()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        foreach ($cqueries as $table => $originalQuery) {
            $originalAttribute = $this->PDO()->getAttribute(\PDO::ATTR_ERRMODE);
            $this->PDO()->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
            $tablefields = $this->query('DESCRIBE `' . $table . '`');
            $this->PDO()->setAttribute(\PDO::ATTR_ERRMODE, $originalAttribute);
            unset($originalAttribute);

            if (!$tablefields) {
                continue;
            }

            // Clear the field and index arrays.
            $cfields = [];
            $indices = [];
            $indices_without_subparts = [];

            // Get all of the field names in the query from between the parentheses.
            preg_match('|\((.*)\)|ms', $originalQuery, $match2);
            $qryline = trim($match2[1]);

            // Separate field lines into an array.
            $flds = explode("\n", $qryline);

            // For every field line specified in the query.
            foreach ($flds as $fld) {
                $fld = trim($fld, " \t\n\r\0\x0B,"); // Default trim characters, plus ','.

                // Extract the field name.
                preg_match('|^([^ ]*)|', $fld, $fvals);
                $fieldname = trim($fvals[1], '`');
                $fieldname_lowercased = strtolower($fieldname);

                // Verify the found field name.
                $validfield = true;
                switch ( $fieldname_lowercased ) {
                    case '':
                    case 'primary':
                    case 'index':
                    case 'fulltext':
                    case 'unique':
                    case 'key':
                    case 'spatial':
                        $validfield = false;

                        /*
                         * Normalize the index definition.
                         *
                         * This is done so the definition can be compared against the result of a
                         * `SHOW INDEX FROM $table_name` query which returns the current table
                         * index information.
                         */

                        // Extract type, name and columns from the definition.
                        // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
                        preg_match(
                                '/^'
                                . '(?P<index_type>'             // 1) Type of the index.
                                . 'PRIMARY\s+KEY|(?:UNIQUE|FULLTEXT|SPATIAL)\s+(?:KEY|INDEX)|KEY|INDEX'
                                . ')'
                                . '\s+'                         // Followed by at least one white space character.
                                . '(?:'                         // Name of the index. Optional if type is PRIMARY KEY.
                                . '`?'                      // Name can be escaped with a backtick.
                                . '(?P<index_name>'     // 2) Name of the index.
                                . '(?:[0-9a-zA-Z$_-]|[\xC2-\xDF][\x80-\xBF])+'
                                . ')'
                                . '`?'                      // Name can be escaped with a backtick.
                                . '\s+'                     // Followed by at least one white space character.
                                . ')*'
                                . '\('                          // Opening bracket for the columns.
                                . '(?P<index_columns>'
                                . '.+?'                 // 3) Column names, index prefixes, and orders.
                                . ')'
                                . '\)'                          // Closing bracket for the columns.
                                . '$/im',
                                $fld,
                                $index_matches
                        );
                        // phpcs:enable
                        // Uppercase the index type and normalize space characters.
                        $index_type = strtoupper(preg_replace('/\s+/', ' ', trim($index_matches['index_type'])));

                        // 'INDEX' is a synonym for 'KEY', standardize on 'KEY'.
                        $index_type = str_replace('INDEX', 'KEY', $index_type);

                        // Escape the index name with backticks. An index for a primary key has no name.
                        $index_name = ( 'PRIMARY KEY' === $index_type ) ? '' : '`' . strtolower($index_matches['index_name']) . '`';

                        // Parse the columns. Multiple columns are separated by a comma.
                        $index_columns = array_map('trim', explode(',', $index_matches['index_columns']));
                        $index_columns_without_subparts = $index_columns;

                        // Normalize columns.
                        foreach ($index_columns as $id => &$index_column) {
                            // Extract column name and number of indexed characters (sub_part).
                            // phpcs:disable Squiz.Strings.ConcatenationSpacing.PaddingFound -- don't remove regex indentation
                            preg_match(
                                    '/'
                                    . '`?'                      // Name can be escaped with a backtick.
                                    . '(?P<column_name>'    // 1) Name of the column.
                                    . '(?:[0-9a-zA-Z$_-]|[\xC2-\xDF][\x80-\xBF])+'
                                    . ')'
                                    . '`?'                      // Name can be escaped with a backtick.
                                    . '(?:'                     // Optional sub part.
                                    . '\s*'                 // Optional white space character between name and opening bracket.
                                    . '\('                  // Opening bracket for the sub part.
                                    . '\s*'             // Optional white space character after opening bracket.
                                    . '(?P<sub_part>'
                                    . '\d+'         // 2) Number of indexed characters.
                                    . ')'
                                    . '\s*'             // Optional white space character before closing bracket.
                                    . '\)'                  // Closing bracket for the sub part.
                                    . ')?'
                                    . '/',
                                    $index_column,
                                    $index_column_matches
                            );
                            // phpcs:enable
                            // Escape the column name with backticks.
                            $index_column = '`' . $index_column_matches['column_name'] . '`';

                            // We don't need to add the subpart to $index_columns_without_subparts
                            $index_columns_without_subparts[$id] = $index_column;

                            // Append the optional sup part with the number of indexed characters.
                            if (isset($index_column_matches['sub_part'])) {
                                $index_column .= '(' . $index_column_matches['sub_part'] . ')';
                            }
                        }

                        // Build the normalized index definition and add it to the list of indices.
                        $indices[] = "{$index_type} {$index_name} (" . implode(',', $index_columns) . ')';
                        $indices_without_subparts[] = "{$index_type} {$index_name} (" . implode(',', $index_columns_without_subparts) . ')';

                        // Destroy no longer needed variables.
                        unset($index_column, $index_column_matches, $index_matches, $index_type, $index_name, $index_columns, $index_columns_without_subparts);

                        break;
                }// endswitch;

                // If it's a valid field, add it to the field array.
                if ($validfield) {
                    $cfields[$fieldname_lowercased] = $fld;
                }
            }// endforaech; $flds
            unset($fld, $flds);
            unset($fieldname, $fieldname_lowercased, $fvals, $qryline, $match2, $validfield);
            // END For every field line specified in the query.

            // For every field in the table.
            foreach ($tablefields as $tablefield) {
                $tablefield_field_lowercased = strtolower($tablefield->Field);
                $tablefield_type_lowercased = strtolower($tablefield->Type);

                $tablefield_type_without_parentheses = preg_replace(
                        '/'
                        . '(.+)'       // Field type, e.g. `int`.
                        . '\(\d*\)'    // Display width.
                        . '(.*)'       // Optional attributes, e.g. `unsigned`.
                        . '/',
                        '$1$2',
                        $tablefield_type_lowercased
                );

                // Get the type without attributes, e.g. `int`.
                $tablefield_type_base = strtok($tablefield_type_without_parentheses, ' ');

                // If the table field exists in the field array...
                if (array_key_exists($tablefield_field_lowercased, $cfields)) {

                    // Get the field type from the query.
                    preg_match('|`?' . $tablefield->Field . '`? ([^ ]*( unsigned)?)|i', $cfields[$tablefield_field_lowercased], $matches);
                    $fieldtype = $matches[1];
                    $fieldtype_lowercased = strtolower($fieldtype);

                    $fieldtype_without_parentheses = preg_replace(
                            '/'
                            . '(.+)'       // Field type, e.g. `int`.
                            . '\(\d*\)'    // Display width.
                            . '(.*)'       // Optional attributes, e.g. `unsigned`.
                            . '/',
                            '$1$2',
                            $fieldtype_lowercased
                    );

                    // Get the type without attributes, e.g. `int`.
                    $fieldtype_base = strtok($fieldtype_without_parentheses, ' ');

                    // Is actual field type different from the field type in query?
                    if ($tablefield->Type != $fieldtype) {
                        $do_change = true;
                        if (in_array($fieldtype_lowercased, $text_fields, true) && in_array($tablefield_type_lowercased, $text_fields, true)) {
                            if (array_search($fieldtype_lowercased, $text_fields, true) < array_search($tablefield_type_lowercased, $text_fields, true)) {
                                $do_change = false;
                            }
                        }

                        if (in_array($fieldtype_lowercased, $blob_fields, true) && in_array($tablefield_type_lowercased, $blob_fields, true)) {
                            if (array_search($fieldtype_lowercased, $blob_fields, true) < array_search($tablefield_type_lowercased, $blob_fields, true)) {
                                $do_change = false;
                            }
                        }

                        if (in_array($fieldtype_base, $int_fields, true) && in_array($tablefield_type_base, $int_fields, true) && $fieldtype_without_parentheses === $tablefield_type_without_parentheses
                        ) {
                            /*
                             * MySQL 8.0.17 or later does not support display width for integer data types,
                             * so if display width is the only difference, it can be safely ignored.
                             * Note: This is specific to MySQL and does not affect MariaDB.
                             */
                            if (version_compare($db_version, '8.0.17', '>=') && !str_contains($db_server_info, 'MariaDB')
                            ) {
                                $do_change = false;
                            }
                        }

                        if ($do_change) {
                            // Add a query to change the column type.
                            $cqueries[] = "ALTER TABLE {$table} CHANGE COLUMN `{$tablefield->Field}` " . $cfields[$tablefield_field_lowercased];

                            $for_update[$table . '.' . $tablefield->Field] = "Changed type of {$table}.{$tablefield->Field} from {$tablefield->Type} to {$fieldtype}";
                        }
                    }

                    // Get the default value from the array.
                    if (preg_match("| DEFAULT '(.*?)'|i", $cfields[$tablefield_field_lowercased], $matches)) {
                        $default_value = $matches[1];
                        if ($tablefield->Default != $default_value) {
                            // Add a query to change the column's default value
                            $cqueries[] = "ALTER TABLE {$table} ALTER COLUMN `{$tablefield->Field}` SET DEFAULT '{$default_value}'";

                            $for_update[$table . '.' . $tablefield->Field] = "Changed default value of {$table}.{$tablefield->Field} from {$tablefield->Default} to {$default_value}";
                        }
                    }

                    // Remove the field from the array (so it's not added).
                    unset($cfields[$tablefield_field_lowercased]);
                } else {
                    // This field exists in the table, but not in the creation queries?
                }
            }// endforeach; $tablefields
            unset($tablefield);
            unset($tablefield_field_lowercased, $tablefield_type_base, $tablefield_type_lowercased, $tablefield_type_without_parentheses);
            unset($fieldtype, $fieldtype_base, $fieldtype_lowercased, $fieldtype_without_parentheses);
            unset($do_change);
            // END For every field in the table.

            // For every remaining field specified for the table.
            foreach ($cfields as $fieldname => $fielddef) {
                // Push a query line into $cqueries that adds the field to that table.
                $cqueries[] = "ALTER TABLE {$table} ADD COLUMN $fielddef";

                $for_update[$table . '.' . $fieldname] = 'Added column ' . $table . '.' . $fieldname;
            }// endforeach; $cfields
            unset($fieldname, $fielddef);

            // Index stuff goes here. Fetch the table index structure from the database.
            $tableindices = $this->PDO()->query("SHOW INDEX FROM {$table};");

            if ($tableindices) {
                // Clear the index array.
                $index_ary = array();

                // For every index in the table.
                foreach ($tableindices as $tableindex) {
                    $keyname = strtolower($tableindex->Key_name);

                    // Add the index to the index data array.
                    $index_ary[$keyname]['columns'][] = array(
                        'fieldname' => $tableindex->Column_name,
                        'subpart' => $tableindex->Sub_part,
                    );
                    $index_ary[$keyname]['unique'] = ( 0 == $tableindex->Non_unique ) ? true : false;
                    $index_ary[$keyname]['index_type'] = $tableindex->Index_type;
                }

                // For each actual index in the index array.
                foreach ($index_ary as $index_name => $index_data) {

                    // Build a create string to compare to the query.
                    $index_string = '';
                    if ('primary' === $index_name) {
                        $index_string .= 'PRIMARY ';
                    } elseif ($index_data['unique']) {
                        $index_string .= 'UNIQUE ';
                    }

                    if ('FULLTEXT' === strtoupper($index_data['index_type'])) {
                        $index_string .= 'FULLTEXT ';
                    }

                    if ('SPATIAL' === strtoupper($index_data['index_type'])) {
                        $index_string .= 'SPATIAL ';
                    }

                    $index_string .= 'KEY ';
                    if ('primary' !== $index_name) {
                        $index_string .= '`' . $index_name . '`';
                    }

                    $index_columns = '';

                    // For each column in the index.
                    foreach ($index_data['columns'] as $column_data) {
                        if ('' !== $index_columns) {
                            $index_columns .= ',';
                        }

                        // Add the field to the column list string.
                        $index_columns .= '`' . $column_data['fieldname'] . '`';
                    }

                    // Add the column list to the index create string.
                    $index_string .= " ($index_columns)";

                    // Check if the index definition exists, ignoring subparts.
                    $aindex = array_search($index_string, $indices_without_subparts, true);
                    if (false !== $aindex) {
                        // If the index already exists (even with different subparts), we don't need to create it.
                        unset($indices_without_subparts[$aindex]);
                        unset($indices[$aindex]);
                    }
                }
            }// endif;
            unset($aindex, $index_ary, $index_columns, $index_data, $index_name, $index_string);
            unset($keyname, $tableindex, $tableindices);

            // For every remaining index specified for the table.
            foreach ((array) $indices as $index) {
                // Push a query line into $cqueries that adds the index to that table.
                $cqueries[] = "ALTER TABLE {$table} ADD $index";

                $for_update[] = 'Added index ' . $table . ' ' . $index;
            }// endforeach; $indices
            unset($index, $indices);

            // Remove the original table creation query from processing.
            unset($cqueries[$table], $for_update[$table]);

            unset($tablefields);
        }// endforeach; $cqueries
        unset($originalQuery, $table);
        unset($cfields, $indices, $indices_without_subparts);
        unset($blob_fields, $int_fields, $text_fields);
        unset($db_server_info, $db_version);

        $allqueries = array_merge($cqueries, []);
        foreach ($allqueries as $qry) {
            $this->PDO()->query($qry);
        }// endforeach;
        unset($allqueries, $qry);

        return $for_update;
    }// alterStructure


    /**
     * Build placeholders and its values.
     * 
     * Example:
     * <pre>
        $sql = 'SELECT * FROM `table` WHERE 1';
        $values = [];
        $placeholders = [];

        $genWhereValues = $this->Db->buildPlaceholdersAndValues(['field1' => 'value1', 'field2' => 'value2']);
        if (isset($genWhereValues['values'])) {
            $values = array_merge($values, $genWhereValues['values']);
        }
        if (isset($genWhereValues['placeholders'])) {
            $placeholders = array_merge($placeholders, $genWhereValues['placeholders']);
        }
        unset($genWhereValues);

        $sql .= ' AND ' . implode(' AND ', $placeholders);
        unset($placeholders);

        $Sth = $this->Db->PDO()->prepare($sql);
        foreach ($values as $placeholder => $value) {
            $Sth->bindValue($placeholder, $value);
        }// endforeach;
        unset($placeholder, $sql, $value, $values);

        $Sth->execute();
     * </pre>
     * 
     * @link https://mariadb.com/kb/en/library/operators/ MariaDB operators
     * @link https://dev.mysql.com/doc/refman/8.0/en/non-typed-operators.html MySQL operators
     * @param array $data The associative array where key is field - value pairs.<br>
     *                                  To make condition `IS NULL`, set value to `null`. (depend on `$replaceNull` argument.)<br>
     *                                  To make condition `IS NOT NULL`, set value to `IS NOT NULL`. (depend on `$replaceNull` argument.)<br>
     *                                  If value is `\IS NOT NULL` then it will be escape as a string. (depend on `$replaceNull` argument.)<br>
     *                                  Value can contain custom comparison operator such as '>= 900' will be `field` >= 900.<br>
     *                                  Available custom comparison operators `&lt;=&gt;`, `&lt;&gt;`, `!=`, `&gt;=`, `&gt;`, `&lt;=`, `&lt;`
     * @param bool $replaceNull If set to `true` then it will replace condition described above that is about `IS NULL`, `IS NOT NULL`. Set it to `false` to use normal placeholders that is better for `UPDATE` query.
     * @param string $placeholderType The type of placeholder. Accepted: 'named', 'positional' (? mark). Default is 'named'.
     * @return array Return generated `placeholders` with `values` as associative array.
     */
    public function buildPlaceholdersAndValues(array $data, bool $replaceNull = true, string $placeholderType = 'named'): array
    {
        if ($placeholderType !== 'named' && $placeholderType !== 'positional') {
            $placeholderType = 'named';
        }

        $placeholders = [];
        $values = [];

        foreach ($data as $field => $value) {
            // make sure that field does not contain table.field. if it is contain table.field then set backtick correctly.
            if (is_scalar($field) && stripos($field, '.') !== false) {
                // if contain table.field.
                list($table, $fieldName) = explode('.', $field);
                if ($placeholderType === 'positional') {
                    $fieldPlaceholder = '?';
                } else {
                    $fieldPlaceholder = ':' . preg_replace('/[^\da-z\_]/iu', '_', $field);// make placeholder.name to be placeholder_name for use as sql `field = :placeholder_name`
                }
                $field = '`' . $table . '`.`' . $fieldName . '`';// set backtick for table and field correctly.
                unset($fieldName, $table);
            } else {
                // if does not contain table.field.
                if ($placeholderType === 'positional') {
                    $fieldPlaceholder = '?';
                } else {
                    $fieldPlaceholder = ':' . $field;
                }
                $field = '`' . $field . '`';
            }

            if (is_null($value) && $replaceNull === true) {
                $placeholders[] = $field . ' IS NULL';
            } elseif ($value === 'IS NOT NULL' && $replaceNull === true) {
                $placeholders[] = $field . ' IS NOT NULL';
            } elseif (is_scalar($value) && preg_match('/^(<=> |<> |!= |>= |> |<= |< )(.+)/iu', $value, $matches)) {
                $placeholders[] = $field . ' ' . trim($matches[1]) . ' ' . $fieldPlaceholder;
                if ($placeholderType === 'positional') {
                    $values[] = $matches[2];
                } else {
                    $values[$fieldPlaceholder] = $matches[2];
                }
            } else {
                if ($value === '\IS NOT NULL' && $replaceNull === true) {
                    // if where is escaped. the value should be `xxx = 'IS NOT NULL'` not `xxx IS NOT NULL`.
                    $value = str_replace('\IS NOT NULL', 'IS NOT NULL', $value);
                }

                $placeholders[] = $field . ' = ' . $fieldPlaceholder;
                if ($placeholderType === 'positional') {
                    $values[] = $value;
                } else {
                    $values[$fieldPlaceholder] = $value;
                }
            }
        }// endforeach;
        unset($field, $fieldPlaceholder, $value);

        return [
            'placeholders' => $placeholders,
            'values' => $values,
        ];
    }// buildPlaceholdersAndValues


    /**
     * Create a connection with connection key in the `db` configuration file.
     * 
     * @param mixed $connectionKey The DB config array key (connection key).
     * @return \PDO|null Return `\PDO` object if create new instance successfully. Return `null` if default connection key is not configured.
     * @throws \RuntimeException Throw the errors if anything goes wrong.
     */
    public function connect($connectionKey = 0)
    {
        if (isset($this->PDO[$connectionKey]) && $this->PDO[$connectionKey] instanceof \PDO) {
            // if this connection is already connected.
            return $this->PDO[$connectionKey];
        }

        global $Container;
        $Container = $this->Container;
        $dbConfig = $this->Config->get($connectionKey, 'db', [
            'dsn' => '',
            'username' => '',
            'passwd' => '',
            'options' => [],
            'tablePrefix' => '',
        ]);
        if ($connectionKey !== 0) {
            // if not default db.
            if (empty($dbConfig)) {
                // if not configured properly or not found any config value.
                // throw the error.
                throw new \RuntimeException('Unable to get DB configuration.');
            }
        } else {
            // if default db.
            if (
                (
                    !isset($dbConfig['dsn']) ||
                    (isset($dbConfig['dsn']) && empty($dbConfig['dsn']))
                ) ||
                (
                    !isset($dbConfig['username']) ||
                    (isset($dbConfig['username']) && empty($dbConfig['username']))
                )
            ) {
                // if it was not set the value.
                // skip the connection.
                return null;
            }
        }

        // create new connection.
        $this->PDO[$connectionKey] = new \PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['passwd'], $dbConfig['options']);
        unset($dbConfig);

        if ($this->PDO[$connectionKey] instanceof \PDO) {
            $this->currentConnectionKey = $connectionKey;

            if ($this->Container->has('Profiler')) {
                /* @var $Profiler \Rdb\System\Libraries\Profiler */
                $Profiler = $this->Container->get('Profiler');
                $row = $this->query('SELECT CONNECTION_ID() AS connectionID')->fetch(\PDO::FETCH_OBJ);
                $Profiler->Console->log('debug', 'DB connected. (connection id: ' . $row->connectionID . ')', __FILE__, __LINE__);
                unset($Profiler, $row);
            }
        }

        return $this->PDO[$connectionKey];
    }// connect


    /**
     * Convert character set and collation for table and columns.
     * 
     * @link https://developer.wordpress.org/reference/functions/maybe_convert_table_to_utf8mb4/ Source code copied from here.
     * @link https://mariadb.com/kb/en/unicode/ From MariaDB 10.6, utf8 is by default an alias for utf8mb3.
     * @link https://dev.mysql.com/doc/refman/8.0/en/charset-unicode-utf8mb3.html Historically, MySQL has used utf8 as an alias for utf8mb3; beginning with MySQL 8.0.28, utf8mb3 is used exclusively.
     * @param string $table The table name to work with.
     * @param mixed $connectionKey The DB config array key (connection key). Leave it to `0` to use default, set to `null` to use current connection key.
     * @param string|array $convertFrom Current character set(s) to convert from this to new one. Default is `['utf8', 'utf8mb3'].<br>
     *              This will lookup character set based on this and convert to the new one.<br>
     *              If its value is array, it will be looking that if current charset found matched one of them.
     * @param string $tableCharset The MySQL table `CHARSET` to be convert to. Default is "utf8mb4".<br>
     *              Do not change this as it might affect with the whole application.
     * @param string $tableCollate The MySQL table `COLLATE` to be convert to. Default is "utf8mb4_unicode_ci".<br>
     *              Do not change this as it might affect with the whole application.
     * @param array $columns The associative array of table columns to convert. Default is empty array.<br>
     *              If this is empty array then it will use the same character set and collation from the table.<br>
     *              The example of array structure: `array(
     *                      'columnName' => array('convertFrom' => 'utf8', 'collate' => utf8mb4_unicode_ci'), 
     *                      'anotherColumn' => ...
     *                  );`<br>
     *              The `convertFrom` sub array key is match first string in column collation.<br>
     *              For example: collation is latin1_general_ci and `convertFrom` is `latin1` then it is matched.
     * @return bool Return `true` if converted, `false` if there is nothing to convert.
     */
    public function convertCharsetAndCollation(
        string $table,
        $connectionKey = 0, 
        $convertFrom = ['utf8', 'utf8mb3'],
        string $tableCharset = 'utf8mb4', 
        string $tableCollate = 'utf8mb4_unicode_ci', 
        array $columns = []
    ): bool
    {
        if (is_null($connectionKey)) {
            $connectionKey = $this->currentConnectionKey;
        }

        $output = false;

        if (!empty($convertFrom) && (!empty($tableCharset) || !empty($tableCollate))) {
            // if convert table charset or collate was set, then working with it.
            $Sth = $this->PDO($connectionKey)->prepare('SHOW TABLE STATUS WHERE `Name` = :table');
            $Sth->bindValue(':table', $table);
            $Sth->execute();
            $result = $Sth->fetchObject();
            unset($Sth);

            if (
                is_object($result) && 
                !empty($result) && 
                isset($result->Collation) && 
                is_scalar($result->Collation)
            ) {
                list($currentCharset) = explode('_', $result->Collation);
                $currentCharset = strtolower($currentCharset);
                if (
                    (
                        (
                            is_string($convertFrom) &&
                            $currentCharset === strtolower($convertFrom)
                        ) ||
                        (
                            is_array($convertFrom) &&
                            in_array($currentCharset, array_map('strtolower', $convertFrom))
                        )
                    ) || 
                    $currentCharset === strtolower($tableCharset)
                ) {
                    if ($currentCharset === strtolower($tableCharset) && $tableCollate === $result->Collation) {
                        // if already converted for table.
                        $output = true;
                    } else {
                        // if current charset equals to convert from OR current charset equals to table charset but collation does not match.
                        // check that MySQL server is supported the selected character set.
                        $sqlCheck = 'SHOW CHARACTER SET WHERE `Charset` = :charset;';
                        $Sth2 = $this->PDO($connectionKey)->prepare($sqlCheck);
                        unset($sqlCheck);
                        $Sth2->bindValue(':charset', $tableCharset);
                        $Sth2->execute();
                        $resultCharset = count($Sth2->fetchAll());
                        unset($Sth2);
                        if ($resultCharset >= 1) {
                            $sqlCheck = 'SHOW COLLATION WHERE `Charset` = :charset AND `Collation` = :collation;';
                            $Sth2 = $this->PDO($connectionKey)->prepare($sqlCheck);
                            unset($sqlCheck);
                            $Sth2->bindValue(':charset', $tableCharset);
                            $Sth2->bindValue(':collation', $tableCollate);
                            $Sth2->execute();
                            $resultCollation = count($Sth2->fetchAll());
                            unset($Sth2);
                            if ($resultCollation >= 1) {
                                $mysqlCondition = true;
                            }
                        }
                        unset($resultCharset, $resultCollation);

                        if (isset($mysqlCondition) && $mysqlCondition === true) {
                            // if character set and collation is available.
                            if (empty($columns)) {
                                // if columns is empty, convert them all.
                                $sqlAlter = 'ALTER TABLE `' . $table . '` CONVERT TO CHARACTER SET :tableCharset COLLATE :tableCollate';
                            } else {
                                // if columns is not empty, just change for the table.
                                $sqlAlter = 'ALTER TABLE `' . $table . '` DEFAULT CHARACTER SET :tableCharset COLLATE :tableCollate';
                            }
                            // convert it.
                            $SthAlter = $this->PDO($connectionKey)->prepare($sqlAlter);
                            //$SthAlter->bindValue(':table', $table);
                            $SthAlter->bindValue(':tableCharset', $tableCharset);
                            $SthAlter->bindValue(':tableCollate', $tableCollate);
                            $SthAlter->execute();
                            $output = true;
                            unset($sqlAlter, $SthAlter);
                        }

                        unset($mysqlCondition);
                    }
                }

                unset($currentCharset);
            }
        }// endif convert table charset and collation.

        unset($result);

        if (!empty($columns)) {
            // if table columns is not empty.
            // check for available collations.
            $collations = array_unique(array_column($columns, 'collate'));
            $placeholders = str_repeat('?, ', count($collations));
            $placeholders = trim($placeholders, ', ');
            $availableCollations = [];

            $sqlCheck = 'SHOW COLLATION WHERE `Collation` IN (' . $placeholders . ');';
            $SthCheck = $this->PDO($connectionKey)->prepare($sqlCheck);
            unset($placeholders, $sqlCheck);
            $SthCheck->execute($collations);
            $resultCollation = $SthCheck->fetchAll();
            unset($SthCheck);
            if (is_array($resultCollation)) {
                foreach ($resultCollation as $row) {
                    $availableCollations[] = $row->Collation;
                }
                unset($row);
            }
            unset($collations, $resultCollation);

            if (!empty($availableCollations)) {
                // if checked that available collations was not emptied.
                $placeholders = str_repeat('?, ', count($columns));
                $placeholders = trim($placeholders, ', ');

                $sqlCols = 'SHOW FULL COLUMNS FROM `' . $table . '` WHERE `Field` IN (' . $placeholders . ');';
                $SthCols = $this->PDO($connectionKey)->prepare($sqlCols);
                unset($placeholders, $sqlCols);
                $SthCols->execute(array_keys($columns));
                $resultCols = $SthCols->fetchAll();
                unset($SthCols);

                if (is_array($resultCols) && !empty($resultCols)) {
                    // prepare beginning of alter table command.
                    $sql = 'ALTER TABLE `' . $table . '`';
                    $columnToConvert = 0;

                    foreach ($resultCols as $row) {
                        if (
                            isset($columns[$row->Field]) && 
                            isset($columns[$row->Field]['convertFrom']) && 
                            isset($columns[$row->Field]['collate']) && 
                            is_scalar($columns[$row->Field]['collate']) &&
                            isset($row->Collation) &&
                            is_scalar($row->Collation)
                        ) {
                            list($currentCharset) = explode('_', $row->Collation);
                            $currentCharset = strtolower($currentCharset);
                            list($newCharset) = explode('_', $columns[$row->Field]['collate']);
                            if (
                                $currentCharset === strtolower($columns[$row->Field]['convertFrom']) || 
                                $currentCharset === strtolower($newCharset)
                            ) {
                                if (
                                    $currentCharset === strtolower($newCharset) && 
                                    strtolower($row->Collation) === strtolower($columns[$row->Field]['collate'])
                                ) {
                                    // if already converted for column.
                                    $output = true;
                                } else {
                                    // if current charset equals to convert from OR current charset equals to column charset but collation does not match.
                                    if (in_array(strtolower($columns[$row->Field]['collate']), array_map('strtolower', $availableCollations))) {
                                        // if collate is also in available collations. it is able to convert.
                                        $output = true;
                                        $sql .= ' MODIFY COLUMN `' . $row->Field . '` ' . $row->Type;// example: MODIFY COLUMN `name` VARCHAR(100)
                                        $sql .= ' CHARACTER SET ' . $newCharset;// example: CHARACTER SET utf8
                                        $sql .= ' COLLATE ' . $columns[$row->Field]['collate'];// example: COLLATE utf8_unicode_ci
                                        $sql .= ',' . PHP_EOL;
                                        $columnToConvert++;
                                    }
                                }
                            }
                            unset($currentCharset, $newCharset);
                        }
                    }// endforeach;
                    unset($row);

                    $sql = trim(trim($sql), ',') . ';';
                    if ($columnToConvert > 0) {
                        $Sth = $this->PDO($connectionKey)->prepare($sql);
                        $Sth->execute();
                        unset($Sth);
                    }
                    unset($columnToConvert, $sql);
                }

                unset($resultCols);
            }// endif; !empty $availableCollations.

            unset($availableCollations);
        }// endif table columns is not empty.

        return $output;
    }// convertCharsetAndCollation


    /**
     * Get current connection key.
     * 
     * @return mixed Return current connection key. It maybe integer, string or it can be `null` if there is no current connection.
     */
    public function currentConnectionKey()
    {
        return $this->currentConnectionKey;
    }// currentConnectionKey


    /**
     * Delete data from DB table.
     * 
     * @link https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Connection.php#L643 Source code copied from here.
     * @param string $tableName The table name. This table name will NOT auto add prefix. The table name will be auto wrap with back-tick (`...`).
     * @param array $identifier The identifier for use in `WHERE` statement. It is associative array where column name is the key and its value is the value pairs.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     * @throws \InvalidArgumentException Throw the error if `$identifier` is incorrect value.
     */
    public function delete(string $tableName, array $identifier)
    {
        if (empty($identifier)) {
            throw new \InvalidArgumentException('The argument $identifier is required associative array column - value pairs.');
        }

        $columns = [];
        $placeholders = [];
        $values = [];
        $conditions = [];

        foreach ($identifier as $columnName => $value) {
            $columns[] = '`' . $columnName . '`';
            if (is_null($value)) {
                $conditions[] = '`' . $columnName . '` IS NULL';
            } else {
                $conditions[] = '`' . $columnName . '` = ?';
                $values[] = $value;
            }
        }// endforeach;
        unset($columnName, $value);

        $sql = 'DELETE FROM `' . $tableName . '` WHERE ' . implode(' AND ', $conditions);
        $this->Sth = $this->PDO($this->currentConnectionKey)->prepare($sql);
        unset($columns, $placeholders, $sql);

        return $this->Sth->execute($values);
    }// delete


    /**
     * Disconnect from DB on specific connection key.
     * 
     * @param mixed $connectionKey The DB config array key (connection key).
     */
    public function disconnect($connectionKey = 0)
    {
        $this->PDO[$connectionKey] = null;
        unset($this->PDO[$connectionKey]);

        if ($this->currentConnectionKey === $connectionKey) {
            $this->currentConnectionKey = null;
        }

        if (!is_array($this->PDO)) {
            $this->PDO = [];
            $this->currentConnectionKey = null;
            $this->Sth = null;
        }

        if ($this->Container->has('Profiler')) {
            /* @var $Profiler \Rdb\System\Libraries\Profiler */
            $Profiler = $this->Container->get('Profiler');
            $Profiler->Console->log('debug', 'DB disconnected.', __FILE__, __LINE__);
            unset($Profiler);
        }
    }// disconnect


    /**
     * Disconnect from DB on all connections.
     */
    public function disconnectAll()
    {
        if (is_array($this->PDO)) {
            foreach ($this->PDO as $key => $val) {
                $this->PDO[$key] = null;
                unset($this->PDO[$key]);
            }// endforeach;
            unset($key, $val);
        }

        if (!is_array($this->PDO)) {
            $this->PDO = [];
        }

        $this->currentConnectionKey = null;
        $this->Sth = null;

        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }// disconnectAll


    /**
     * Execute an SQL statement and return the number of affected rows.
     * 
     * This is overridden \PDO method to be able to log the query.
     * 
     * @see https://www.php.net/manual/en/pdo.exec.php
     * @param string $statement The SQL statement to prepare and execute.
     * @return int Returns the number of rows that were modified or deleted by the SQL statement you issued. If no rows were affected, \PDO::exec() returns 0. 
     *                      This function may return Boolean `false`, but may also return a non-Boolean value which evaluates to `false`.
     *                      Please read the section on Booleans for more information.
     *                      Use the === operator for testing the return value of this function.
     */
    public function exec(string $statement)
    {
        $Logger = new Db\Logger($this->Container);
        $Logger->queryLog($statement);
        unset($Logger);

        return $this->PDO($this->currentConnectionKey)->exec($statement);
    }// exec


    /**
     * Insert data into DB table.
     * 
     * This is just build the `INSERT` command, prepare, and then execute it.<br>
     * To get insert ID, you must call `$this->Db->PDO()->lastInsertId()` manually and must call before `commit()`.<br>
     * Example:
     * <pre>
     * $data = [
     *      'name' => 'Sarah',
     *      'lastname' => 'Connor',
     * ];
     * $PDO = $this->Db->PDO();
     * $PDO->beginTransaction();
     * $insertResult = $this->Db->insert('the_terminator', $data);
     * if ($insertResult === true) {
     *      $insertId = $PDO->lastInsertId();
     *      $PDO->commit();
     * } else {
     *      $PDO->rollBack();
     *      echo 'Insert failed.';
     * }
     * </pre>
     * 
     * @link https://www.php.net/manual/en/pdo.lastinsertid.php#85129 To get insert ID the `lastInsertId()` must be called before commit if you use transactions.
     * @link https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Connection.php#L749 Source code copied from here.
     * @param string $tableName The table name. This table name will NOT auto add prefix. The table name will be auto wrap with back-tick (`...`).
     * @param array $data The associative array where column name is the key and its value is the value pairs. The column name will be auto wrap with back-tick (`...`).
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     * @throws \InvalidArgumentException Throw the error if `$data` is invalid.
     */
    public function insert(string $tableName, array $data): bool
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('The argument $data is required associative array column - value pairs.');
        }

        $columns = [];
        $placeholders = [];
        $values = [];

        foreach ($data as $columnName => $value) {
            $columns[] = '`' . $columnName . '`';
            $placeholders[] = '?';
            $values[] = $value;
        }// endforeach;
        unset($columnName, $value);

        $sql = 'INSERT INTO `' . $tableName . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';

        $Logger = new Db\Logger($this->Container);
        $Logger->queryLog($sql, $values);
        unset($Logger);

        $this->Sth = $this->PDO($this->currentConnectionKey)->prepare($sql);
        unset($columns, $placeholders, $sql);

        return $this->Sth->execute($values);
    }// insert


    /**
     * Get PDO object instance on specific connection key.
     * 
     * @param mixed $connectionKey The DB config array key (connection key). Leave it to `null` to use current connection key.
     * @return \PDO|null Return `\PDO` object if there is already connection. Return `null` for otherwise.
     */
    public function PDO($connectionKey = null)
    {
        if ($connectionKey === null) {
            $connectionKey = $this->currentConnectionKey;
        }

        if (isset($this->PDO[$connectionKey]) && $this->PDO[$connectionKey] instanceof \PDO) {
            // if this connection is already connected.
            return $this->PDO[$connectionKey];
        }

        return null;
    }// PDO


    /**
     * Get PDO statement after called `insert()`, `update()`, `delete()`.
     * 
     * @return \PDOStatement|null Return `\PDOStatement` object if exists, `null` if not exists.
     */
    public function PDOStatement()
    {
        return $this->Sth;
    }// PDOStatement


    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object.
     * 
     * This is overridden \PDO method to be able to log the query.
     * 
     * @see https://www.php.net/manual/en/pdo.query.php
     * @param string $statement The SQL statement to prepare and execute. Data inside the query should be properly escaped.
     * @return \PDOStatement|bool Returns a `\PDOStatement` object, or `false` on failure
     */
    public function query(string $statement)
    {
        $Logger = new Db\Logger($this->Container);
        $Logger->queryLog($statement);
        unset($Logger);

        return call_user_func_array([$this->PDO($this->currentConnectionKey), 'query'], func_get_args());
    }// query


    /**
     * Set current connection key to the new key.
     * 
     * This will not connect to specific key but change the current connection to specific key.<br>
     * The specific connection must be already connected via `connect()` method otherwise it will be return `false`.
     * 
     * @param mixed $connectionKey The DB config array key (connection key).
     * @return bool Return `true` if it is already connected and found this connection key in connected data. Return `false` for otherwise.
     */
    public function setCurrentConnectionKey($connectionKey = 0): bool
    {
        if (isset($this->PDO[$connectionKey]) && $this->PDO[$connectionKey] instanceof \PDO) {
            $this->currentConnectionKey = $connectionKey;
            return true;
        }

        return false;
    }// setCurrentConnectionKey


    /**
     * Get table name with prefix based on configuration for specific connection key.
     * 
     * For example: table name is `users` and prefix is `rdb_` then it will be return `rdb_users`.
     * 
     * @param string $tableName The table name without prefix.
     * @param mixed $connectionKey The DB config array key (connection key).
     * @return string Return table name with prefix.
     */
    public function tableName(string $tableName, $connectionKey = 0): string
    {
        $dbConfig = $this->Config->get($connectionKey, 'db', [
            'tablePrefix' => '',
        ]);

        return $dbConfig['tablePrefix'] . $tableName;
    }// tableName


    /**
     * Update data into DB table.
     * 
     * @link https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Connection.php#L714 Source code copied from here.
     * @param string $tableName The table name. This table name will NOT auto add prefix. The table name will be auto wrap with back-tick (`...`).
     * @param array $data The associative array where column name is the key and its value is the value pairs. The column name will be auto wrap with back-tick (`...`).
     * @param array $identifier The identifier for use in `WHERE` statement. It is associative array where column name is the key and its value is the value pairs.
     * @return bool Return PDOStatement::execute(). Return `true` on success, `false` for otherwise.
     * @throws \InvalidArgumentException Throw the error if `$data` or `$identifier` is incorrect value.
     */
    public function update(string $tableName, array $data, array $identifier): bool
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('The argument $data is required associative array column - value pairs.');
        }

        if (empty($identifier)) {
            throw new \InvalidArgumentException('The argument $identifier is required associative array column - value pairs.');
        }

        $values = [];
        $sets = [];
        $conditions = [];

        $genData = $this->buildPlaceholdersAndValues($data, false, 'positional');
        if (isset($genData['values'])) {
            $values = array_merge($values, $genData['values']);
        }
        if (isset($genData['placeholders'])) {
            $sets = array_merge($sets, $genData['placeholders']);
        }
        unset($genData);

        $genIdentifier = $this->buildPlaceholdersAndValues($identifier, true, 'positional');
        if (isset($genIdentifier['values'])) {
            $values = array_merge($values, $genIdentifier['values']);
        }
        if (isset($genIdentifier['placeholders'])) {
            $conditions = array_merge($conditions, $genIdentifier['placeholders']);
        }
        unset($genIdentifier);

        $sql = 'UPDATE `' . $tableName . '` SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $conditions);
        $this->Sth = $this->PDO($this->currentConnectionKey)->prepare($sql);
        unset($sql);

        unset($conditions, $sets);

        return $this->Sth->execute($values);
    }// update


}
