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
 * @version 0.0.12
 */
class DBA_Relational extends PEAR
{
    // table handles
    var $_tables=array();

    // handle to manager table; used for handling multi-table operations
    var $_manager;

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
    function DBA_Relational($home = '', $driver = 'simple')
    {
        // add trailing slash if not present
        if (substr($home, -1) != '/') {
            $home = $home.'/';
        }
        $this->_home = $home;

        $this->_driver = $driver;

        // initialize the manager table
        $this->_manager =& new DBA_Table($this->_driver);
    }

    /**
     * Closes all open tables
     *
     * @access public
     */
    function close()
    {
        if (sizeof($this->_tables)) {
            reset($this->_tables);
            $this->_tables[key($this->_tables)]->close();
            while(next($this->_tables)) {
                $this->_tables[key($this->_tables)]->close();
            }
        }
    }

    /**
     * PEAR emulated destructor calls close on PHP shutdown
     * @access  private
     */
    function _DBA_Relational()
    {
//        echo "I'm Melting!\n";
        $this->close();
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
    function _openTable($tableName, $mode = 'r')
    {
        if (!isset($this->_tables[$tableName])) {
            if (!$this->_manager->tableExists($this->_home.$tableName)) {
                return $this->raiseError("DBA: table '$tableName' does not exist");
            } else {
                $this->_tables[$tableName] =& new DBA_Table($this->_driver);
            }
        }

        if (!$this->_tables[$tableName]->isOpen()) {
            return $this->_tables[$tableName]->open($this->_home.$tableName, $mode);
        } else {
            if (($mode == 'r') && !$this->_tables[$tableName]->isReadable()) {
                // obtain a shared lock on the table
                return $this->_tables[$tableName]->lockSh();
            } elseif (($mode == 'w') && !$this->_tables[$tableName]->isWritable()){
                // obtain an exclusive lock on the table
                return $this->_tables[$tableName]->lockEx();
            }
        }
    }

    /**
     * Returns whether the specified table exists in the db home
     *
     * @param string $tableName table to check existence of
     * @returns boolean
     */
    function tableExists($tableName)
    {
        return (!PEAR::isError($this->_openTable($tableName)));
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
     * Returns an array with the stored schema for the table
     *
     * @param   string $tableName
     * @returns array
     */
    function getSchema($tableName)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->getSchema();
        }
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
     * @param   string $tableName table on which to operate
     * @param   array  $data assoc array or ordered list of data to insert
     * @returns mixed  PEAR_Error on failure, the row index on success
     */
    function insert($tableName, $data)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->insert($data);
        }
    }

    /**
     * Replaces an existing row in a table, inserts if the row does not exist
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   string $key row id to replace
     * @param   array  $data assoc array or ordered list of data to insert
     * @returns mixed  PEAR_Error on failure, the row index on success
     */
    function replace($tableName, $key, $data)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->replace($key, $data);
        } 
    }

    /**
     * Deletes an existing row in a table
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   string $key row id to delete
     * @returns object PEAR_Error on failure
     */
    function delete($tableName, $key)
    {
        $result = $this->_openTable($tableName, 'w');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->delete($key);
        }
    }

    /**
     * Fetches an existing row from a table
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   string $key row id to fetch
     * @returns mixed  PEAR_Error on failure, the row array on success
     */
    function fetch($tableName, $key)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->fetch($key);
        }
    }

    /**
     * Performs a select on a table. This means that a subset of rows in a
     * table are filtered and returned based on the query. Accepts any valid
     * expression of the form '(field == field) || (field > 3)', etc. Using the
     * expression '*' returns the entire table
     * SQL analog: 'select * from rows where rawQuery'
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   string $rawQuery query expression for performing the select
     * @param   array  $rows rows to select on
     * @returns mixed  PEAR_Error on failure, the row array on success
     */
    function select($tableName, $query, $rows=null)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->select($query, $rows);
        }
    }


    /**
     * Sorts rows by field in either ascending or descending order
     * SQL analog: 'select * from rows, order by fields'
     *
     * @access  public
     * @param   mixed  $fields a string with the field name to sort by or an
     *                         array of fields to sort by in order of preference
     * @param   string $order 'a' for ascending, 'd' for descending
     * @param   array  $rows rows to sort, sorts the entire table if not
     *                       specified
     * @returns mixed  PEAR_Error on failure, the row array on success
     */
    function sort($fields, $order='a', $rows)
    {
        return $this->_manager->sort($fields, $order, $rows);
    }

    /**
     * Projects rows by field. This means that a subset of the possible fields
     * are in the resulting rows. The SQL analog is 'select fields from table'
     *
     * @access  public
     * @param   array $fields fields to project
     * @param   array $rows rows to project, projects entire table if not
     *                      specified
     * @returns mixed  PEAR_Error on failure, the row array on success
     */
    function project($fields, $rows)
    {
        return $this->_manager->project($fields, $rows);
    }

    /**
     * Returns the unique rows from a set of rows
     *
     * @access  public
     * @param   array  $rows rows to process, uses entire table if not
     *                     specified
     * @returns mixed  PEAR_Error on failure, the row array on success
     */
    function unique($rows)
    {
        return $this->_manager->unique($rows);
    }

    /**
     * Converts the results from any of the row operations to a 'finalized'
     * display-ready form. That means that timestamps, sets and enums are
     * converted into strings. This obviously has some consequences if you plan
     * on chaining the results into another row operation, so don't call this
     * unless it is the final operation.
     *
     * This function does not yet work reliably with the results of a join
     * operation, due to a loss of metadata
     *
     * @access  public
     * @param   string $tableName table on which to operate
     * @param   array $rows rows to finalize, if none are specified, returns
     *                      the whole table
     * @returns mixed  PEAR_Error on failure, the row array on success
     */
    function finalize($tableName, $rows=null)
    {
        $result = $this->_openTable($tableName, 'r');
        if (PEAR::isError($result)) {
            return $result;
        } else {
            return $this->_tables[$tableName]->finalize($rows);
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
        // add spaces around symbols for explode to work properly
        $rawQuery = DBA_Table::_addSpaces($rawQuery);

        // begin building the php query for a row
        $phpQuery = '';

        // scan the tokens in the raw query to build a new query
        // if the token is a field name, use it as a key in $rowN[]
        $tokens = explode(' ', $rawQuery);
        foreach ($tokens as $token) {
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
    function join($tableA, $tableB, $rawQuery)
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
