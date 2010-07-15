<?php
/**
 * The main entry point for the Kolab_Filter application.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */

/** Setup default autoloading */
require_once 'Horde/Autoloader/Default.php';

/**
 * The main entry point for the Kolab_Filter application.
 *
 * Copyright 2010 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Filter
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Filter
 */
class Horde_Kolab_Filter
{
    public function __construct(Horde_Injector $injector = null)
    {
        if ($injector === null) {
            $this->injector = new Horde_Injector(new Horde_Injector_TopLevel());

            $this->injector->bindFactory(
                'Horde_Log_Logger', 'Horde_Kolab_Filter_Factory', 'getLogger'
            );
            $this->injector->bindImplementation(
                'Horde_Kolab_Filter_Temporary', 'Horde_Kolab_Filter_Temporary_File'
            );
        } else {
            $this->injector = $injector;
        }
    }

    /**
     * Run the mail filter.
     *
     * @param string $type The type of filtering to run (Incoming|Content).
     */
    public function main($type, $inh = STDIN, $transport = null)
    {
        /** Setup all configuration information */
        /* $configuration = $this->injector->getInstance('Horde_Kolab_Filter_Configuration'); */
        /* $configuration->init(); */

        /** Now run the filter */
        $filter = $this->injector->getInstance('Horde_Kolab_Filter_' . $type);
        $filter->init();
        $filter->parse($inh, $transport);
    }
}