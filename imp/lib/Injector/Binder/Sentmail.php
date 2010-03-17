<?php
/**
 * Binder for IMP_Sentmail::.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Injector_Binder_Sentmail implements Horde_Injector_Binder
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        if (empty($GLOBALS['conf']['sentmail']['driver'])) {
            return IMP_Sentmail::factory();
        }

        $driver = $GLOBALS['conf']['sentmail']['driver'];
        return IMP_Sentmail::factory($driver, Horde::getDriverConfig('sentmail', $driver));
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
