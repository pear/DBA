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
define ('DBA_TABLE_META', '__meta__');

/**
 * DBA Table
 * This class provides a simple, single-table database system.
 * It uses a DBA class as the storage driver.
 *
 * @author Brent Cook <busterb@mail.utexas.edu>
 * @version 0.0.2
 */
class DBA_Table
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
    var $_fieldSchema;

    /**
     * @access private
     */
    var $_dateFormat = 'D M j G:i:s T Y';

    /**
     * Constructor
     * @param object $dba dba object to use for storage, you need this sometime
     */
    function DBATable ($dba = NULL)
    {
        // set the internal dba object
        if (!is_null($dba)) {
            $this->_dba &= $dba;
        }
    }

    /**
     * Opens a table
     *
     * @param string $tableName name of the table to open
     * @param char   $mode      mode to open the table; one of r,w,c,n
     * @param object $dba       dba object to use for storage, you need this
     * @returns boolean false on error, true on success
     */
    function open ($tableName, $mode = 'r', $dba = NULL)
    {
        // set the internal dba object
        if (!is_null($dba)) {
            $this->_dba &= $dba;
        }

        if (!($this->_dba->open($tableName, $mode))) {
            return false;
        }

        // fetch the field descriptor at the key, DBA_TABLE_META
        if ($fieldString = $this->_dba->fetch(DBA_TABLE_META)) {
            // unpack the field descriptor into a PHP structure
            $this->_fieldSchema = $this->_unpackFieldSchema($fieldString);
            return true;
        } else {
            trigger_error('DBA: Table is missing field descriptor at key, '.
                           DBA_TABLE_META, E_USER_WARNING);
            return false;
        }
    }

    /**
     * Closes a table
     * @returns boolean false on error, true on success
     */
    function close ()
    {
        if ($this->_dba->isWritable()) {
            // pack up the field structure and store it back in the table
            $fieldString = $this->_packFieldSchema($this->_fieldSchema);
            $this->_dba->replace(DBA_TABLE_META, $fieldString);
            return $this->_dba->close();
        }
        return true;
    }

    /**
     * Creates a new table. Note, this closes any open table if $dba is not
     * specified
     *
     * @param string $tableName   name of the table to create
     * @param array  $fieldSchema field schema for the table
     * @param object $dba         dba object to use
     */
    function create ($tableName, $fieldSchema, $dba=NULL)
    {
        // pack the fieldSchema
        $fieldString = $this->_packFieldSchema($fieldSchema);

        if (is_null($dba)) {
            if (is_object($this->_dba)) {
                // close any open table, since this opens a new database with
                // the same internal dba object
                $r = $this->close();
                $r = $r && $this->_dba->open($tableName, 'n');
                $r = $r && $this->_dba->insert(DBA_TABLE_META, $fieldString);
                $r = $r && $this->_dba->close();
            } else {
                trigger_error("DBA: Could not create $tableName, no dba object".
                               "specified", E_USER_WARNING);
                return false;
        } else {
            $r = $dba->open($tableName, 'n');
            $r = $r && $dba->insert(DBA_TABLE_META, $fieldString);
            $r = $r && $dba->close();
        }
        // return the result of the creation operations
        return $r;
    }

    /**
     * Check whether key exists
     *
     * @param $key string
     * @returns boolean
     */
    function exists ($tableName)
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
        return ($this->isOpen() && isset($this->_fieldSchema[$fieldName]));
    }

    /**
     * Aquire an exclusive lock on the table
     *
     * returns @boolean
     */
    function lockTableEx ()
    {
        return ($this->_dba->reopen('w'));
    }

    /**
     * Aquire a shared lock on the table
     * returns @boolean
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
        switch ($this->_fieldSchema[$field]['type'])
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
                                         $this->_fieldSchema[$field]['domain']);
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
                                      $this->_fieldSchema[$field]['domain']);
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
                        $c_value = strval(in_array(strtolower($str)
                                          ,array('t','true','y','yes','1'));
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
                    if ($this->_fieldSchema[$field]['size']) {
                        $c_value = rtrim(substr($value, 0,
                                        $this->_fieldSchema[$field]['size']));
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
        switch ($this->_fieldSchema[$field]['type'])
        {
            case 'set':
                $c_value = array();
                $value = explode (',',$value);
                if (is_array($value)) {
                    foreach ($value as $element) {
                        $c_value[] = $this->_fieldSchema[$field]['domain'][$element];
                    }
                }
                return $c_value;
            case 'enum':
                return $this->_fieldSchema[$field]['domain'][$value];
            case 'bool':
                if ($value == '1') {
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
     * @param $field  mixed
     * @param $value  string
     * @returns string
     */
    function _finalizeField($field, $value)
    {
        switch ($this->_fieldSchema[$field]['type'])
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
                if ($format = $this->_fieldSchema[$field]['format']) {
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
     * @param $fieldSchema array schema to pack
     * @returns string the packed schema
     */
    function _packFieldSchema ($fieldSchema)
    {
        foreach ($fieldSchema as $fieldName => $fieldMeta)
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
     * Unpacks a raw string as created by _packFieldSchema into an array
     * structure for use as $this->_fieldSchema
     *
     * @access private
     * @param $rawFieldString string data to be unpacked into the schema
     * @returns array
     */
    function _unpackFieldSchema ($rawFieldString)
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

    function _packRow ($data)
    {
        $buffer = array();
        $i = 0;
        foreach ($this->_fieldSchema as $fieldName => $fieldMeta) {

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
                    $c_value = ++$this->_fieldSchema[$fieldName]['ceiling'];
                } elseif ($fieldMeta['autodecrement']) {
                    // get a value and decrease the floor
                    $c_value = --$this->_fieldSchema[$fieldName]['floor'];
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

    function _unpackRow ($packedData)
    {
        $data = $this->_unpackRawRow($packedData);
        $i = 0;
        foreach ($this->_fieldSchema as $fieldName => $fieldMeta) {
            $buffer[$fieldName] = $this->_unpackField($fieldName, $data[$i]);
            $i++;
        }
        return $buffer;
    }

    function _packRawRow ($unpackedData)
    {
        return implode('|', $unpackedData);
    }

    function _unpackRawRow ($packedData)
    {
        return explode('|', $packedData);
    }

    /**
     * Inserts a new row in a database
    function insertRow ($data)
    {
        if ($this->isOpen()) {
            $key = $this->_getUniqueKey();
            if ($this->_dba->insert($key, $this->_packRow($data))) {
                return $key;
            } else
                return false;
        } else {
            return false;
        }
    }

    function replaceRow ($key, $data)
    {
        if ($this->isOpen()) {
            return $this->_dba->replace($key, $this->_packRow($data));
        }
    }

    function deleteRow ($key)
    {
        return $this->_dba->delete($key);
    }

    function getRow ($key)
    {
        return $this->_unpackRow($this->_dba->fetch($key));
    }

    function finalizeRows ($rows=null)
    {
        if ($this->_dba->isOpen()) {
            if (is_null($rows)) {
                $rows = $this->getRows();
            }

            foreach ($rows as $key=>$row) {
                foreach ($row as $field=>$data) {
                    $row[$field] = $this->_finalizeField($field, $row[$field]);
                }
                $rows[$key] = $row;
            }
            return $rows;
        }
    }

    function getRows ($rowKeys=null)
    {
        $rows = array();
        if ($this->_dba->isOpen()) {
            $key = $this->_dba->firstkey();
            while ($key) {
                if ($key != DBA_TABLE_META) {
                    if (is_null($rowIDs)) {
                        $rows[$key] = $this->_unpackRow($this->_dba->fetch($key));
                    } else {
                        if (in_array($key, $rowKeys)) {
                            $rows[$key] = $this->_unpackRow($this->_dba->fetch($key));
                        }
                    }
                }
                $key = $this->_dba->nextkey($key);
            }
        }
        return $rows;
    }

    function _addSpaces ($string)
    {
        foreach (array('(',')','==','!=','>','<','<=','>=') as $symbol) {
            $string = str_replace($symbol, " $symbol ", $string);
        }
        return $string;
    }

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

    function select ($rawQuery, $rows=null)
    {
        if ($this->_dba->isOpen()) {

            // get a list of valid field names
            $fieldTokens = array_keys($this->_fieldSchema);

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
        }
    }

    function _sortCmpA ($a, $b)
    {
        foreach ($this->_sortFields as $field) {
            if ($a[$field] < $b[$field]) return -1;
            if ($a[$field] > $b[$field]) return 1;
        }
        return 0;
    }

    function _sortCmpD ($a, $b)
    {
        foreach ($this->_sortFields as $field) {
            if ($a[$field] < $b[$field]) return 1;
            if ($a[$field] > $b[$field]) return -1;
        }
        return 0;
    }

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
        }
    }

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
        }
    }

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

    function unique ($rows=null)
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
        }
    }
}
