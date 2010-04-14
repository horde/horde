<?php
/**
 * Binder for IMP_Compose::.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Injector_Binder_Compose implements Horde_Injector_Binder
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        return new IMP_Injector_Factory_Compose($injector);
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
