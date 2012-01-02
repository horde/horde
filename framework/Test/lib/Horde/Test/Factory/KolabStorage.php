<?php
/**
 * Generates a Kolab storage handler.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */

/**
 * Generates a Kolab storage handler.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @since Horde_Test 1.2.0
 *
 * @category Horde
 * @package  Test
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL
 * @link     http://www.horde.org/components/Horde_Test
 */
class Horde_Test_Factory_KolabStorage
{
    /**
     * Create a SQL next generate share setup.
     *
     * @params array $params Additional options.
     * <pre>
     * 'user' - (string) The current user.
     * 'imapuser' - (string) The short IMAP ID of the user.
     * </pre>
     *
     * @return Horde_Share_Sqlng The share setup.
     */
    public function create($params)
    {
        if (!class_exists('Horde_Kolab_Storage_Factory')) {
            throw new Horde_Test_Exception('The "Horde_Kolab_Storage_Factory" class is unavailable!');
        }
        $kolab_factory = new Horde_Kolab_Storage_Factory(
            array(
                'driver' => 'mock',
                'queryset' => array('list' => array('queryset' => 'horde')),
                'params' => array(
                    'username' => $params['user'],
                    'host' => 'localhost',
                    'port' => 143,
                    'data' => array(
                        'user/' . $params['imapuser'] => array(
                            'permissions' => array('anyone' => 'alrid')
                        )
                    )
                )
            )
        );
        return $kolab_factory->create();
    }
}