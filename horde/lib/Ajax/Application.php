<?php
/**
 * Defines the AJAX interface for Horde.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package  Horde
 */
class Horde_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     */
    protected function _init()
    {
        // Needed because Core contains Imples
        $this->addHelper(new Horde_Core_Ajax_Application_Helper_Imple());
    }

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
        $html = '';

        if (isset($this->_vars->app) && isset($this->_vars->blockid)) {
            try {
                $html = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_BlockCollection')
                    ->create()
                    ->getBlock($this->_vars->app, $this->_vars->blockid)
                    ->getContent(isset($this->_vars->options) ? $this->_vars->options : null);
            } catch (Exception $e) {
                $html = $e->getMessage();
            }
        }

        return new Horde_Core_Ajax_Response_Raw($html, 'text/html');
    }

    /**
     * AJAX action: Refresh portal block.
     */
    public function blockRefresh()
    {
        $html = '';
        if (!isset($this->_vars->app)) {
            $this->_vars->set('app', 'horde');
        }
        if (isset($this->_vars->blockid)) {
            try {
                $html = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_BlockCollection')
                    ->create()
                    ->getBlock($this->_vars->app, $this->_vars->blockid)
                    ->refreshContent($this->_vars);
            } catch (Exception $e) {
                $html = $e->getMessage();
            }
        }

        return new Horde_Core_Ajax_Response_Raw($html, 'text/html');
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
                    ->getBlock($this->_vars->blockid)
                    ->getAjaxUpdate($this->_vars);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return '';
    }

}
