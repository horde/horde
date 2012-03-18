<?php
/**
 * Handling distribution lists.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Require our basic test case definition
 */
require_once __DIR__ . '/Scenario.php';

/**
 * Handling distribution lists.
 *
 * Copyright 2008-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Integration_DistListHandlingTest extends Horde_Kolab_Server_Integration_Scenario
{

    /**
     * Test adding a distribution list.
     *
     * @scenario
     *
     * @return NULL
     */
    public function creatingDistributionList()
    {
        $this->given('several Kolab servers')
            ->when('adding a distribution list')
            ->then(
                'the result should be an object of type',
                'Horde_Kolab_Server_Object_Kolab_Distlist'
            );
    }

}
