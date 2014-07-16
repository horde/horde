<?php
/**
 * Copyright 2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * Query the capabilities of a server.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 * @since     2.24.0
 */
class Horde_Imap_Client_Data_Capability
{
    /**
     * Capability data.
     *
     * @var array
     */
    protected $_data = array();

    /**
     * Add a capability (and optional parameters).
     *
     * @param string $capability  The capability to add.
     * @param mixed $params       A parameter (or array of parameters) to add.
     */
    public function add($capability, $params = null)
    {
        $capability = strtoupper($capability);

        if (is_null($params)) {
            if (isset($this->_data[$capability])) {
                return;
            }
            $params = true;
        } else {
            if (!is_array($params)) {
                $params = array($params);
            }
            $params = array_map('strtoupper', $params);

            if (isset($this->_data[$capability]) &&
                is_array($this->_data[$capability])) {
                $params = array_merge($this->_data[$capability], $params);
            }
        }

        $this->_data[$capability] = $params;
    }

    /**
     * Remove a capability.
     *
     * @param string $capability  The capability to remove.
     */
    public function remove($capability)
    {
        unset($this->_data[strtoupper($capability)]);
    }

    /**
     * Returns whether the server supports the given capability.
     *
     * @param string $capability  The capability string to query.
     * @param string $parameter   If set, require the parameter to exist.
     *
     * @return boolean  True if the capability (and parameter) exist.
     */
    public function query($capability, $parameter = null)
    {
        $capability = strtoupper($capability);

        if (!isset($this->_data[$capability])) {
            return false;
        }

        return (is_null($parameter) || !is_array($this->_data[$capability]))
            ? true
            : in_array(strtoupper($parameter), $this->_data[$capability]);
    }

    /**
     * Return the list of parameters for an extension.
     *
     * @param string $capability  The capability string to query.
     *
     * @return array  An array of parameters if the extension exists and
     *                supports parameters.  Otherwise, an empty array.
     */
    public function getParams($capability)
    {
        return ($this->query($capability) && is_array($out = $this->_data[strtoupper($capability)]))
            ? $out
            : array();
    }

    /**
     * Returns the raw data.
     *
     * @deprecated
     *
     * @return array  Capability data.
     */
    public function toArray()
    {
        return $this->_data;
    }

}
