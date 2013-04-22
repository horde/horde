<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mongo
 */

/**
 * Extend the base PECL MongoClient class by allowing it to be serialized.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Mongo
 */
class Horde_Mongo_Client extends MongoClient implements Serializable
{
    /**
     * Constructor args.
     *
     * @var array
     */
    private $_cArgs;

    /**
     * @see MongoClient#__construct
     */
    public function __construct($server = null, array $options = array())
    {
        $this->_cArgs = array($server, $options);
        parent::__construct($server, $options);
    }

    /**
     */
    public function serialize()
    {
        $this->close();
        return serialize($this->_cArgs);
    }

    /**
     */
    public function unserialize($data)
    {
        $this->_cArgs = unserialize($data);
        parent::__construct($this->_cArgs[0], $this->_cArgs[1]);
    }

}
