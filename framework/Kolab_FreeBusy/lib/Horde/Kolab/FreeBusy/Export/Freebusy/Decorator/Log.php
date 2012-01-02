<?php
/**
 * Logs exporting free/busy data.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */

/**
 * Logs exporting free/busy data.
 *
 * Copyright 2009-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If
 * you did not receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_FreeBusy
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_FreeBusy
 */
class Horde_Kolab_FreeBusy_Export_Freebusy_Decorator_Log
{
    /**
     * The decorated exporter.
     *
     * @var Horde_Kolab_FreeBusy_Export_Freebusy_Interface
     */
    private $_export;

    /**
     * The logger.
     *
     * @var mixed
     */
    private $_logger;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_FreeBusy_Export_Freebusy_Interface $export The decorated
     *                                                               export.
     * @param mixed                                          $logger The log handler. The
     *                                                               class must at least
     *                                                               provide the debug()
     *                                                               method.
     */
    public function __construct(
        Horde_Kolab_FreeBusy_Export_Freebusy_Interface $export,
        $logger
    ) {
        $this->_export = $export;
        $this->_logger = $logger;
    }

    public function export()
    {
        $this->_logger->debug(
            sprintf('Exporting free/busy data for resource %s from %s to %s',
                    $this->_export->getResourceName(),
                    $this->_export->getStart()->timestamp(),
                    $this->_export->getEnd()->timestamp()
            )
        );

        return $this->_export->export();
    }

}