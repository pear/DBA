<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2002 Brent Cook                                        |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
//
// $Id$
//
require_once 'PEAR.php';
require_once 'DB/DBA/DBA.php';

/**
 * Reserved key used to store the schema record
 */
define('DBA_SCHEMA_KEY', '__schema__');

/**
 * Reserved character used to separate fields in a row
 */
define('DBA_FIELD_SEPARATOR', '|');
define('DBA_OPTION_SEPARATOR', ';');
define('DBA_DOMAIN_SEPARATOR', ',');

/**
 * Available types
 */
define('DBA_INTEGER', 10);
define('DBA_NUMERIC', 11);
define('DBA_FLOAT', 12);
define('DBA_CHAR', 13);
define('DBA_VARCHAR', 14);
define('DBA_TEXT', 15);
define('DBA_BOOLEAN', 16);
define('DBA_ENUM', 17);
define('DBA_SET', 18);
define('DBA_TIMESTAMP', 19);

/**
 * Key tokens
 */
define('DBA_TYPE', 0);
define('DBA_DOMAIN', 1);
define('DBA_SIZE', 2);
define('DBA_PRIMARYKEY', 3);
define('DBA_AUTOINCREMENT', 4);
define('DBA_DEFAULT', 5);
define('DBA_NOTNULL', 6);

/**
 * DBA Table
 * This class provides a simple, table storage object.
 * It uses a DBA storage object as returned by DBA::create().
 * This class is used extensively in DBA_Relational as part of
 * a complete, relational database system.
 *
 * Enough functionality exists within this class to create and delete tables,
 * insert, retrieve, remove and validate data based on a table schema, and
 * perform basic database operations such as selects, projects and sorts.
 *
 * @author  Brent Cook <busterb@mail.utexas.edu>
 * @package DBA
 * @access  public
 * @version 0.15
 */
class DBA_Table extends PEAR
{
    /**
     * DBA object handle
     * @var     object
     * @access  private
     */
    var $_dba;

    /**
     * Describes the types of fields in a table
     * @var     array
     * @access  private
     */
    var $_schema;

    /**
     * Default date format
     * @var     string
     * @access  private
     */
    var $_dateFormat = 'D M j G:i:s T Y';

    /**
     * When no primary key is specified, this is used to generate a unique
     * key; like a row ID
     * @var    int
     * @access private
     */
    var $_maxKey=null;

    /**
     * Field name to use as a primary key. Null indicates that there is no
     * primary key
     * @var    int
     * @access private
     */
    var $_primaryKey=null;

    /**
     * Constructor
     *
     * @param string $driver dba driver to use for storage
     */
    function DBA_Table($driver = 'simple')
    {
        // call the base constructor
        $this->PEAR();
        // initialize the internal dba object
        $this->_dba =& DBA::create($driver);
    }

    /**
     * PEAR emulated destructor calls close on PHP shutdown
     * @access  private
     */
    function _DBA_Table()
    {
        $this->close();
    }

    function raiseError($message)
    {
        return PEAR::raiseError('DBA_Table: '.$message);
    }

    /**
     * Opens a table
     *
     * @access  public
     * @param   string $tableName name of the table to open
     * @param   char   $mode      mode to open the table; one of r,w,c,n
     * @return  object PEAR_Error on failure
     */
    function open($tableName, $mode = 'r')
    {
        if (($mode != 'w') && ($mode != 'r')) {
            return $this->raiseError("table open mode '$mode' is invalid");
        }

        $result = $this->_dba->open($tableName, $mode);
        if (PEAR::isError($result)) {
            return $result;
        }

        // fetch the field descriptor at the key, DBA_SCHEMA_KEY
        if (!PEAR::isError($schema = $this->_dba->fetch(DBA_SCHEMA_KEY))) {

            // unpack the field schema into an array
            $this->_schema = $this->_unpackSchema($schema);

            if (PEAR::isError($this->_schema)) {
                $this->close();
            }

        } else {
            return $this->raiseError('Table is missing field descriptor.'.
                                     'at key, '. DBA_SCHEMA_KEY);
        }
        return $this->_schema;
    }

    /**
     * Closes a table
     *
     * @access  public
     * @return  object PEAR_Error on failure
     */
    function close()
    {
        if ($this->_dba->isWritable()) {
            // pack up the field structure and store it back in the table
            $schema = $this->_packSchema($this->_schema);
            $this->_dba->replace(DBA_SCHEMA_KEY, $schema);
        }
        $this->_maxKey = null;
        return $this->_dba->close();
    }

    /**
     * Creates a new table. Note, this closes any open table.
     *
     * @param   string $tableName   name of the table to create
     * @param   array  $schema field schema for the table
     * @return  object PEAR_Error on failure
     */
    function create($tableName, $schema)
    {
        // pack the schema
        $packedSchema = $this->_packSchema($schema);

        if (PEAR::isError($packedSchema)) {
            return $packedSchema;
        }

        $r = $this->_dba->open($tableName, 'n');
        if (PEAR::isError($r)) {
            return $r;
        }

        $r = $this->_dba->insert(DBA_SCHEMA_KEY, $packedSchema);
        if (PEAR::isError($r)) {
            return $r;
        }

        $r = $this->_dba->close();
        if (PEAR::isError($r)) {
            return $r;
        }
    }

    /**
     * Returns the stored schema for the table
     *
     * @return  mixed an array of the form 'fieldname'=>array(fieldmeta) on
     *          success, PEAR_Error on failure
     */
    function getSchema() {
        if ($this->isOpen()) {
            return $this->_schema;
        } else {
            return $this->raiseError('table not open, no schema available');
        }
    }  

    /**
     * Check whether table exists
     *
     * @access  public
     * @param   string $key
     * @return  boolean true if the table exists, false otherwise
     */
    function tableExists($tableName)
    {
        return $this->_dba->db_exists($tableName);
    }

    /**
     * Returns the current open status for the database
     *
     * @returns true if the table exists, false otherwise
     * @return  boolean true if the table is open, false if it is closed
     */
    function isOpen()
    {
        return $this->_dba->isOpen();
    }

    /**
     * Returns the current read status for the database
     *
     * @return  boolean true if the table is readable, false otherwise
     */
    function isReadable()
    {
        return $this->_dba->isReadable();
    }

    /**
     * Returns the current write status for the database
     *
     * @return  boolean true if the table is writable, false otherwise
     */
    function isWritable()
    {
        return $this->_dba->isWritable();
    }

    /**
     * Returns whether a field exists in the current table's schema
     *
     * @return  boolean true if the field exists, false otherwise
     */
    function fieldExists($fieldName)
    {
        return($this->isOpen() && isset($this->_schema[$fieldName]));
    }

    /**
     * Aquire an exclusive lock on the table
     *
     * @return  object PEAR_Error on failure
     */
    function lockEx()
    {
        return $this->_dba->reopen('w');
    }

    /**
     * Aquire a shared lock on the table
     *
     * @return  object PEAR_Error on failure
     */
    function lockSh($table_name)
    {
        return $this->_dba->reopen('r');
    }

    /**
     * DBA_Table keeps an internal row index (key)
     * This function returns the highest row index
     *
     * @access  private
     * @return  mixed an integer or false if there are no keys
     */
    function _findMaxKey()
    {
        $maxKey = 0;
        $key = $this->_dba->firstkey();
        while ($key !== false) {
            if (is_numeric ($key) && ($key > $maxKey)) {
                $maxKey = $key;
            }
            $key = $this->_dba->nextkey($key);
        }
        return $maxKey;
    }

    /**
     * Returns a unique key to be used as a row index
     *
     * @access  private
     * @return  integer a new key
     */
    function _getUniqueKey()
    {
        // find the maxKey if necessary
        if (is_null($this->_maxKey)) {
            $this->_maxKey = $this->_findMaxKey();
        }

        // check if this is the first key
        if ($this->_maxKey === false) {
            $this->_maxKey = 0;
        } else {
            $this->_maxKey++;
        }

        return $this->_maxKey;
    }

    /**
     * Returns a string for a raw field
     *
     * @access  private
     * @param   array   $field field to pack
     * @param   string  $value value of this field to pack
     * @return  string  packed version of value, as per $field spec
     */
    function _packField($field, $value)
    {
        $c_value = null;
        switch ($this->_schema[$field][DBA_TYPE]) {
            case DBA_SET:
                if (is_string($value)) {
                    $value = explode(DBA_DOMAIN_SEPARATOR, $value);
                }

                if (is_array($value)) {
                    $c_value = array();
                    foreach ($value as $element) {
                        if (is_string($element)) {
                            $c_element = array_search($element,
                                         $this->_schema[$field][DBA_DOMAIN]);
                            if (!is_null($c_element)) {
                                $c_value[] = $c_element;
                            }
                        }
                    }
                    $c_value = implode(DBA_DOMAIN_SEPARATOR,$c_value);
                }
                break;
            case DBA_ENUM:
                if (is_string ($value)) {
                    $c_value = array_search($value,
                                      $this->_schema[$field][DBA_DOMAIN]);
                    if (!is_null($c_value)) {
                        $c_value = strval($c_value);
                    }
                }
                break;
            case DBA_TIMESTAMP:
                if (is_numeric($value)) {
                    $c_value = strval($value);
                } else {
                    if (is_string($value)) {
                        $c_value = strtotime ($value);
                        if ($c_value != -1) {
                            $c_value = strval ($c_value);
                        } else {
                            $c_value = 0;
                        }
                    }
                }
                break;
            case DBA_BOOLEAN:
                if (is_bool ($value)) {
                    $c_value = strval ($value);
                } else {
                    if (is_string ($value)) {
                        // convert a 'boolean' string into a string 1 or 0
                        $c_value = in_array(strtolower($value),
                                     array('t','true','y','yes','1')) ? '1':'0';
                    }
                }
                break;
            case DBA_TEXT:
                if (is_scalar($value)) {
                    $c_value = str_replace(DBA_FIELD_SEPARATOR,'', $value);
                }
                break;
            case DBA_CHAR:
                if (is_scalar($value)) {
                    $c_value = str_replace(DBA_FIELD_SEPARATOR, '', substr(
                               str_pad($value, $this->_schema[$field][DBA_SIZE])
                                       ,0, $this->_schema[$field][DBA_SIZE]));
                }
                break;
            case DBA_VARCHAR:
                if (is_scalar($value)) {
                    $c_value = str_replace(DBA_FIELD_SEPARATOR,'', str_pad(
                               $value, $this->_schema[$field][DBA_SIZE]));
                }
                break;
            case DBA_INTEGER: case DBA_FLOAT: case DBA_NUMERIC:
                if (is_numeric($value)) {
                    $c_value = strval($value);
                }
                break;
        }
        return $c_value;
    }

    /**
     * Converts a field from its packed representation to its original value
     *
     * @access  private
     * @param   string $field field to convert
     * @return  mixed  string $field packed value
     */
    function _unpackField($field, $value)
    {
        switch ($this->_schema[$field][DBA_TYPE]) {
            case DBA_SET:
                $c_value = array();
                $value = explode (DBA_DOMAIN_SEPARATOR,$value);
                if (is_array($value)) {
                    foreach ($value as $element) {
                      $c_value[] = $this->_schema[$field][DBA_DOMAIN][$element];
                    }
                }
                return $c_value;
            case DBA_ENUM:
                return $this->_schema[$field][DBA_DOMAIN][$value];
            case DBA_BOOLEAN:
                return($value == '1');
            case DBA_TIMESTAMP:
            case DBA_INTEGER:
                return intval($value);
            case DBA_FLOAT:
            case DBA_NUMERIC:
                return floatval($value);
            case DBA_CHAR:
            case DBA_VARCHAR:
                return rtrim($value);
            case DBA_TEXT:
                return $value;
        }
    }

    /**
     * Returns a string for a field structure
     *
     * This function uses the following is the grammar to pack elements:
     * enum      => name;type=enum;domain=[element1,...]
     * set       => name;type=set;domain=[element1,...]
     * timestamp => name;type=timestamp;format=<string>;init=<num>
     * boolean   => name;type=bool;default=[true, false]
     * text      => name;type=text;default=<string>
     * varchar   => name;varchar;size=<num>;default=<string>
     * numeric   => name;[autoincrement];size=<num>;default=<string>
     *
     * @access  private
     * @param   array  $schema schema to pack
     * @return  string the packed schema
     */
    function _packSchema($schema)
    {
        $primaryKey = false;
        foreach ($schema as $fieldName => $fieldMeta) {
            $buffer = $fieldName;

            foreach ($fieldMeta as $attribute => $value) {
                $buffer .= DBA_OPTION_SEPARATOR.$attribute.'=';

                switch ($attribute) {
                    case DBA_PRIMARYKEY:
                        if ($primarykey) {
                            return $this->raiseError('cannot have two '.
                                                     'primary keys in schema');
                        } else {
                            $buffer .= $value;
                            $primarykey = true;
                        }
                        break;
                    default:
                        if (is_array($value)) {
                            $buffer .= implode(DBA_DOMAIN_SEPARATOR,$value);
                        } else {
                            $buffer .= $value;
                        }
                }
            }
            $fields[] = $buffer;
        }
        return implode(DBA_FIELD_SEPARATOR, $fields);
    }

    /**
     * Unpacks a raw string as created by _packSchema into an array
     * structure for use as $this->_schema
     *
     * @access  private
     * @param   string  $rawFieldString data to be unpacked into the schema
     * @return  array
     */
    function _unpackSchema($rawFieldString)
    {
        $rawFields = explode(DBA_FIELD_SEPARATOR, $rawFieldString);
        $this->_primaryKey = false;
        foreach ($rawFields as $rawField) {
            $rawMeta = explode(DBA_OPTION_SEPARATOR, $rawField);
            $name = array_shift($rawMeta);
            foreach ($rawMeta as $rawAttribute) {
                list($attribute,$rawValue) = explode('=',$rawAttribute);
                switch ($attribute) {
                    case DBA_DOMAIN:
                        $value = explode(DBA_DOMAIN_SEPARATOR,$rawValue);
                        break;
                    case DBA_PRIMARYKEY:
                        $this->_primaryKey = true;
                    default:
                        $value = $rawValue;
                }
                $fields[$name][$attribute] = $value;
            }
        }
        return $fields;
    }

    /**
     * Packs a fields from a raw row into an internal representation suitable
     * for storing in the table. Think of this as a cross-language version of
     * serialize.
     *
     * @access  private
     * @param   array   $data row data to pack, key=>field pairs
     * @return  string
     */
    function _packRow($data, &$primaryKey)
    {
        $buffer = array();
        $i = 0;
        foreach ($this->_schema as $fieldName => $fieldMeta) {

            if (isset($data[$fieldName])) {

                // data ordering is explicit, e.g. field=>data
                $c_value = $this->_packField($fieldName, $data[$fieldName]);

            } elseif (isset($data[$i])) {

                // data ordering is implicit, e.g. $i=>data
                $c_value = $this->_packField($fieldName, $data[$i]);

            } elseif (isset($fieldMeta[DBA_DEFAULT])) {

                if ($fieldMeta[DBA_AUTOINCREMENT]) {
                    // use the autoincrement value
                    $c_value = $this->_packField($fieldName,
                                    $this->_schema[$fieldName][DBA_DEFAULT]++);
                } else {
                    // use the default value
                    $c_value = $this->_packField($fieldName,
                                    $this->_schema[$fieldName][DBA_DEFAULT]);
                }

            } elseif ($fieldMeta[DBA_NOTNULL]) {

                return $this->raiseError("$fieldName cannot be null");

            } else {

                // when all else fails
                $c_value = null;
            }

            // if this field is the primary key, set $primaryKey
            if (isset($fieldMeta[DBA_PRIMARYKEY])) {
                $primaryKey = $c_value;
            }

            $buffer[] = $c_value;
            ++$i;
        }
        return implode(DBA_FIELD_SEPARATOR, $buffer);
    }

    /**
     * Unpacks a string into an array containing the data from the original
     * packed row. Think of this as a cross-language version of deserialize.
     *
     * @access  private
     * @param   array packedData row data to unpack
     * @return  array field=>value pairs
     */
    function _unpackRow($packedData)
    {
        $data = explode(DBA_FIELD_SEPARATOR, $packedData);
        $i = 0;
        foreach ($this->_schema as $fieldName => $fieldMeta) {
            $buffer[$fieldName] = $this->_unpackField($fieldName, $data[$i]);
            $i++;
        }
        return $buffer;
    }

    /**
     * Inserts a new row in a table
     * 
     * @access  public
     * @param   array $data assoc array or ordered list of data to insert
     * @return  mixed PEAR_Error on failure, the row index on success
     */
    function insert($data)
    {
        if ($this->isWritable()) {
            $primaryKey = null;

            $packedRow = $this->_packRow($data, $primaryKey);
            if (PEAR::isError($packedRow)) {
                return $packedRow;
            }

            if (!isset($this->_primaryKey)) {
                if (!is_null($primaryKey)) {
                    $key = $primaryKey;
                } else {
                    return $this->raiseError('no primary key specified');
                }
            } else {
                $key = $this->_getUniqueKey();
            }

            $result = $this->_dba->insert($key, $packedRow);
            if (PEAR::isError($result)) {
                return $result;
            } else {
                return $key;
            }

        } else {
            return $this->raiseError('table not writable');
        }
    }

    /**
     * Replaces rows that match $rawQuery with $
     *
     * @access  public
     * @param   string $rawQuery query expression for performing the remove
     * @param   array  $rows rows to select on
     * @return  object PEAR_Error on failure
     */
    function replace($rawQuery, $data, $rows=null)
    {
        $rows =& $this->select($rawQuery, $rows);
        if (PEAR::isError($rows)) {
            return $rows;
        }

        $packedRow =& $this->_packRow($data);
        if (PEAR::isError($packedRow)) {
            return $packedRow;
        }
        foreach (array_keys($rows) as $rowKey) {
            $result = $this->_dba->replace($rowKey, $packedRow);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
    }

    /**
     * Replaces an existing row in a table, inserts if the row does not exist
     *
     * @access  public
     * @param   string $key row id to replace
     * @param   array  $data assoc array or ordered list of data to insert
     * @return  mixed  PEAR_Error on failure, the row index on success
     */
    function replaceKey($key, $data)
    {
        if ($this->isOpen()) {
            $packedRow =& $this->_packRow($data);
            if (PEAR::isError($packedRow)) {
                return $packedRow;
            }
            return $this->_dba->replace($key, $packedRow);
        } else {
            return $this->raiseError('table not open');
        }
    }

    /**
     * Removes existing rows from table that match $rawQuery
     *
     * @access  public
     * @param   string $rawQuery query expression for performing the remove
     * @param   array  $rows rows to select on
     * @return  object PEAR_Error on failure
     */
    function remove($rawQuery, $rows=null)
    {
        $rows =& $this->select($rawQuery, $rows);
        if (PEAR::isError($rows)) {
            return $rows;
        }
        foreach (array_keys($rows) as $rowKey) {
            $result = $this->_dba->remove($rowKey);
            if (PEAR::isError($result)) {
                return $result;
            }
        }
    }

    /**
     * Removes an existing row from a table, referenced by the row key
     *
     * @access  public
     * @param   string $key row id to remove
     * @return  object PEAR_Error on failure
     */
    function removeKey($key)
    {
        return $this->_dba->remove($key);
    }

    /**
     * Fetches an existing row from a table
     *
     * @access  public
     * @param   string $key row id to fetch
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function fetch($key)
    {
        $result = $this->_dba->fetch($key);
        if (!PEAR::isError($result)) {
            return $this->_unpackRow($result);
        } else {
            return $result;
        }
    }

    /**
     * Converts a field from its native value to a value, that is
     * sets are converted to strings, bools are converted to 'true' and 'false'
     * timestamps are converted to a readable date. No more operations
     * should be performed on a field after this though.
     *
     * @access  private
     * @param   mixed   $field
     * @param   string  $value
     * @return  string  the string representation of $field
     */
    function _finalizeField($field, $value)
    {
        switch ($this->_schema[$field][DBA_TYPE]) {
            case DBA_SET:
                $buffer = '';
                foreach ($value as $element) {
                    $buffer .= "$element, ";
                }
                return substr($buffer,0 ,-2);
            case DBA_BOOLEAN:
                return $value ? 'true' : 'false';
            case DBA_TIMESTAMP:
                return date($this->_dateFormat, $value);
            default:
                return $value;
        }
    }

    /**
     * Converts the results from any of the row operations to a 'finalized'
     * display-ready form. That means that timestamps, sets and enums are
     * converted into strings. This obviously has some consequences if you plan
     * on chaining the results into another row operation, so don't call this
     * unless it is the final operation.
     *
     * @access  public
     * @param   array  $rows rows to finalize, if none are specified, returns
     *                      the whole table
     * @return  mixed PEAR_Error on failure, the row array on success
     */
    function finalize($rows=null)
    {
        if (is_null($rows)) {
            if ($this->_dba->isOpen()) {
                $rows = $this->getRows();
            } else {
                return $this->raiseError('table not open / no rows specified');
            }
        }

        foreach ($rows as $key=>$row) {
            foreach ($row as $field=>$data) {
                $row[$field] = $this->_finalizeField($field, $row[$field]);
            }
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Returns the specified rows. A multiple-value version of getRow
     *
     * @access  public
     * @param   array  $rowKeys keys of rows to get, if none are specified,
     *                        returns the whole table
     * @return  mixed PEAR_Error on failure, the row array on success
     */
    function getRows($rowKeys=null)
    {
        $rows = array();
        if ($this->_dba->isOpen()) {
            $key = $this->_dba->firstkey();
            while ($key) {
                if ($key != DBA_SCHEMA_KEY) {
                    if (is_null($rowKeys)) {
                        $rows[$key] = $this->_unpackRow(
                                                   $this->_dba->fetch($key));
                    } else {
                        if (in_array($key, $rowKeys)) {
                            $rows[$key] = $this->_unpackRow(
                                                   $this->_dba->fetch($key));
                        }
                    }
                }
                $key = $this->_dba->nextkey($key);
            }
        } else {
            return $this->raiseError('table not open');
        }
        return $rows;
    }

    /**
     * Returns an array of the defined field names in the table
     *
     * @access  public
     * @return  array the field names in an array
     */
    function getFieldNames()
    {
        return array_keys($this->_schema);
    }

    /**
     * Adds spaces around special symbols so that explode will separate them
     * properly from other tokens. Replace spaces within strings with pipe
     * characters so that explode will not separate string tokens.
     *
     * @access  private
     * @param   string  $string
     * @return  string  the cooked query
     */
    function _cookQuery($query)
    {
        foreach (array(',','(',')','==','!=','>','<','<=','>=') as $symbol) {
            $query = str_replace($symbol, " $symbol ", $query);
        }

        $inString = false;
        $cookedQuery = '';
        for ($i=0; $i < strlen($query); ++$i) {
            $chr = $query[$i];
            if ($chr =='\'') {
                $inString = !$inString;
                $cookedQuery .= $chr;
            } elseif ($inString && ($chr == ' ')) {
                $cookedQuery .= DBA_FIELD_SEPARATOR;
            } else {
                $cookedQuery .= $chr;
            }
        }
        return $cookedQuery;
    }

    /**
     * Converts a query expression into PHP code for executing a select.
     *
     * @access  private
     * @param   string  $rawQuery the incoming query
     * @param   array   $fieldTokens list of tokens that should be treated as
     *                               field names
     * @return  string  PHP code for performing a select
     */
    function _parsePHPQuery($rawQuery, $fieldTokens)
    {
        // add spaces around symbols for explode to work properly
        $cookedQuery = $this->_cookQuery($rawQuery);

        // begin building the php query for a row
        $phpQuery = '';

        // scan the tokens in the raw query to build a new query
        // if the token is a field name, use it as a key in $row[]
        $tokens = explode(' ', $cookedQuery);
        foreach ($tokens as $token) {
            // is this token a field name?
            if (in_array($token, $fieldTokens)) {
                $phpQuery .= "\$row['$token']";
            } elseif ($token != '||') {
                // restore spaces in a string token
                $phpQuery .= str_replace(DBA_FIELD_SEPARATOR, ' ', $token);
            } else {
                $phpQuery .= $token;
            }
        }
        return $phpQuery;
    }

    /**
     * Performs a select on a table. This means that a subset of rows in a
     * table are filtered and returned based on the query. Accepts any valid
     * expression of the form '(field == field) || (field > 3)', etc. Using the
     * expression '*' returns the entire table
     * SQL analog: 'select * from rows where rawQuery'
     *
     * @access  public
     * @param   string $rawQuery query expression for performing the select
     * @param   array  $rows rows to select on
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function &select($rawQuery, $rows=null)
    {
        if ($this->_dba->isOpen()) {

            // get a list of valid field names
            $fieldTokens = array_keys($this->_schema);

            // if we haven't passed any rows to select from, use the whole table
            if ($rows==null)
                $rows = $this->getRows();

            // handle the special case of requesting all rows
            if ($rawQuery == '*')
                return $rows;

            // convert the query into a php statement
            $PHPSelect = 'foreach ($rows as $key=>$row) if ('.
                         $this->_parsePHPQuery($rawQuery, $fieldTokens).
                        ') $results[$key] = $row;';

            // perform the select
            $results = array();
            eval ($PHPSelect);

            return $results;
        } else {
            return $this->raiseError('table not open');
        }
    }

    /**
     * Comparison function for sorting ascending order
     *
     * @access  private
     * @return  int
     */
    function _sortCmpA($a, $b)
    {
        foreach ($this->_sortFields as $field) {
            if ($a[$field] < $b[$field]) return -1;
            if ($a[$field] > $b[$field]) return 1;
        }
        return 0;
    }

    /**
     * Comparison function for sorting descending order
     *
     * @access  private
     * @return  int
     */
    function _sortCmpD($a, $b)
    {
        foreach ($this->_sortFields as $field) {
            if ($a[$field] < $b[$field]) return 1;
            if ($a[$field] > $b[$field]) return -1;
        }
        return 0;
    }

    /**
     * explodes a string of field names into an array
     *
     * @access  private
     * @param   string  $fieldString field names to explode
     * @return  array
     */
    function _parseFieldString($fieldString, $possibleFields)
    {
        $fields = array();
        $tokens = preg_split('/[ \,]/', $fieldString);
        foreach ($tokens as $token) {
            if (isset($possibleFields[$token])) {
                $fields[] = $token;
            }
        }
        return $fields;
    }

    /**
     * Sorts rows by field in either ascending or descending order
     * SQL analog: 'select * from rows, order by fields'
     *
     * @access  public
     * @param   mixed  $fields a string with the field name to sort by or an
     *                         array of fields to sort by in order of preference
     * @param   string $order 'a' for ascending, 'd' for descending
     * @param   array  $rows rows to sort
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function sort($fields, $order, $rows)
    {
        if (is_array($rows)) {
            if (is_string($fields)) {
                // parse the sort string to produce an array of sort fields
                // we pass the sortfields as a member variable because
                // uasort does not pass extra parameters to the comparison
                // function
                $this->_sortFields = $this->_parseFieldString($fields,
                                       reset($rows));
            } else {
                if (is_array($fields)) {
                    // we already have an array of sort fields
                    $this->_sortFields = $fields;
                }
            }

            if ($order=='a') {
                uasort($rows, array($this, '_sortCmpA'));
            } elseif ($order=='d') {
                uasort($rows, array($this, '_sortCmpD'));
            } else {
                return $this->raiseError("$order is not a valid sort order");
            }

            return $rows;
        } else {
            return $this->raiseError('no rows to sort specified');
        }
    }

    /**
     * Projects rows by field. This means that a subset of the possible fields i
     * are in the resulting rows. The SQL analog is 'select fields from table'
     *
     * @access  public
     * @param   array  $fields fields to project
     * @param   array  $rows rows to project
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function project($fields, $rows)
    {
        if (is_array($rows)) {
            $projectFields = array();
            if (is_string($fields)) {
                $projectFields = $this->_parseFieldString($fields,
                                       reset($rows));
            } else {
                if (is_array($fields)) {
                    // we already have an array of fields
                    $projectFields = $fields;
                }
            }

            foreach ($rows as $key=>$row) {
                foreach ($projectFields as $field) {
                    $projectedRows[$key][$field] = $row[$field];
                }
            }
            return $projectedRows;
        } else {
            return $this->raiseError('no rows to sort specified');
        }
    }

    /**
     * Compares two rows
     *
     * @access  public
     * @param   array  $a row a
     * @param   array  $b row b
     * @return  bool   true if they are the same, false if they are not
     */
    function cmpRows($a, $b)
    {
        $equal = true;
        foreach ($a as $field=>$value) {
            if ($value != $b[$field]) {
                $equal = false;
            }
        }
        return $equal;
    }

    /**
     * Returns the unique rows from a set of rows
     * 
     * @access  public
     * @param   array  $rows rows to process
     * @return  mixed  PEAR_Error on failure, the row array on success
     */
    function unique($rows)
    {
        if (is_array($rows)) {
            $results = array();
            foreach ($rows as $key=>$row) {
                if (!isset($current) || ($current != $row)) {
                    $results[$key] = $row;
                    $current=$row;
                }
            }
            return $results;
        } else {
            return $this->raiseError('no rows to sort specified');
        }
    }
}
