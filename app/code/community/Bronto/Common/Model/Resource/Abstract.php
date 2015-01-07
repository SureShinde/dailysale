<?php

abstract class Bronto_Common_Model_Resource_Abstract extends Mage_Core_Model_Resource_Setup
{
    protected $_dropStmt = 'DROP TABLE IF EXISTS `{table}`;';

    /**
     * Return the module namespace for this setup
     *
     * eg: 'bronto_common'
     * @return string
     */
    protected abstract function _module();

    /**
     * Return the table definitions belonging to this module
     *
     * eg: array('table' => 'CREATE {table} ...')
     * @return array
     */
    protected abstract function _tables();

    /**
     * Return the update definitions belonging to this module
     *
     * eg: array('1.0.1' => array('table' => array('sql' => 'UPDATE....')))
     * @return array
     */
    protected abstract function _updates();

    /**
     * Gets the internal table name for $key
     *
     * @param string $key
     * @return string
     */
    protected function _tableName($key)
    {
        return $this->getTable("{$this->_module()}/$key");
    }

    /**
     * Replaces the {table} name in the statement
     *
     * @param string $key
     * @param string $string
     * @return string
     */
    protected function _replaceName($key, $string)
    {
        return $this->_replaceText('table', $this->_tableName($key), $string);
    }

    /**
     * Wrapper around str_replace for special replace keys
     *
     * @param string $key
     * @param string $value
     * @param string $original
     * @return string
     */
    protected function _replaceText($key, $value, $original)
    {
        return str_replace('{' . $key . '}', $value, $original);
    }

    /**
     * Repalce the table and all others
     *
     * @param string $table
     * @param string $string
     * @param array $extra
     * @return string
     */
    protected function _replaceStatement($table, $string, $extra = array()) {
        $statement = $this->_replaceName($table, $string);
        foreach ($extra as $key => $value) {
            $statement = $this->_replaceText($key, $value, $statement);
        }
        return $statement;
    }

    /**
     * Triggers the callback for a given update
     *
     * @param array $update
     */
    protected function _updateCallback($state, $table, $update)
    {
        if (array_key_exists($state, $update)) {
            $callback = $update[$state];
            if (is_string($callback)) {
                $callback = array($this, $callback);
            }
            if (is_callable($callback)) {
                call_user_func($callback, $table);
            }
        }
    }

    /**
     * Creates the table with the provided stmt.
     *
     * @param string $table
     * @param array $extra (Optional)
     * @return void
     * @throws RuntimeException is the table does not exist
     */
    public function createTable($table, $extra = array())
    {
        $tables = $this->_tables();
        if (!array_key_exists($table, $this->_tables())) {
            throw new RuntimeException("Table {$table} does not exist.");
        }
        $this->run($this->_replaceStatement($table, $tables[$table], $extra));
    }

    /**
     * Creates all of the tables
     *
     * @param array $extra (Optional)
     */
    public function createTables($extra = array())
    {
        foreach ($this->_tables() as $table => $statement) {
            $this->dropTable($table);
            $this->createTable($table, $extra);
        }
    }

    /**
     * Drops all of the tables
     */
    public function dropTables()
    {
        foreach ($this->_tables() as $table => $_) {
            $this->dropTable($table);
        }
    }

    /**
     * Drops the table with the given key
     *
     * @param string $table
     * @return void
     */
    public function dropTable($table)
    {
        $this->run($this->_replaceName($table, $this->_dropStmt));
    }

    /**
     * Updates the table with the specific commands
     *
     * @param string $table
     * @param string $version
     * @return void
     */
    public function updateTables($version)
    {
        $updates = $this->_updates();
        if (!array_key_exists($version, $updates)) {
            throw new RuntimeException("Version $version is not defined.");
        }
        // Updates all of the tables in this version
        foreach ($updates[$version] as $table => $update) {
            $extra = !empty($update['extra']) ? $update['extra'] : array();
            try {
                $this->_updateCallback('before', $table, $update);
                if (isset($update['sql'])) {
                    $sql = is_array($update['sql']) ? implode(';', $update['sql']) : $update['sql'];
                    $this->run($this->_replaceName($table, $sql, $extra));
                }
                $this->_updateCallback('after', $table, $update);
            } catch (Exception $e) {
                Mage::helper($this->_module())->writeError("Failed to update $table to $version: {$e->getMessage()}");
            }
        }
    }
}
