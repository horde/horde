<?php
/**
 * Binder for IMP_Search::.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package IMP
 */
class IMP_Injector_Binder_Search implements Horde_Injector_Binder
{
    /**
     */
    public function create(Horde_Injector $injector)
    {
        return new IMP_Search(array(
            'id' => (isset($_SESSION['imp']) && IMP_Search::isSearchMbox(IMP::$mailbox)) ? IMP::$mailbox : null
        ));
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }

}
