<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL-2). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */

/**
 * Defines the AJAX actions used in Horde.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl LGPL-2
 * @package   Horde
 */
class Horde_Ajax_Application_Handler extends Horde_Core_Ajax_Application_Handler
{
    /**
     * AJAX action: Update topbar.
     *
     * @return Horde_Core_Ajax_Response_HordeCore  Response object.
     */
    public function topbarUpdate()
    {
        global $injector, $registry;

        $registry->pushApp($this->vars->app);
        $topbar = $injector->getInstance('Horde_Core_Factory_Topbar')
            ->create('Horde_Tree_Renderer_Menu', array('nosession' => true));
        $hash = $topbar->getHash();
        $tree = $topbar->getTree();
        $registry->popApp();

        if ($this->vars->hash == $hash) {
            return false;
        }

        $node_defs = $tree->renderNodeDefinitions();
        $node_defs->hash = $hash;

        if (isset($node_defs->files)) {
            $jsfiles = $node_defs->files;
            unset($node_defs->files);
        } else {
            $jsfiles = array();
        }

        $ob = new Horde_Core_Ajax_Response_HordeCore($node_defs);
        $ob->jsfiles = $jsfiles;

        return $ob;
    }

    /**
     * AJAX action: Update sidebar.
     *
     * @return object  See Horde_Core_Tree_Renderer_Javascript#renderNodeDefinitions().
     */
    public function sidebarUpdate()
    {
        return $GLOBALS['injector']->getInstance('Horde_Core_Sidebar')->getTree()->renderNodeDefinitions();
    }

    /**
     * AJAX action: Auto-update portal block. To be called automatically by
     * the block, with no user selected options.
     *
     * @return string  The full HTML needed to render the block content.
     */
    public function blockAutoUpdate()
    {
        $html = '';

        if (isset($this->vars->app) && isset($this->vars->blockid)) {
            try {
                $block = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_BlockCollection')
                    ->create()
                    ->getBlock($this->vars->app, $this->vars->blockid);
                if (!empty($block->autoUpdateMethod) && is_callable(array($block, $block->autoUpdateMethod))) {
                    $html = call_user_func_array(array($block, $block->autoUpdateMethod), isset($this->vars->options) ? array($this->vars->options) : array());
                } else {
                    $html = $block->getContent(isset($this->vars->options) ? $this->vars->options : null);
                }
            } catch (Exception $e) {
                $html = $e->getMessage();
            }
        }

        return $html;
    }

    /**
     * AJAX action: Refresh portal block. Manually refresh the block content,
     * may $this->vars may contain user selected/provided values.
     *
     * @return string  The full HTML needed to render the block content.
     */
    public function blockRefresh()
    {
        $html = '';
        if (!isset($this->vars->app)) {
            $this->vars->set('app', 'horde');
        }
        if (isset($this->vars->blockid)) {
            try {
                $html = $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_BlockCollection')
                    ->create()
                    ->getBlock($this->vars->app, $this->vars->blockid)
                    ->refreshContent($this->vars);
            } catch (Exception $e) {
                $html = $e->getMessage();
            }
        }
        return $html;
    }

    /**
     * AJAX action: Update portal block data. To be used when the block can
     * refresh using only JSON data. I.e., this data would need to be parsed
     * by the block code to be rendered.
     *
     * @return Horde_Core_Response
     */
    public function blockUpdate()
    {
        if (isset($this->vars->blockid)) {
            try {
                return $GLOBALS['injector']
                    ->getInstance('Horde_Core_Factory_BlockCollection')
                    ->create()
                    ->getBlock($this->vars->app, $this->vars->blockid)
                    ->getAjaxUpdate($this->vars);
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return '';
    }

}
