<?php
/**
 * Horde_ActiveSync_Interface_LoggerFactory::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * Horde_ActiveSync_Interface_LoggerFactory:: Defines an interface for a factory
 * object that knows how to provide an appropriate Horde_Log_Logger object.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *            NOTE: According to sec. 8 of the GENERAL PUBLIC LICENSE (GPL),
 *            Version 2, the distribution of the Horde_ActiveSync module in or
 *            to the United States of America is excluded from the scope of this
 *            license.
 * @copyright 2012 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
interface Horde_ActiveSync_Interface_LoggerFactory
 {

    /**
     * Factory for a log object. Attempts to create a device specific file if
     * custom logging is requested.
     *
     * @param array $properties  The property array.
     *
     * @return Horde_Log_Logger  The logger object, correctly configured.
     */
    public function create($properties = array());
 }