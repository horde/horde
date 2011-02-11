<?php
/**
 * Defines the AJAX interface for Horde.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Horde
 */
class Horde_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * AJAX action: Update sidebar.
     *
     * @return object  See Horde_Tree_Javascript#renderNodeDefinitions().
     */
    public function sidebarUpdate()
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Sidebar')->getTree()->renderNodeDefinitions();
    }

    /**
     * AJAX action: Auto-update portal block.
     */
    public function blockAutoUpdate()
    {
        if (isset($this->_vars->blockid)) {
            try {
                return $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_BlockCollection')
                    ->create()
                    ->getBlock('horde', $this->_vars->blockid)
                    ->getContent();
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return '';
    }
    /**
     * AJAX action: Update portal block.
     */
    public function blockUpdate()
    {
        if (isset($this->_vars->blockid)) {
            try {
                return $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_BlockCollection')
                    ->create()
                    ->getBlock('horde', $this->_vars->blockid)
                    ->getAjaxUpdate($this->_vars);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return '';
    }

}
