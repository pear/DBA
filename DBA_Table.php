<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP Version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2002 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Brent Cook <busterb@mail.utexas.edu>                        |
// +----------------------------------------------------------------------+
//
// $Id$
//
require_once 'PEAR.php';
require_once 'DB_DBA/DBA.php';

define ('DBA_SCHEMA_KEY', '__schema__');

/**
 * DBA Table
 * This class provides a simple, single-table database system.
 * It uses a DBA class as the storage driver.
 *
 * @author Brent Cook <busterb@mail.utexas.edu>
 * @version 0.0.10
 */
class DBA_Table extends PEAR
{
    /**
     * DBA object handle
     * @access private
     */
    var $_dba;

    /**
     * Describes the types of fields in a table
     * @access private
     */
    var $_schema;

    /**
     * Default date format
     * @access private
     */
    var $_dateFormat = 'D M j G:i:s T Y';

    /**
     * Constructor
     *
     * @param object $dba dba object to use for storage, you need this sometime
     */
    function DBA_Table($driver = 'simple')
    {
        // initialize the internal dba object
        $this->_dba = DBA::create($driver);
    }

    /**
     * PEAR emulated destructor calls close on PHP shutdown
     * @access private
     */
    function _DBA_Table()
    {
        $this->close();
    }

    /**
     * Opens a table
     *
     * @access public
     * @param string $tableName name of the table to open
     * @param char   $mode      mode to open the table; one of r,w,c,n
     * @returns object PEAR_Error on failure
     */
    function open($tableName, $mode = 'r')
    {
        $result = $this->_dba->open($tableName, $mode);
        if (PEAR::isError($result)) {
            return $result;
        }

        // fetch the field descriptor at the key, DBA_SCHEMA_KEY
        if (!PEAR::isError($schema = $this->_dba->fetch(DBA_SCHEMA_KEY))) {

            // unpack the field schema into an array
            $this->_schema = $this->_unpackSchema($schema);

        } else {
            $this->raiseError('DBA: Table is missing field descriptor at key, '.
                               DBA_SCHEMA_KEY);
        }
    }

    /**
     * Closes a table
     *
     * @access public
     * @returns object PEAR_Error on failure
     */
    function close()
    {
        if ($this->_dba->isWritable()) {
            // pack up the field structure and store it back in the table
            $schema = $this->_packSchema($this->_schema);
            $this->_dba->replace(DBA_SCHEMA_KEY, $schema);
        }
        return $this->_dba->close();
    }

    /**
     * Creates a new table. Note, this closes any open table.
     *
     * @param string $tableName   name of the table to create
     * @param array  $schema field schema for the table
     */
    function create ($tableName, $schema)
    {
        // pack the schema
        $packedSchema = $this->_packSchema($schema);

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
     * Check whether table exists
     *
     * @access public
     * @param $key string
     * @returns boolean
     */
    function tableExists ($tableName)
    {
        return $this->_dba->db_exists($tableName);
    }

    /**
     * Returns the current read status for the database
     *
     * @returns boolean
     */
    function isOpen ()
    {
        return $this->_dba->isOpen();
    }

    /**
     * Returns the current read status for the database
     *
     * @returns boolean
     */
    function isReadable ()
    {
        return $this->_dba->isReadable();
    }

    /**
     * Returns the current write status for the database
     *
     * @returns boolean
     */
    function isWritable ()
    {
        return $this->_dba->isWritable();
    }

    /**
     * Returns whether a field exists in the current table's schema
     *
     * @returns boolean
     */
    function fieldExists($fieldName)
    {
        return ($this->isOpen() && isset($this->_schema[$fieldName]));
    }

    /**
     * Aquire an exclusive lock on the table
     *
     * @returns object PEAR_Error on failure
     */
    function lockTableEx ()
    {
        return ($this->_dba->reopen('w'));
    }

    /**
     * Aquire a shared lock on the table
     *
     * @returns object PEAR_Error on failure
     */
    function lockTableSh ($table_name)
    {
        return ($this->_dba->reopen('r'));
    }

    /**
     * DBA_Table keeps an internal row index (key)
     * This function returns the highest row index
     *
     * @access private
     * @returns mixed a number or false if there are no keys
     */
    function _findMaxKey()
    {
        $maxKey = 0;
        $key = $this->_dba->firstkey();
        while ($key) {
            $key = $this->_dba->nextkey($key);
            if (is_numeric ($key) && ($key > $maxKey)) {
                $maxKey = $key;
            }
        }
        return $maxKey;
    }

    /**
     * Returns a unique key to be used as a row index
     *
     * @access private
     * @returns integer a new key
     */
    function _getUniqueKey()
    {
        // find the maxKey if necessary
        if (!isset ($this->_maxKey)) {
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
     * @param   array  $field field to pack
     * @param   string $value value of this field to pack
     * @returns string packed version of value, as per $field spec
     */
    function _packField($field, $value)
    {
        switch ($this->_schema[$field]['type'])
        {
            case 'set':
                if (is_string($value)) {
                    $value = explode(',', $value);
                }

                if (is_array($value)) {
                    $c_value = array();
                    foreach ($value as $element) {
                        if (is_string($element)) {
                            $c_element = array_search($element,
                                         $this->_schema[$field]['domain']);
                            if (!is_null($c_element)) {
                                $c_value[] = $c_element;
                            }
                        }
                    }
                    $c_value = implode(',',$c_value);
                }
                break;
            case 'enum':
                if (is_string ($value)) {
                    $c_value = array_search($value,
                                      $this->_schema[$field]['domain']);
                    if (!is_null($c_value)) {
                        $c_value = strval($c_value);
                    }
                }
                break;
            case 'timestamp':
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
            case 'boolean': case 'bool':
                if (is_bool ($value)) {
                    $c_value = strval ($value);
                } else {
                    if (is_string ($value)) {
                        // convert a 'boolean' string into a string boolean
                        $c_value = strval(in_array(strtolower($str),
                                           array('t','true','y','yes','1')));
                    }
                }
                break;
            case 'text':
                if (is_string ($value)) {
                    $c_value = strval ($value);
                }
                break;
            case 'varchar':
                if (is_string ($value)) {
                    if ($this->_schema[$field]['size']) {
                        $c_value = rtrim(substr($value, 0,
                                        $this->_schema[$field]['size']));
                    } else {
                        $c_value = rtrim ($value);
                    }
                }
                break;
            case 'integer': case 'int':
            case 'float':
            case 'numeric':
                if (is_numeric ($value)) {
                    $c_value = strval ($value);
                }
                break;
        }
        return $c_value;
    }

    /**
     * Converts a field from its packed representation to its original value
     *
     * @param   $field string  field to convert
     * @param   $field string  packed value
     * @returns mixed
     */
    function _unpackField($field, $value)
    {
        switch ($this->_schema[$field]['type'])
        {
            case 'set':
                $c_value = array();
                $value = explode (',',$value);
                if (is_array($value)) {
                    foreach ($value as $element) {
                        $c_value[] = $this->_schema[$field]['domain'][$element];
                    }
                }
                return $c_value;
            case 'enum':
                return $this->_schema[$field]['domain'][$value];
            case 'bool':
                if ($value != '1') {
                    return true;
                } else {
                    return false;
                }
            case 'timestamp':
            case 'integer':
                return intval($value);
            case 'float':
            case 'numeric':
                return floatval($value);
            case 'varchar':
                return $value;
        }
    }

    /**
     * Converts a field from its native value to a value, that is
     * sets are converted to strings, bools are converted to 'true' and 'false'
     * timestamps are converted to a readable date. No more operations
     * should be performed on a field after this though.
     *
     * @access private
     * @param $field  mixed
     * @param $value  string
     * @returns string
     */
    function _finalizeField($field, $value)
    {
        switch ($this->_schema[$field]['type'])
        {
            case 'set':
                $buffer = '';
                foreach ($value as $element) {
                    $buffer .= "$element, ";
                }
                return substr($buffer,0 ,-2);
            case 'bool':
                if ($value) return "true";
                return "false";
            case 'timestamp':
                if ($format = $this->_schema[$field]['format']) {
                    return date($format, $value);
                } else {
                    return date($this->_dateFormat, $value);
                }
            default:
                return $value;
        }
    }

    /**
     * Returns a string for a field structure
     *
     * The following is the grammar for each element as packed
     * ENUM => name;type=enum;domain=[element1,...]
     * SET  => name;type=set;domain=[element1,...]
     * TIMESTAMP => name;type=timestamp;format=<string>;init=<num>
     * BOOL => name;type=bool;init=[true, false]
     * TEXT => name;type=text;init=<string>
     * VARCHAR => name;varchar;size=<num>;init=<string>
     * NUMERIC => name;int;size=<num>;init=<string>
     *
     * @param $schema array schema to pack
     * @returns string the packed schema
     */
    function _packSchema ($schema)
    {
        foreach ($schema as $fieldName => $fieldMeta)
        {
            $buffer = $fieldName;
            foreach ($fieldMeta as $attribute => $value)
            {
                $attribute = strtolower($attribute);
                $buffer .= ';'.$attribute.'=';
                switch ($attribute)
                {
                    case 'domain':
                        $buffer .= implode(',',$value);
                        break;
                    case 'type':
                        $buffer .= strtolower($value);
                        break;
                    case 'autoincrement':
                        if (isset($fieldMeta['floor'])) {
                            $buffer .= $value.';floor=0';
                            break;
                        }
                    case 'autodecrement':
                        if (!isset($fieldMeta['ceiling'])) {
                            $buffer .= $value.';ceiling=0';
                            break;
                        }
                    default:
                        $buffer .= $value;
                }
            }
            $fields[] = $buffer;
        }
        return $this->_packRawRow($fields);
    }

    /**
     * Unpacks a raw string as created by _packSchema into an array
     * structure for use as $this->_schema
     *
     * @access private
     * @param $rawFieldString string data to be unpacked into the schema
     * @returns array
     */
    function _unpackSchema ($rawFieldString)
    {
        $rawFields = $this->_unpackRawRow($rawFieldString);
        foreach ($rawFields as $rawField)
        {
            $rawMeta = explode(';',$rawField);
            $name = array_shift($rawMeta);
            foreach ($rawMeta as $rawAttribute)
            {
                list($attribute,$rawValue) = explode('=',$rawAttribute);
                if ($attribute == 'domain')
                {
                    $value = explode(',',$rawValue);
                } else {
                    $value = $rawValue;
                }
                $fields[$name][$attribute] = $value;
            }
        }
        return $fields;
    }

    /**
     * @access private
     */
    function _packRow ($data)
    {
        $buffer = array();
        $i = 0;
        foreach ($this->_schema as $fieldName => $fieldMeta) {

            if (isset($data[$fieldName])) {

                // data ordering is explicit, e.g. field=>data
                $c_value = $this -> _packField($fieldName, $data[$fieldName]);

            } elseif (isset($data[$i])) {

                // data ordering is implicit, e.g. $i=>data
                $c_value = $this -> _packField($fieldName, $data[$i]);

            } else {
                
                // no data is supplied
                if ($fieldMeta['autoincrement']) {
                    // get a value as well as increase the ceiling
                    $c_value = ++$this->_schema[$fieldName]['ceiling'];
                } elseif ($fieldMeta['autodecrement']) {
                    // get a value and decrease the floor
                    $c_value = --$this->_schema[$fieldName]['floor'];
                } else {
                    // use the default value
                    $c_value = $this->_packField($fieldName,
                                                 $fieldMeta['default']);
                }
            }
            $buffer[] = $c_value;
            ++$i;
        }
        return $this->_packRawRow($buffer);
    }

    /**
     * @access private
     */
    function _unpackRow ($packedData)
    {
        $data = $this->_unpackRawRow($packedData);
        $i = 0;
        foreach ($this->_schema as $fieldName => $fieldMeta) {
            $buffer[$fieldName] = $this->_unpackField($fieldName, $data[$i]);
            $i++;
        }
        return $buffer;
    }

    /**
     * @access private
     */
    function _packRawRow ($unpackedData)
    {
        return implode('|', $unpackedData);
    }

    /**
     * @access private
     */
    function _unpackRawRow ($packedData)
    {
        return explode('|', $packedData);
    }

    /**
     * Inserts a new row in a table
     * 
     * @param   array $data assoc array or ordered list of data to insert
     * @returns mixed PEAR_Error on failure, the row index on success
     */
    function insertRow ($data)
    {
        if ($this->isWritable()) {
            $key = $this->_getUniqueKey();
            $result = $this->_dba->insert($key, $this->_packRow($data));
            if (PEAR::isError($result)) {
                return $result;
            } else {
                return $key;
            }
        } else {
            return $this->raiseError('DBA: table not open');
        }
    }

    /**
     * @access public
     */
    function replaceRow ($key, $data)
    {
        if ($this->isOpen()) {
            return $this->_dba->replace($key, $this->_packRow($data));
        } else {
            return $this->raiseError('DBA: table not open');
        }
    }

    /**
     * @access public
     */
    function deleteRow ($key)
    {
        return $this->_dba->delete($key);
    }

    /**
     * @access public
     */
    function getRow ($key)
    {
        return $this->_unpackRow($this->_dba->fetch($key));
    }

    /**
     * @access public
     */
    function finalizeRows ($rows=null)
    {
        if (is_null($rows)) {
            if ($this->_dba->isOpen()) {
                $rows = $this->getRows();
            } else {
                return $this->raiseError('DBA: table not open and no rows');
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
     * @access public
     */
    function getRows ($rowKeys=null)
    {
        $rows = array();
        if ($this->_dba->isOpen()) {
            $key = $this->_dba->firstkey();
            while ($key) {
                if ($key != DBA_SCHEMA_KEY) {
                    if (is_null($rowIDs)) {
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
            return $this->raiseError('DBA: table not open');
        }
        return $rows;
    }

    /**
     * @access private
     */
    function _addSpaces ($string)
    {
        foreach (array('(',')','==','!=','>','<','<=','>=') as $symbol) {
            $string = str_replace($symbol, " $symbol ", $string);
        }
        return $string;
    }

    /**
     * @access private
     */
    function _parsePHPQuery ($rawQuery, $fieldTokens)
    {
        // add spaces around symbols for strtok to work properly
        $rawQuery = $this->_addSpaces($rawQuery);

        // begin building the php query for a row
        $phpQuery = '';

        // scan the tokens in the raw query to build a new query
        // if the token is a field name, use it as a key in $row[]
        $token = strtok($rawQuery, ' ');
        while ($token) {
            // is this token a field name?
            if (in_array($token, $fieldTokens)) {
                $phpQuery .= "\$row['$token']";
            } else {
                $phpQuery .= $token;
            }
            $token = strtok(' ');
        }
        return $phpQuery;
    }

    /**
     * @access public
     */
    function select ($rawQuery, $rows=null)
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
            return $this->raiseError('DBA: table not open');
        }
    }

    /**
     * Comparison function for sorting ascending order
     * @access private
     */
    function _sortCmpA ($a, $b)
    {
        foreach ($this->_sortFields as $field) {
            if ($a[$field] < $b[$field]) return -1;
            if ($a[$field] > $b[$field]) return 1;
        }
        return 0;
    }

    /**
     * Comparison function for sorting descending order
     * @access private
     */
    function _sortCmpD ($a, $b)
    {
        foreach ($this->_sortFields as $field) {
            if ($a[$field] < $b[$field]) return 1;
            if ($a[$field] > $b[$field]) return -1;
        }
        return 0;
    }

    /**
     * @access private
     */
    function _parseFieldString ($fieldString)
    {
        $fields = array();
        $token = strtok($fieldString, ' ,');
        while ($token) {
            $fields[] = $token;
            $token = strtok(' ,');
        }
        return $fields;
    }

    /**
     * @access public
     */
    function sort ($fields, $order='a', $rows=null)
    {
        if ($this->_dba->isOpen()) {
            if (is_string($fields)) {
                // parse the sort string to produce an array of sort fields
                $this->_sortFields = $this->_parseFieldString($fields);
            } else {
                if (is_array($fields)) {
                    // we already have an array of sort fields
                    $this->_sortFields = $fields;
                }
            }

            // if we haven't passed any rows to select from, use the whole table
            if (is_null($rows))
                $rows = $this->getRows();

            if ($order=='a')
                uasort($rows, array($this, '_sortCmpA'));
            else
                uasort($rows, array($this, '_sortCmpD'));

            return $rows;
        } else {
            return $this->raiseError('DBA: table not open');
        }
    }

    /**
     * @access public
     */
    function project ($fields, $rows=null)
    {
        if ($this->_dba->isOpen()) {
            $projectFields = array();
            if (is_string($fields)) {
                $projectFields = $this->_parseFieldString($fields);
            } else {
                if (is_array($fields)) {
                    // we already have an array of fields
                    $projectFields = $fields;
                }
            }

            if (is_null($rows))
                $rows = $this->getRows();

            foreach ($rows as $key=>$row) {
                foreach ($projectFields as $field) {
                    $projectedRows[$key][$field] = $row[$field];
                }
            }
            return $projectedRows;
        } else {
            return $this->raiseError('DBA: table not open');
        }
    }

    /**
     * @access public
     */
    function cmpRows ($a, $b)
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
     * @access public
     */
    function unique($rows=null)
    {
        if ($this->_dba->isOpen()) {

            if (is_null($rows))
                $rows = $this->getRows();

            $results = array();
            foreach ($rows as $key=>$row) {
                if (!isset($current) || ($current != $row)) {
                    $results[$key] = $row;
                    $current=$row;
                }
            }
            return $results;
        } else {
            return $this->raiseError('DBA: table not open');
        }
    }
}
