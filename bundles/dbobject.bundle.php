<?php

namespace Bundle;

use Core\Event;

define('TYPE_DB_PRIMARY', 1);
define('TYPE_DB_NUMERIC', 2);
define('TYPE_DB_TEXT', 4);
define('TYPE_DB_FLOAT', 8);

define('TYPE_DB_NON_SERIALIZABLE', 256);
define('TYPE_DB_NULLABLE', 1024);

class DBObject
{
    private static $db = null;
    private static $fieldDefinitions = [];
    private static $tableObjects = [];

    public static function Item($tableName, $fields)
    {
        return Event::raise($tableName.':item', [$fields]);
    }

    public static function InitDefaultConnection()
    {
        if (self::$db == null) {
            self::$db = new Database(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        }

        return self::$db;
    }

    private $fields = [];
    private $fieldDef = null;
    private $tableName = null;
    private $primary = array('col' => null, 'val' => null);

    public static function __init($tableName, $tableFields, $objClass = null)
    {
        self::InitDefaultConnection();
        if ($objClass != null) {
            self::$tableObjects[$tableName] = $objClass;
        }
        self::$db->Query('SELECT 1 FROM `'.$tableName.'` LIMIT 1');
        if (self::$db->GetLastError()) {
            $create = 'CREATE TABLE `'.$tableName.'` (';
            $fields = [];
            $primary = null;
            foreach ($tableFields as $name => $def) {
                $fieldDef = ' `'.$name.'` ';
                switch (true) {
                    case ($def | TYPE_DB_NUMERIC) == $def:
                        $fieldDef .= 'BIGINT';
                        break;
                    case ($def | TYPE_DB_TEXT) == $def:
                        $fieldDef .= 'TEXT';
                        break;
                }
                if (($def | TYPE_DB_NULLABLE) == $def) {
                    $fieldDef .= ' DEFAULT NULL';
                } else {
                    $fieldDef .= ' NOT NULL';
                }
                if (($def | TYPE_DB_PRIMARY) == $def) {
                    $fieldDef .= ' AUTO_INCREMENT';
                    $primary = $name;
                }
                $fields[] = $fieldDef;
            }
            if ($primary) {
                $fields[] = ' PRIMARY KEY (`'.$primary.'`)';
            }
            $create .= implode(',', $fields).');';
            self::$db->Query($create);
            if ($error = self::$db->GetLastError()) {
                echo $error['error'].' / '.$error['query'];
            }
        }
    }

    public function Save()
    {
        $this->insert();

        return $this;
    }

    public function Serialize()
    {
        $data = [];
        foreach ($this->fieldDef as $key => $value) {
            if (($value | TYPE_DB_NON_SERIALIZABLE) != $value) {
                $data[$key] = $this->fields[$key];
            }
        }

        return $data;
    }

    public static function SerializeSet($dataSet, $defaultKey = null)
    {
        $result = [];
        if ($defaultKey != null) {
            if (is_array($dataSet)) {
                foreach ($dataSet as $data) {
                    if ($data instanceof DBObject) {
                        $result[$data->getField($defaultKey)] = $data->Serialize();
                    }
                }
            }
        } else {
            if (is_array($dataSet)) {
                foreach ($dataSet as $data) {
                    if ($data instanceof DBObject) {
                        $result[] = $data->Serialize();
                    }
                }
            }
        }

        return $result;
    }

    protected static function __delete(array $rules, $tableName)
    {
        $table = $tableName;
        $rulesQuery = array();
        $first = true;
        $orders = array();
        foreach ($rules as $key => $value) {
            switch (true) {
                case substr($value, 0, 4) == 'LIKE':
                    $value = self::$db->Filter($value);
                    $rulesQuery[] = "$key LIKE '$value'";
                    break;
                case preg_match('/^(>=|<=|<|>)/', substr($value, 0, 2)) > 0:
                    $rulesQuery[] = "$key $value";
                    break;
                default:
                    $rulesQuery[] = "$key = $value";
                    break;
            }
        }
        $query = "DELETE FROM `$table`";
        if (count($rulesQuery) > 0) {
            foreach ($rulesQuery as $key => $value) {
                if ($first) {
                    $first = false;
                } elseif (!preg_match('/^(AND|OR)/', $value)) {
                    $rulesQuery[$key] = 'AND '.$value;
                }
            }
            $query .= ' WHERE '.implode(' ', $rulesQuery);
        }

        $result = self::$db->Result(
            self::$db->Query($query), RESULT_TYPE_BOTH
        );
    }

    protected static function __selectCount(array $rules, $tableName)
    {
        $table = $tableName;
        $rulesQuery = array();
        $first = true;
        $orders = array();
        foreach ($rules as $key => $value) {
            switch (true) {
                case substr($key, 0, 5) == 'ORDER':
                    $order = substr($key, 5);
                    $orders[] = $order.' '.$value;
                    break;
                case substr($value, 0, 4) == 'LIKE':
                    $value = self::$db->Filter($value);
                    $rulesQuery[] = "$key LIKE '$value'";
                    break;
                case preg_match('/^(>=|<=|<|>)/', substr($value, 0, 2)) > 0:
                    $rulesQuery[] = "$key $value";
                    break;
                default:
                    $rulesQuery[] = "$key = '$value'";
                    break;
            }
        }
        $query = "SELECT COUNT(*) FROM `$table`";
        if (count($rulesQuery) > 0) {
            foreach ($rulesQuery as $key => $value) {
                if ($first) {
                    $first = false;
                } elseif (!preg_match('/^(AND|OR)/', $value)) {
                    $rulesQuery[$key] = 'AND '.$value;
                }
            }
            $query .= ' WHERE '.implode(' ', $rulesQuery);
        }
        if (count($orders) > 0) {
            $query .= ' ORDER BY '.implode(',', $orders);
        }

        self::InitDefaultConnection();
        $result = self::$db->Result(
            self::$db->Query($query), RESULT_TYPE_NUMERIC
        );

        if (@$count = $result[0][0]) {
            return $count;
        } else {
            return null;
        }
    }

    protected static function __select(array $rules, $tableName, $objType, $count = null, $start = 0)
    {
        $table = $tableName;
        $rulesQuery = array();
        $first = true;
        $orders = array();
        foreach ($rules as $key => $value) {
            switch (true) {
                case substr($key, 0, 5) == 'ORDER':
                    $order = substr($key, 5);
                    $orders[] = $order.' '.$value;
                    break;
                case substr($value, 0, 4) == 'LIKE':
                    $value = self::$db->Filter($value);
                    $rulesQuery[] = "$key LIKE '$value'";
                    break;
                case preg_match('/^(>=|<=|<|>|IN)/', substr($value, 0, 2)) > 0:
                    $rulesQuery[] = "$key $value";
                    break;
                default:
                    $rulesQuery[] = "$key = '$value'";
                    break;
            }
        }
        $query = "SELECT * FROM `$table`";
        if (count($rulesQuery) > 0) {
            foreach ($rulesQuery as $key => $value) {
                if ($first) {
                    $first = false;
                } elseif (!preg_match('/^(AND|OR)/', $value)) {
                    $rulesQuery[$key] = 'AND '.$value;
                }
            }
            $query .= ' WHERE '.implode(' ', $rulesQuery);
        }
        if (count($orders) > 0) {
            $query .= ' ORDER BY '.implode(',', $orders);
        }
        if ((int) $count) {
            $query .= " LIMIT $start, $count";
        }

        //echo "$query\r\n";

        self::InitDefaultConnection();
        $result = self::$db->Result(
            self::$db->Query($query), RESULT_TYPE_BOTH
        );
        if ($result) {
            if ($count == 1) {
                return new $objType($result[0]);
            }
        } else {
            return null;
        }
        $data = array();
        foreach ($result as $value) {
            $data[] = new $objType($value);
        }

        return $data;
    }

    protected function __construct(array &$fieldDefintions, $tableName, $obj = null)
    {
        $this->tableName = $tableName;
        $this->fieldDef = $fieldDefintions;
        foreach ($fieldDefintions as $key => $value) {
            if (($value | TYPE_DB_PRIMARY) == $value) {
                $this->primary['col'] = $key;
                if ($obj == null) {
                    $this->primary['val'] = null;
                } else {
                    $this->primary['val'] = $obj[$key];
                }
                $this->fields[$key] = $this->primary['val'];
            } else {
                switch (true) {
                    case ($value | TYPE_DB_NUMERIC) == $value:
                        $this->fields[$key] = (int) $obj[$key];
                        break;
                    case ($value | TYPE_DB_TEXT) == $value:
                        $this->fields[$key] = $obj[$key];
                        break;
                }
            }
        }
        if (@self::$fieldDefinitions[$tableName] == null) {
            self::$fieldDefinitions[$tableName] = $fieldDefintions;
        }
    }

    private function insert()
    {
        $fields = array();
        $values = array();
        $table = $this->tableName;
        foreach (self::$fieldDefinitions[$table] as $key => $value) {
            switch (true) {
                case ($value | TYPE_DB_PRIMARY) == $value:
                    if ($this->fields[$key] != null) {
                        continue;
                    } else {
                        break;
                    }
                    // no break
                case ($value | TYPE_DB_NUMERIC) == $value:
                    $fields[] = "`$key`";
                    $values[] = (int) $this->fields[$key];
                    break;
                case ($value | TYPE_DB_TEXT) == $value:
                    $fields[] = "`$key`";
                    $values[] = "'".self::$db->Filter($this->fields[$key])."'";
                    break;
            }
        }
        $key = $this->primary['col'];
        if ($this->fields[$key] != null) {
            array_unshift($fields, '`'.$key.'`');
            $fields = implode(',', $fields);
            array_unshift($values, "'".self::$db->Filter($this->fields[$key])."'");
            $values = implode(',', $values);
            $query = "REPLACE INTO $table ($fields) VALUES ($values)";
        } else {
            $fields = implode(',', $fields);
            $values = implode(',', $values);
            $query = "INSERT INTO $table ($fields) VALUES ($values)";
        }
        self::$db->Query($query);
        @$result = self::$db->Result(
            self::$db->Query('SELECT LAST_INSERT_ID()'),
            RESULT_TYPE_NUMERIC
        )[0][0];
        if ($result) {
            $this->fields[$this->primary['col']] = $result;
            $this->primary['val'] = $result;
        } elseif ($this->fields[$key] == null) {
            $error = self::$db->GetLastError();
            $error = 'An error occured in a query '.$error['query'].': '.$error['error'];
            throw new \Exception($error);
        }
    }

    protected function setField($field, $value)
    {
        $this->fields[$field] = $value;
    }

    protected function getField($field)
    {
        return $this->fields[$field];
    }
}

interface DBTableObject extends DBSelectable, DBDeletable
{
    public function __construct($obj = null);

    public static function Init();

    public static function Count($rules = []);
}

interface DBSelectable
{
    public static function Select($rules, $count = null, $start = 0);
}

interface DBDeletable
{
    public static function Delete($rules);
}
