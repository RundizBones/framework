<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Tests\System\Libraries;


class DbTest extends \Rdb\Tests\BaseTestCase
{


    /**
     * @var mixed
     */
    private $connectKey = null;


    /**
     * @var \Rdb\System\Libraries\Db
     */
    private $Db;


    /**
     * @var string
     */
    private $testTableName;


    private function dropTestTableBeforeDisconnect()
    {
        if ($this->connectKey === null || is_null($this->Db->PDO($this->connectKey))) {
            return ;
        }

        $sql = 'DROP TABLE IF EXISTS `' . $this->testTableName . '`';
        $this->Db->PDO($this->connectKey)->exec($sql);
        unset($sql);
    }// dropTestTableBeforeDisconnect


    private function removeNewlineAndSpaces(string $string): string
    {
        $string = str_replace(["\r\n", "\r", "\n"], '', $string);
        $string = str_replace(['    ', '   ', '  ', ' '], ' ', $string);
        return $string;
    }// removeNewlineAndSpaces


    public function setup()
    {
        $Config = new \Rdb\System\Config();
        $this->Db = new \Rdb\System\Libraries\Db(new \Rdb\System\Container);

        $dbConfigVals = $Config->get('ALL', 'db', []);
        foreach ($dbConfigVals as $key => $item) {
            if (
                isset($item['dsn']) && 
                !empty($item['dsn']) &&
                isset($item['username']) &&
                !empty($item['username'])
            ) {
                // if found configured db.
                $connectResult = $this->Db->connect($key);
                if ($connectResult instanceof \PDO) {
                    $this->connectKey = $key;
                }
                unset($connectResult);
                break;
            }
        }// endforeach;
        unset($dbConfigVals, $item, $key);

        if ($this->connectKey === null) {
            $this->markTestIncomplete('Unable to connect to DB.');
        } else {
            $this->testTableName = $this->Db->tableName('temp_table_for_test_' . time() . round(microtime(true) * 1000));
            $sql = 'CREATE TABLE IF NOT EXISTS `' . $this->testTableName . '` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(255) DEFAULT NULL,
                  `lastname` varchar(255) DEFAULT NULL,
                  PRIMARY KEY (`id`)
                ) DEFAULT CHARSET=utf8;';
            $this->Db->PDO($this->connectKey)->exec($sql);
            $sql = 'INSERT INTO `' . $this->testTableName . '` (name, lastname) VALUES (?, ?)';
            $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
            $Sth->execute(['Jack', 'Nicholson']);
            $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
            $Sth->execute(['ธงไชย', 'แมคอินไตย']);
            $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
            $Sth->execute(['Tom', 'Cruise']);
            unset($sql, $Sth);
        }
    }// setup


    public function tearDown()
    {
        $this->dropTestTableBeforeDisconnect();
        $this->Db->disconnectAll();
        $this->connectKey = null;
    }// tearDown


    public function testBasicPDOQuery()
    {
        $sql = 'SELECT * FROM `' . $this->testTableName . '`';
        $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        unset($sql, $Sth);
        $this->assertTrue(is_array($result));
        $this->assertCount(3, $result);

        $sql = 'SELECT * FROM `' . $this->testTableName . '` WHERE 1 AND `id` = \'1\' LIMIT 0, 1';
        $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($sql, $Sth);
        $this->assertTrue(is_object($result));

        $sql = 'SELECT * FROM `' . $this->testTableName . '` WHERE :defaultWhere AND `id` = :id LIMIT 0, 1';
        $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
        $Sth->bindValue(':defaultWhere', 1);
        $Sth->bindValue(':id', '1');
        $Sth->execute();
        $result = $Sth->fetchObject();
        $Sth->closeCursor();
        unset($sql, $Sth);
        $this->assertTrue(is_object($result));
    }// testBasicPDOQuery


    public function testBuildPlaceholdersAndValues()
    {
        // test named placeholders. --------------------------------------------------------------
        $where = [];
        $where['field'] = 'value';
        $where['table.field'] = 'table.fieldValue';
        $where['nullField'] = null;// no values output
        $where['notNullField'] = 'IS NOT NULL';// no values output
        $where['isNotNullField'] = '\IS NOT NULL';
        $where['nullSafeEqual'] = '<=> NULL';
        $where['notEqual1'] = '!= value6';
        $where['notEqual2'] = '<> value7';
        $where['lessThanEqual'] = '<= value8';
        $where['lessThan'] = '< value9';
        $where['greaterThanEqual'] = '>= value10';
        $where['greaterThan'] = '> value11';
        $generatedResult = $this->Db->buildPlaceholdersAndValues($where);
        unset($where);

        $this->assertArrayHasKey('placeholders', $generatedResult);
        $this->assertArrayHasKey('values', $generatedResult);
        $this->assertCount(12, $generatedResult['placeholders']);
        $this->assertCount(10, $generatedResult['values']);

        $placeholders = $generatedResult['placeholders'];
        $values = $generatedResult['values'];
        unset($generatedResult);

        $this->assertArraySubset([':field' => 'value'], $values);
        $this->assertArraySubset([':table_field' => 'table.fieldValue'], $values);
        $this->assertArraySubset([':isNotNullField' => 'IS NOT NULL'], $values);
        $this->assertTrue(in_array('`field` = :field', $placeholders));
        $this->assertTrue(in_array('`isNotNullField` = :isNotNullField', $placeholders));

        $this->assertTrue(in_array('`nullField` IS NULL', $placeholders));
        $this->assertTrue(in_array('`notNullField` IS NOT NULL', $placeholders));

        $this->assertTrue(in_array('`nullSafeEqual` <=> :nullSafeEqual', $placeholders));
        $this->assertTrue(in_array('`notEqual1` != :notEqual1', $placeholders));
        $this->assertTrue(in_array('`notEqual2` <> :notEqual2', $placeholders));
        $this->assertTrue(in_array('`lessThanEqual` <= :lessThanEqual', $placeholders));
        $this->assertTrue(in_array('`lessThan` < :lessThan', $placeholders));
        $this->assertTrue(in_array('`greaterThanEqual` >= :greaterThanEqual', $placeholders));
        $this->assertTrue(in_array('`greaterThan` > :greaterThan', $placeholders));

        // test positional placeholders. -------------------------------------------------------------
        $where = [];
        $where['field'] = 'value';
        $where['table.field'] = 'table.fieldValue';
        $where['nullField'] = null;// no values output
        $where['notNullField'] = 'IS NOT NULL';// no values output
        $where['isNotNullField'] = '\IS NOT NULL';
        $where['nullSafeEqual'] = '<=> NULL';
        $where['notEqual1'] = '!= value6';
        $where['notEqual2'] = '<> value7';
        $where['lessThanEqual'] = '<= value8';
        $where['lessThan'] = '< value9';
        $where['greaterThanEqual'] = '>= value10';
        $where['greaterThan'] = '> value11';
        $generatedResult = $this->Db->buildPlaceholdersAndValues($where, true, 'positional');
        unset($where);

        $this->assertArrayHasKey('placeholders', $generatedResult);
        $this->assertArrayHasKey('values', $generatedResult);
        $this->assertCount(12, $generatedResult['placeholders']);
        $this->assertCount(10, $generatedResult['values']);

        $placeholders = $generatedResult['placeholders'];
        $values = $generatedResult['values'];
        unset($generatedResult);

        $this->assertArraySubset([0 => 'value'], $values);
        $this->assertArraySubset([1 => 'table.fieldValue'], $values);
        $this->assertArraySubset([2 => 'IS NOT NULL'], $values);
        $this->assertTrue(in_array('`field` = ?', $placeholders));
        $this->assertTrue(in_array('`table`.`field` = ?', $placeholders));
        $this->assertTrue(in_array('`isNotNullField` = ?', $placeholders));

        $this->assertTrue(in_array('`nullField` IS NULL', $placeholders));
        $this->assertTrue(in_array('`notNullField` IS NOT NULL', $placeholders));

        $this->assertTrue(in_array('`nullSafeEqual` <=> ?', $placeholders));
        $this->assertTrue(in_array('`notEqual1` != ?', $placeholders));
        $this->assertTrue(in_array('`notEqual2` <> ?', $placeholders));
        $this->assertTrue(in_array('`lessThanEqual` <= ?', $placeholders));
        $this->assertTrue(in_array('`lessThan` < ?', $placeholders));
        $this->assertTrue(in_array('`greaterThanEqual` >= ?', $placeholders));
        $this->assertTrue(in_array('`greaterThan` > ?', $placeholders));
    }// testBuildPlaceholdersAndValues


    public function testConvertCharsetAndCollation()
    {
        $tableName = $this->Db->tableName('temp_table_for_test_convertcollation');
        $sql = 'CREATE TABLE IF NOT EXISTS `' . $tableName . '` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(100) CHARACTER SET tis620 COLLATE tis620_bin DEFAULT NULL,
                `lastname` varchar(100) DEFAULT NULL,
                PRIMARY KEY (`id`)
            ) DEFAULT COLLATE latin1_general_ci COMMENT=\'for test only\' AUTO_INCREMENT=1;';

        try {
            $this->Db->PDO($this->connectKey)->exec($sql);

            // test make sure that default charset is already there.
            $Sth = $this->Db->PDO($this->connectKey)->prepare('SHOW TABLE STATUS WHERE `Name` = \'' . $tableName . '\'');
            $Sth->execute();
            $result = $Sth->fetchObject();
            unset($Sth);
            $this->assertSame('latin1_general_ci', $result->Collation);

            $Sth = $this->Db->PDO($this->connectKey)->prepare('SHOW FULL COLUMNS FROM `' . $tableName . '` WHERE `Field` = \'name\'');
            $Sth->execute();
            $result = $Sth->fetchObject();
            unset($Sth);
            $this->assertSame('tis620_bin', $result->Collation);

            // start convert
            $convertResult = $this->Db->convertCharsetAndCollation(
                $tableName, 
                $this->connectKey, 
                'latin1', 
                'utf8mb4', 
                'utf8mb4_unicode_ci', 
                [
                    'name' => ['convertFrom' => 'tis620', 'collate' => 'utf8mb4_bin'],
                ]
            );
            $this->assertTrue($convertResult);

            // test make sure that charset and collation was changed.
            $Sth = $this->Db->PDO($this->connectKey)->prepare('SHOW TABLE STATUS WHERE `Name` = \'' . $tableName . '\'');
            $Sth->execute();
            $result = $Sth->fetchObject();
            unset($Sth);
            $this->assertSame('utf8mb4_unicode_ci', $result->Collation);

            $Sth = $this->Db->PDO($this->connectKey)->prepare('SHOW FULL COLUMNS FROM `' . $tableName . '` WHERE `Field` = \'name\'');
            $Sth->execute();
            $result = $Sth->fetchObject();
            unset($Sth);
            $this->assertSame('utf8mb4_bin', $result->Collation);
        } catch (\Exception $ex) {
            $sql = 'DROP TABLE IF EXISTS `' . $tableName . '`;';
            $this->Db->PDO($this->connectKey)->exec($sql);

            throw new \RuntimeException($ex->getMessage() . '(' . $ex->getFile() . ':' . $ex->getLine() . ')' . PHP_EOL . $ex->getTraceAsString(), $ex->getCode());
        }

        $sql = 'DROP TABLE IF EXISTS `' . $tableName . '`;';
        $this->Db->PDO($this->connectKey)->exec($sql);
        unset($sql);
    }// testConvertCharsetAndCollation


    public function testCurrentConnectionKey()
    {
        $this->assertEquals($this->connectKey, $this->Db->currentConnectionKey());
    }// testCurrentConnectionKey


    public function testDelete()
    {
        $sql = 'SELECT * FROM `' . $this->testTableName . '`';
        $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
        $Sth->execute();
        $result = $Sth->fetchAll();

        $this->assertCount(3, $result);

        $dbWhere = [
            'name' => 'Jack',
            'lastname' => 'Nicholson',
        ];
        $deleteResult = $this->Db->delete($this->testTableName, $dbWhere);

        $this->assertTrue($deleteResult);

        $sql = 'SELECT * FROM `' . $this->testTableName . '`';
        $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
        $Sth->execute();
        $result = $Sth->fetchAll();

        $this->assertCount(2, $result);
    }// testDelete


    public function testDisconnect()
    {
        $this->dropTestTableBeforeDisconnect();

        $row = $this->Db->PDO($this->connectKey)->query('SELECT CONNECTION_ID() AS conId')->fetch(\PDO::FETCH_OBJ);
        $beforeConnectionId = $row->conId;

        // disconnect
        $this->Db->disconnect($this->connectKey);
        $this->assertNull($this->Db->PDO($this->connectKey));

        // re-connect
        $this->setup();
        $row = $this->Db->PDO($this->connectKey)->query('SELECT CONNECTION_ID() AS conId')->fetch(\PDO::FETCH_OBJ);
        $afterConnectionId = $row->conId;
        // test that id before and after must NOT matched.
        $this->assertTrue($beforeConnectionId !== $afterConnectionId);
        unset($afterConnectionId, $beforeConnectionId, $row);
    }// testDisconnect


    public function testDisconnectAll()
    {
        $this->dropTestTableBeforeDisconnect();

        $this->Db->disconnectAll();
        $this->assertEmpty($this->Db->PDO($this->connectKey));
        $this->assertNull($this->Db->PDO($this->connectKey));
    }// testDisconnectAll


    public function testInsert()
    {
        $data = [
            'name' => 'Kate',
            'lastname' => 'Winslet',
        ];
        $insertResult = $this->Db->insert($this->testTableName, $data);
        $insertId = $this->Db->PDO($this->connectKey)->lastInsertId();

        $this->assertTrue($insertResult);
        $this->assertNotEmpty($insertId);
        $this->assertGreaterThanOrEqual(1, $this->Db->PDOStatement()->rowCount());

        $sql = 'SELECT * FROM `' . $this->testTableName . '` WHERE `id` = :id';
        $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
        $Sth->bindValue(':id', $insertId);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        $firstRow = $result[0];

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertSame('Winslet', $firstRow->lastname);

        // test insert null
        $data = [
            'name' => 'Will',
            'lastname' => null,
        ];
        $insertResult = $this->Db->insert($this->testTableName, $data);
        $insertId = $this->Db->PDO($this->connectKey)->lastInsertId();

        $this->assertTrue($insertResult);
        $this->assertNotEmpty($insertId);

        $sql = 'SELECT * FROM `' . $this->testTableName . '` WHERE `id` = :id';
        $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
        $Sth->bindValue(':id', $insertId);
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        $firstRow = $result[0];

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertSame('Will', $firstRow->name);
        $this->assertSame(null, $firstRow->lastname);
    }// testInsert


    public function testPDO()
    {
        $this->assertTrue($this->Db->PDO($this->connectKey) instanceof \PDO);
    }// testPDO


    public function testTableName()
    {
        $Db = $this->Db;
        
        $this->assertTrue((strpos($Db->tableName('users', $this->connectKey), 'users') !== false));
        $this->assertTrue((strpos($Db->tableName('Bills', $this->connectKey), 'Bills') !== false));
        $this->assertFalse((strpos($Db->tableName('Bills', $this->connectKey), 'bills') !== false));
    }// testTableName


    public function testUpdate()
    {
        $dataUpdate = [
            'name' => 'Ethan',
            'lastname' => 'Hunt',
        ];
        $dataWhere = [
            'name' => 'Tom',
            'lastname' => 'Cruise',
        ];
        $updateResult = $this->Db->update($this->testTableName, $dataUpdate, $dataWhere);
        $this->assertTrue($updateResult);
        $this->assertGreaterThanOrEqual(1, $this->Db->PDOStatement()->rowCount());

        $sql = 'SELECT * FROM `' . $this->testTableName . '` WHERE name = :name AND lastname = :lastname';
        $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
        $Sth->bindValue(':name', 'Ethan');
        $Sth->bindValue(':lastname', 'Hunt');
        $Sth->execute();
        $result = $Sth->fetchAll();
        $Sth->closeCursor();
        $firstRow = $result[0];

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertSame('Ethan', $firstRow->name);
        $this->assertSame('Hunt', $firstRow->lastname);

        // test update null.
        $dataUpdate = [
            'name' => 'Ethan',
            'lastname' => null,
        ];
        $dataWhere = [
            'name' => 'Ethan',
            'lastname' => 'Hunt',
        ];
        $updateResult = $this->Db->update($this->testTableName, $dataUpdate, $dataWhere);
        $this->assertTrue($updateResult);
        $this->assertGreaterThanOrEqual(1, $this->Db->PDOStatement()->rowCount());

        $sql = 'SELECT * FROM `' . $this->testTableName . '` WHERE name = :name';
        $Sth = $this->Db->PDO($this->connectKey)->prepare($sql);
        $Sth->bindValue(':name', 'Ethan');
        $Sth->execute();
        $result = $Sth->fetchAll();
        $firstRow = $result[0];

        $this->assertGreaterThanOrEqual(1, count($result));
        $this->assertSame('Ethan', $firstRow->name);
        $this->assertSame(null, $firstRow->lastname);
    }// testUpdate


}
