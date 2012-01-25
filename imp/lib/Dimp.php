<?php
/**
 * DIMP Base Class - provides dynamic view functions.
 *
 * Copyright 2005-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Dimp
{
    /**
     * Output a dimp-style action (menubar) link.
     *
     * @param array $params  A list of parameters:
     *   - app: (string) The application to load the icon from.
     *   - class: (string) The CSS classname to use for the link.
     *   - icon: (string) The icon CSS classname.
     *   - id: (string) The DOM ID of the link.
     *   - title: (string) The title string.
     *
     * @return string  An HTML link to $url.
     */
    static public function actionButton($params = array())
    {
        return Horde::link(
            '',
            '',
            empty($params['class']) ? '' : $params['class'],
            '',
            '',
            '',
            Horde::getAccessKey($params['title']),
           empty($params['id']) ? array() : array('id' => $params['id']),
           true
       ) . (empty($params['icon'])
            ? ''
            : '<span class="iconImg dimpaction' . $params['icon'] . '"></span>').
           $params['title'] . '</a>';
    }

    /**
     * Initialize the JS browser environment and output everything up to, and
     * including, the <body> tag.
     *
     * @param string $page    The dimp page.
     * @param string $title   The title of the page.
     * @param array $scripts  Any additional scripts that need to be loaded.
     *                        Each entry contains the three elements necessary
     *                        for a Horde::addScriptFile() call.
     */
    static public function init($page, $title, $scripts = array())
    {
        $GLOBALS['injector']->getInstance('Horde_Core_Ajax')->init(array(
            'app' => 'imp',
            'growler_log' => ($page == 'main')
        ));

        // Need to include script files before we start output
        $core_scripts = array(
            array('dimpcore.js', 'imp'),
            array('indices.js', 'imp')
        );
        foreach (array_merge($core_scripts, $scripts) as $val) {
            Horde::addScriptFile($val[0], $val[1]);
        }

        $page_title = $GLOBALS['registry']->get('name');
        if (!empty($title)) {
            $page_title .= ' :: ' . $title;
        }

        include IMP_BASE . '/templates/common-header.inc';

        // Send what we have currently output so the browser can start
        // loading CSS/JS. See:
        // http://developer.yahoo.com/performance/rules.html#flush
        echo Horde::endBuffer();
        flush();
    }

    /**
     * Build data structure needed by DimpCore javascript to display message
     * log information.
     *
     * @var string $msg_id  The Message-ID header of the message.
     *
     * @return array  An array of information that can be parsed by
     *                DimpCore.updateInfoList().
     */
    static public function getMsgLogInfo($msg_id)
    {
        $ret = array();

        foreach (IMP_Maillog::parseLog($msg_id) as $val) {
            $ret[] = array_map('htmlspecialchars', array(
                'm' => $val['msg'],
                't' => $val['action']
            ));
        }

        return $ret;
    }

}
