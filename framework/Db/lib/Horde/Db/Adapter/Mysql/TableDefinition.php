<?php
/**
 * Copyright 2007 Maintainable Software, LLC
 * Copyright 2008 The Horde Project (http://www.horde.org/)
 *
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */

/**
 * @author     Mike Naberezny <mike@maintainable.com>
 * @author     Derek DeVries <derek@maintainable.com>
 * @author     Chuck Hagenbuch <chuck@horde.org>
 * @license    http://opensource.org/licenses/bsd-license.php
 * @category   Horde
 * @package    Horde_Db
 * @subpackage Adapter
 */
class Horde_Db_Adapter_Mysql_TableDefinition extends Horde_Db_Adapter_Abstract_TableDefinition
{
    /**
     * @param   string  $name
     * @param   array   $options
     */
    public function end()
    {
        if (empty($this->_options['temporary'])) {
            $this->_options['options'] = 'ENGINE=InnoDB DEFAULT CHARSET=' . $this->_base->getCharset();
        }
        return parent::end();
    }

}
