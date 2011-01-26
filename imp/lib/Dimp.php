<?php
/**
 * DIMP Base Class - provides dynamic view functions.
 *
 * Copyright 2005-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
 */
class IMP_Dimp
{
    /* String used to separate indexes. */
    const IDX_SEP = "\0";

    /**
     * Output a dimp-style action (menubar) link.
     *
     * @param array $params  A list of parameters.
     * <pre>
     * 'app' - The application to load the icon from.
     * 'class' - The CSS classname to use for the link.
     * 'icon' - The icon CSS classname.
     * 'id' - The DOM ID of the link.
     * 'title' - The title string.
     * </pre>
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
     * Output everything up to but not including the <body> tag.
     *
     * @param string $title   The title of the page.
     * @param array $scripts  Any additional scripts that need to be loaded.
     *                        Each entry contains the three elements necessary
     *                        for a Horde::addScriptFile() call.
     */
    static public function header($title, $scripts = array())
    {
        // Need to include script files before we start output
        $core_scripts = array(
            array('effects.js', 'horde'),
            array('horde.js', 'horde'),
            array('dimpcore.js', 'imp'),
            array('growler.js', 'horde')
        );
        foreach (array_merge($core_scripts, $scripts) as $val) {
            call_user_func_array(array('Horde', 'addScriptFile'), $val);
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
     * Return information about the current attachments for a message
     *
     * @param IMP_Compose $imp_compose  An IMP_Compose object.
     *
     * @return array  An array of arrays with the following keys:
     * <pre>
     * 'num' - The current attachment number
     * 'name' - The HTML encoded attachment name
     * 'type' - The MIME type of the attachment
     * 'size' - The size of the attachment in KB (string)
     * </pre>
     */
    static public function getAttachmentInfo($imp_compose)
    {
        $fwd_list = array();

        if (count($imp_compose)) {
            foreach ($imp_compose as $atc_num => $data) {
                $mime = $data['part'];

                $fwd_list[] = array(
                    'name' => htmlspecialchars($mime->getName(true)),
                    'num' => $atc_num,
                    'type' => $mime->getType(),
                    'size' => $mime->getSize()
                );
            }
        }

        return $fwd_list;
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

    /**
     * Return to main dimp mailbox page from within IFRAME.
     *
     * @var string $mailbox  The mailbox to load.
     */
    static public function returnToDimp($mailbox = '')
    {
        print '<html><head>' .
            Horde::wrapInlineScript(array('window.parent.DimpBase.go(\'mbox\', \'' . Horde_Serialize::serialize(strval($mailbox), Horde_Serialize::JSON, 'UTF-8') . '\')')) .
            '</head></html>';
        exit;
    }

}
