<?php
/**
 * Preference storage implementation for an IMSP server.
 *
 * Copyright 2004-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @package  Prefs
 */
class Horde_Prefs_Storage_Imsp extends Horde_Prefs_Storage_Base
{
    /**
     * Handle for the IMSP server connection.
     *
     * @var Horde_Imsp_Options
     */
    protected $_imsp;

    public function __construct($user, array $params = array())
    {
        if (empty($params['imsp'])) {
            throw new InvalidArguementException('Missing required imsp parameter.');
        }
        $this->_imsp = $params['imsp'];
        parent::__construct($user, $params);
    }

    /**
     */
    public function get($scope_ob)
    {
        try {
            $prefs = $this->_imsp->get($scope_ob->scope . '.*');
        } catch (Horde_Imsp_Exception $e) {
            throw new Horde_Prefs_Exception($e);
        }

        foreach ($prefs as $name => $val) {
            $name = str_replace($scope_ob->scope . '.', '', $name);
            if ($val != '-') {
                $scope_ob->set($name, $val);
            }
        }

        return $scope_ob;
    }

    /**
     */
    public function store($scope_ob)
    {
        /* Driver has no support for storing locked status. */
        foreach ($scope_ob->getDirty() as $name) {
            $value = $scope_ob->get($name);
            try {
                $this->_imsp->set($scope_ob->scope . '.' . $name, $value ? $value : '-');
            } catch (Horde_Imsp_Exception $e) {
                throw new Horde_Prefs_Exception($e);
            }
        }
    }

    /**
     */
    public function remove($scope = null, $pref = null)
    {
        // TODO
    }

    /* Helper functions. */

}
