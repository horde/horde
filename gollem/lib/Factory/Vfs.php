<?php
/**
 * A Horde_Injector based Horde_Vfs factory.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */

/**
 * A Horde_Injector based Horde_Vfs factory.
 *
 * Copyright 2011-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael J. Rubinsky <mrubinsk@horde.org>
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */
class Gollem_Factory_Vfs extends Horde_Core_Factory_Base
{
    /**
     * Instances.
     *
     * @var array
     */
    private $_instances = array();

    /**
     * Returns the VFS instance.
     *
     * @param string $backend  The backend to return.
     *
     * @return Horde_Vfs  The VFS object.
     */
    public function create($backend)
    {
        if (empty($this->_instances[$backend])) {
            $be_config = Gollem_Auth::getBackend($backend);
            $params = $be_config['params'];

            if (!empty($params['password'])) {
                $secret = $GLOBALS['injector']->getInstance('Horde_Secret');
                $params['password'] = $secret->read($secret->getKey(), $params['password']);
            }

            switch (Horde_String::lower($be_config['driver'])) {
            case 'sql':
            case 'sqlfile':
            case 'musql':
                $db_params = $params;
                unset($db_params['table']);
                $params['db'] = $this->_injector
                    ->getInstance('Horde_Core_Factory_Db')
                    ->create('gollem', $db_params);
                $params['user'] = $GLOBALS['registry']->getAuth();
                break;
            }

            $vfs = Horde_Vfs::factory($be_config['driver'], $params);

            if (!empty($be_config['quota'])) {
                $vfs->setQuotaRoot($be_config['root'] == '/' ? '' : $be_config['root']);
                if (isset($be_config['quota_val'])) {
                    $vfs->setQuota($be_config['quota_val'], $be_config['quota_metric']);
                } else {
                    $quota_metric = array(
                        'B' => Horde_Vfs::QUOTA_METRIC_BYTE,
                        'KB' => Horde_Vfs::QUOTA_METRIC_KB,
                        'MB' => Horde_Vfs::QUOTA_METRIC_MB,
                        'GB' => Horde_Vfs::QUOTA_METRIC_GB
                    );
                    $quota_str = explode(' ', $be_config['quota'], 2);
                    if (is_numeric($quota_str[0])) {
                        $metric = trim(Horde_String::upper($quota_str[1]));
                        if (!isset($quota_metric[$metric])) {
                            $metric = 'B';
                        }
                        $vfs->setQuota($quota_str[0], $quota_metric[$metric]);
                    }
                }
            }

            $this->_instances[$backend] = $vfs;
        }

        return $this->_instances[$backend];
    }
}
