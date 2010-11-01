<?php
/**
 * Provides functions required by several Kolab_Server tests.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Prepare the test setup.
 */
require_once dirname(__FILE__) . '/Autoload.php';

require_once dirname(__FILE__) . '/Constraints/Restrictkolabusers.php';
require_once dirname(__FILE__) . '/Constraints/Restrictgroups.php';
require_once dirname(__FILE__) . '/Constraints/Searchuid.php';
require_once dirname(__FILE__) . '/Constraints/Searchmail.php';
require_once dirname(__FILE__) . '/Constraints/Searchcn.php';
require_once dirname(__FILE__) . '/Constraints/Searchalias.php';

/**
 * Provides functions required by several Kolab_Server tests.
 *
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_TestCase extends PHPUnit_Framework_TestCase
{
    protected function getComposite()
    {
        return $this->getMock(
            'Horde_Kolab_Server_Composite', array(), array(), '', false, false
        );
    }

    protected function getMockedComposite()
    {
        return new Horde_Kolab_Server_Composite(
            $this->getMock('Horde_Kolab_Server_Interface'),
            $this->getMock('Horde_Kolab_Server_Objects_Interface'),
            $this->getMock('Horde_Kolab_Server_Structure_Interface'),
            $this->getMock('Horde_Kolab_Server_Search_Interface'),
            $this->getMock('Horde_Kolab_Server_Schema_Interface')
        );
    }

    public function isRestrictedToGroups()
    {
        return new Horde_Kolab_Server_Constraint_Restrictgroups();
    }

    public function isRestrictedToKolabUsers()
    {
        return new Horde_Kolab_Server_Constraint_Restrictedkolabusers();
    }

    public function isSearchingByUid()
    {
        return new Horde_Kolab_Server_Constraint_Searchuid();
    }

    public function isSearchingByMail()
    {
        return new Horde_Kolab_Server_Constraint_Searchmail();
    }

    public function isSearchingByCn()
    {
        return new Horde_Kolab_Server_Constraint_Searchcn();
    }

    public function isSearchingByAlias()
    {
        return new Horde_Kolab_Server_Constraint_Searchcn();
    }
}