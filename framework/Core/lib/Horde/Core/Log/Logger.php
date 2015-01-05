<?php
/**
 * Copyright 2010-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Core
 */

/**
 * Output log entries as configured by current Horde framework settings.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2010-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL-2.1
 * @package   Core
 */
class Horde_Core_Log_Logger extends Horde_Log_Logger
{
    /**
     * Logs a message to the global Horde log backend.
     *
     * @deprecated  Use Horde_Core_Log_Logger#logObject() instead.
     *
     * @param mixed $event     See Horde_Core_Log_Object#__construct(). Can
     *                         also be a Horde_Core_Log_Object object.
     * @param mixed $priority  See Horde_Core_Log_Object#__construct().
     * @param array $options   See Horde_Core_Log_Object#__construct().
     */
    public function log($event, $priority = null, array $options = array())
    {
        if (!($event instanceof Horde_Core_Log_Object)) {
            $options['trace'] = isset($options['trace'])
                ? ($options['trace'] + 1)
                : 1;
            $event = new Horde_Core_Log_Object($event, $priority, $options);
        }

        $this->logObject($event);
    }

    /**
     * Logs an entry.
     *
     * @since 2.5.0
     *
     * @param Horde_Core_Log_Object $ob  Log entry object.
     */
    public function logObject(Horde_Core_Log_Object $ob)
    {
        if (!$ob->logged) {
            parent::log($ob->toArray());
            if ($bt = $ob->backtrace) {
                parent::log(strval($bt), Horde_Log::DEBUG);
            }
            $ob->logged = true;
        }
    }

}
