<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */

/**
 * Ingo_Transport_Sql implements an Ingo transport driver using a SQL database.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Ingo
 */
class Ingo_Transport_Sql extends Ingo_Transport_Base
{
    /**
     * Database handle.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  A hash containing driver parameters.
     */
    public function __construct(array $params = array())
    {
        $this->_supportShares = true;
        parent::__construct($params);
    }

    /**
     * Sets a script running on the backend.
     *
     * @param array $script  The filter script information. Passed elements:
     *                       - 'name': (string) the script name.
     *                       - 'recipes': (array) the filter recipe objects.
     *                       - 'script': (string) the filter script.
     *
     * @throws Ingo_Exception
     */
    public function setScriptActive($script)
    {
        $this->_connect();

        try {
            foreach ($script['recipes'] as $recipe) {
                $this->_db->execute($recipe['object']->generate());
            }
        } catch (Horde_Db_Exception $e) {
            throw new Ingo_Exception($e);
        }
    }

    /**
     * Quotes user input if supported by the transport driver.
     *
     * @param string $string  A string to quote.
     *
     * @return string  The quoted string.
     */
    public function quote($string)
    {
        $this->_connect();
        return $this->_db->quote($string);
    }

    /**
     * Connect to the SQL server.
     *
     * @throws Ingo_Exception
     */
    protected function _connect()
    {
        if ($this->_db) {
            return;
        }

        try {
            $this->_db = $GLOBALS['injector']
                ->getInstance('Horde_Core_Factory_Db')
                ->create('ingo', $this->_params);
        } catch (Horde_Exception $e) {
            throw new Ingo_Exception($e);
        }
    }
}
