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
// | Author: Brent Cook <busterb@mail.utexas.edu>                         |
// +----------------------------------------------------------------------+
//
// $Id$

require_once 'PEAR.php';
require_once 'DB/DBA/DBA_Table.php';

/**
 * a relational database manager for DBM-style databases
 *
 * @author Brent Cook <busterb@mail.utexas.edu>
 * @version 0.0.11
 */
class DBA_Relational extends PEAR
{
    // table handles
    var $_tables=array();

    // location of table data files
    var $_home;

    // table driver to use
    var $_driver;

    /**
     * Constructor
     *
     * @param string  $home path where data files are stored
     * @param string  $driver DBA driver to use
     *
     */
    function DBA_Relational ($home = '', $driver = 'simple')
    {
        // add trailing slash if not present
        if (substr($home, -1) != '/') {
            $this->_home = $home.'/';
        } else {
            $this->_home = $home;
        }
        $this->_driver = $driver;
    }

    /**
     * Closes all open tables
     *
     * @access public
     */
    function close ()
    {
        foreach ($this->_tables as $table) {
            $table->close();
        }
    }
    
    /**
     * Opens a table, keeps it in the list of tables. Can also reopen tables
     * to different file modes
     *
     * @access private
     * @param string $tableName name of the table to open
     * @param char   $mode      mode to open the table; one of r,w,c,n
     * @returns object PEAR_Error on failure
     */
    function _openTable ($tableName, $mode = 'r')
    {
        if (!isset($this->_tables[$tableName])) {

            $this->_tables[$tableName] = new DBA_Table($this->_driver);

            if (!$this->_tables[$tableName]->tableExists($tableName)) {
                unset($this->_tables[$tableName]);
                return $this->raiseError("Table: '$tableName' does not exist");
            }
        }

        if ($this->_tables[$tableName]->isOpen()) {

            if (!((($mode == 'r') && $this->_tables[$tableName]->isReadable())
               || (($mode == 'w') && $this->_tables[$tableName]->isWritable())))
            {
                $this->_tables[$tableName]->close();
            }
        }
        return $this->_tables[$tableName]->open($this->_home.$tableName, $mode);
    }

    /**
     * Generates a nice, ASCII table from a results set, a-la MySQL
     *
     * @param array $results
     * @param array $fields  list of fields to cull from results and display
     * @returns string
     */
    function formatResults($results, $fields = null)
    {
        if (is_array($results) && sizeof($results)) {

            if (is_null($fields))
                $fields = array_keys(current($results));

            // get the maximum length of each field
            foreach ($fields as $key=>$field) {
                $longest[$key] = strlen($field) + 1;
                foreach ($results as $result) {
                    $resultLen = strlen($result[$field]) + 1;
                    if ($resultLen > $longest[$key])
                        $longest[$key] = $resultLen;
                }
            }

            // generate separator line
            foreach ($longest as $length)
                $separator .= '+-'.str_repeat('-',$length);
            $separator .= "+\n";

            $buffer = $separator;
            
            // print fields
            foreach ($fields as $key=>$field)
                $buffer .= '| '.str_pad($field, $longest[$key]);
            $buffer .= "|\n$separator";

            // print rows
            foreach ($results as $result) {
                foreach ($fields as $key=>$field)
                    $buffer .= '| '.str_pad($result[$field], $longest[$key]);
                $buffer .= "|\n$separator";
            }
        }
        return $buffer;
    }
    

    /**
     * Creates a new table
     *
     * @access public
     * @param   string $tableName   name of the table to create
     * @param   array  $schema field schema for the table
     * @returns object PEAR_Error on failure
     */
    function createTable($tableName, $schema)
    {
        // check if this table object exists
        if (!isset($this->_tables[$tableName])) {
            $this->_tables[$tableName] = new DBA_Table();
        } else {
            // the table object exists, so the table must exist
            return $this->raiseError("DBA: cannot create table: $tableName,".
                                     " it already exists");
        }

        return $this->_tables[$tableName]->create($this->_home.$tableName,
                                                  $schema);
    }

    /**
     * Returns the current read status for the database
     *
     * @returns boolean
     */
    function isOpen($tableName)
    {
        if (isset($this->_tables[$tableName])) {
            return $this->_tables[$tableName]->isOpen();
        } else {
            return false;
        }
    }

    /**
     * Inserts a new row in a table
     *
     * @param   string $tableName table to insert on
     * @param   array  $data assoc array or ordered list of data to insert
     * @returns mixed  PEAR_Error on failure, the row index on success
     */
    function insertRow($tableName, $data)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->insertRow($data);
        }
    }

    /**
     * @access public
     */
    function replaceRow($tableName, $key, $data)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->replaceRow($key, $data);
        } 
    }

    /**
     * @access public
     */
    function deleteRow($tableName, $key)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->deleteRow($key);
        }
    }

    /**
     * @access public
     */
    function fetchRow($tableName, $key)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->fetchRow($key);
        }
    }

    /**
     * @access public
     */
    function select ($tableName, $query, $rows=null)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->select($query, $rows);
        }
    }

    /**
     * @access public
     */
    function sort ($tableName, $fields, $order='a', $rows=null)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->sort($fields, $order, $rows);
        }
    }

    /**
     * @access public
     */
    function project ($tableName, $fields, $rows=null)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->project($fields, $rows);
        }
    }

    /**
     * @access public
     */
    function unique ($tableName, $rows=null)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->unique($rows);
        }
    }

    /**
     * @access public
     */
    function finalizeRows($tableName, $rows=null)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->finalizeRows($rows);
        }
    }

    /**
     * @access private
     */
    function _validateTable(&$table, &$rows, &$fields, $altName)
    {
        // validate query by checking for existence of fields
        if (is_string($table) && !PEAR::isError($this->_openTable($table, 'r')))
        {

            $rows = $this->_tables[$table]->getRows();
            $fields = $this->_tables[$table]->getFieldNames();
            return true;

        } elseif (is_array($table) && sizeof($table)) {
            reset($table);
            $rows = $table;
            $fields = array_keys(current($table));
            $table = $altName;
            return true;
        }
        $fields = null;
        $rows = null;
        return false;
    }

    /**
     * @access private
     */
    function _parsePHPQuery($rawQuery, $fieldsA, $fieldsB, $tableA, $tableB)
    {
        // add spaces around symbols for strtok to work properly
        $rawQuery = DBA_Table::_addSpaces($rawQuery);

        // begin building the php query for a row
        $phpQuery = '';

        // scan the tokens in the raw query to build a new query
        // if the token is a field name, use it as a key in $row[]
        $token = strtok($rawQuery, ' ');
        while ($token) {
            // is this token a field name?
            if ($i = strpos($token, '.')) {
                // trim everything after the '.'
                $table = substr($token, 0, $i);

                // trim everything before the '.'
                $field = substr($token, $i+1);
                if (($table == $tableA) &&
                in_array($field, $fieldsA)) {
                    $phpQuery .= "\$rowA['$field']";
                } else
                if (($table == $tableB) && in_array($field, $fieldsB)) {
                    $phpQuery .= "\$rowB['$field']";
                }
            } else {
                $phpQuery .= $token;
            }
            $token = strtok(' ');
        }
        return $phpQuery;
    }
    
    /**
     * Joins rows between two tables based on a query.
     *
     * @access public
     * @param  string $tableA   name of table to join
     * @param  string $tableB   name of table to join
     * @param  string $rawQuery expression of how to join tableA and tableB
     */
    function join ($tableA, $tableB, $rawQuery)
    {
        // validate tables
        if (!$this->_validateTable($tableA, $rowsA, $fieldsA, 'A')) {
            return $this->raiseError("DBA: $tableA does not match query");
        }

        if (!$this->_validateTable($tableB, $rowsB, $fieldsB, 'B')) {
            return $this->raiseError("DBA: $tableA does not match query");
        }

        // check for empty tables
        if (is_null($rowsA) && !is_null($rowsB)) {
            return $rowsB;
        }
        if (!is_null($rowsA) && is_null($rowsB)) {
            return $rowsA;
        }
        if (is_null($rowsA) && is_null($rowsB)) {
            return array();
        }
        
        // TODO Implement merge join, needs secondary indexes on tables
        // build the join operation with nested loops
        $PHPJoin = 'foreach ($rowsA as $rowA) foreach ($rowsB as $rowB) if ('.
          $this->_parsePHPQuery($rawQuery, $fieldsA, $fieldsB, $tableA, $tableB)
          .') $results[] = array_merge($rowA, $rowB);';

        // evaluate the join
        eval ($PHPJoin);

        return $results;
    }
}
