<?php
/**
 * $Id: Activity.php 979 2008-10-08 08:31:13Z duck $
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author Duck <duck@obala.net>
 */
class Folks_Activity_Form extends Horde_Form {

    function __construct($vars, $title, $name)
    {
        parent::__construct($vars, $title, $name);

        if ($name == 'long') {
            $this->addVariable(_("Activity"), 'activity', 'longText', true, false, null, array(4));
        } else {
            $this->addVariable(_("Activity"), 'activity', 'text', true, false, null, array('', 80));
        }

        $this->setButtons(_("Post"));
    }

    function execute()
    {
        $message = trim(strip_tags($this->_vars->get('activity')));

        if (empty($message)) {
            return PEAR::raiseError(_("You cannot post an empty activity message."));
        }

        require_once 'Horde/Text/Filter.php';
        $filters = array('text2html', 'bbcode', 'highlightquotes', 'emoticons');
        $filters_params = array(array('parselevel' => TEXT_HTML_MICRO),
                                array(),
                                array(),
                                array());

        if (($hasBBcode = strpos($message, '[')) !== false &&
                strpos($message, '[/', $hasBBcode) !== false) {
            $filters_params[0]['parselevel'] = TEXT_HTML_NOHTML;
        }

        $message = Text_Filter::filter(trim($message), $filters, $filters_params);

        return $GLOBALS['folks_driver']->logActivity($message, 'folks:custom');
    }
}
