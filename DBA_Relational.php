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

require_once('DB_DBA/DBA_Table.php');

/**
 * a relational database manager for DBM-style databases
 *
 * @author Brent Cook <busterb@mail.utexas.edu>
 * @version 0.0.1
 */
class DBA_Relational
{
    // table handles
    var $_tables=array();

    // location of table data files
    var $_home;

    /**
     * Constructor
     * @param $_home
     */
    function DBA_Relational ($_home = '')
    {
        // add trailing slash if not present
        if (substr($_home, -1) != '/') {
            $this->_home = $_home.'/';
        } else {
            $this->_home = $_home;
        }
    }

    function close ()
    {
        foreach ($this->_tables as $table) {
            $table->close();
        }
    }
    
    /**
     * Opens a database. 
     * @param $tableName
     * @param $mode
     */
    function openTable ($tableName, $mode = 'r')
    {
        if (!isset($this->_tables[$tableName])) {

            $this->_tables[$tableName] = new DBA_Table($this->_home.$tableName,
                                                      $mode);

            if (!$this->_tables[$tableName]->isOpen()) {
                unset($this->_tables[$tableName]);
                return PEAR::raiseError("Table: '$tableName' does not exist");
            }
        }

        if ($this->_tables[$tableName]->isOpen()) {

            if ((($mode == 'r') && $this->_tables[$tableName]->isReadable())
               || (($mode == 'w') && $this->_tables[$tableName]->isWritable())) {
                return true;
            } else {
                $this->_tables[$tableName]->close();
            }
        }
        return $this->_tables[$tableName]->open($this->_home.$tableName, $mode);
    }

    function formatResults($results, $fields=null)
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
     *
     */
    function closeTable ($tableName)
    {
        if (isset($this->_tables[$tableName]))
            return $this->_tables[$tableName]->close();
        else
            return false;
    }

    function createTable ($tableName, $schema)
    {
        // check if this table object exists
        if (!isset($this->_tables[$tableName])) {
            $this->_tables[$tableName] = new DBA_Table();
        else
            return false;  // the table object exists, so the table must exist

        // ask if the table really exists
        if (!$this->_tables[$tableName]->exists($this->_home.$tableName))
            return $this->_tables[$tableName]->create($this->_home.$tableName, $schema);
        else
            return false;
    }

    function isOpen ($tableName)
    {
        if (isset($this->_tables[$tableName]))
            return $this->_tables[$tableName]->isOpen();
        else
            return false;
    }

    function dropTable ($tableName)
    {
        if ($this->openTable($tableName, 'w'))
        {
            unset($this->_tables[$tableName]);
            return $this->_tables[$tableName]->dropTable($tableName);
        } else {
            return false;
        }
    }

    function insertRow ($tableName, $data)
    {
        if ($this->openTable($tableName, 'w'))
            return $this->_tables[$tableName]->insertRow($data);
        else
            return false;
    }

    function replaceRow ($tableName, $key, $data)
    {
        if ($this->openTable($tableName, 'w'))
            return $this->_tables[$tableName]->replaceRow($key, $data);
        else
            return false;
    }

    function deleteRow ($tableName, $key)
    {
        if ($this->openTable($tableName, 'w'))
            return $this->_tables[$tableName]->deleteRow($key);
        else
            return false;
    }

    function fetchRow ($tableName, $key)
    {
        if ($this->openTable($tableName, 'r'))
            return $this->_tables[$tableName]->fetchRow($key);
        else
            return false;
    }

    function select ($tableName, $query, $rows=null)
    {
        if ($this->openTable($tableName, 'r')) {
            return $this->_tables[$tableName]->select($query, $rows);
        } else {
            return false;
        }
    }

    function sort ($tableName, $fields, $order='a', $rows=null)
    {
        if ($this->openTable($tableName, 'r'))
            return $this->_tables[$tableName]->sort($fields, $order, $rows);
        else
            return false;
    }

    function project ($tableName, $fields, $rows=null)
    {
        if ($this->openTable($tableName, 'r'))
            return $this->_tables[$tableName]->project($fields, $rows);
        else
            return false;
    }

    function unique ($tableName, $rows=null)
    {
        if ($this->openTable($tableName, 'r'))
            return $this->_tables[$tableName]->unique($rows);
        else
            return false;
    }

    function finalizeRows($tableName, $rows=null)
    {
        if ($this->openTable($tableName, 'r'))
            return $this->_tables[$tableName]->finalizeRows($rows);
        else
            return false;
    }

    function _validateTable (&$table, &$rows, &$fields, $altName)
    {
        // validate query by checking for existence of fields
        if (is_string($table) && ($this->openTable($table, 'r'))) {

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

    function _parsePHPQuery ($rawQuery, $fieldsA, $fieldsB, $tableA, $tableB)
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
    
    function join ($tableA, $tableB, $rawQuery)
    {
        // validate tables
        if (!$this->_validateTable($tableA, $rowsA, $fieldsA, 'A'))
            return false;
        if (!$this->_validateTable($tableB, $rowsB, $fieldsB, 'B'))
            return false;

        // check for empty tables
        if (is_null($rowsA) && !is_null($rowsB))
            return $rowsB;
        if (!is_null($rowsA) && is_null($rowsB))
            return $rowsA;
        if (is_null($rowsA) && is_null($rowsB))
            return array();
        
        // build the join operation with nested loops
        $PHPJoin = 'foreach ($rowsA as $rowA) foreach ($rowsB as $rowB) if ('.
          $this->_parsePHPQuery($rawQuery, $fieldsA, $fieldsB, $tableA, $tableB)
          .') $results[] = array_merge($rowA, $rowB);';

        // evaluate the join
        eval ($PHPJoin);

        return $results;
    }
}
