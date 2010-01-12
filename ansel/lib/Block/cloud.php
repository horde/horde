<?php

$block_name = _("Tag Cloud");

/**
 * Display Tag Cloud
 *
 * Copyright 2007-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_ansel_cloud extends Horde_Block {

    var $_app = 'ansel';

    function _params()
    {
        $params = array('count' => array(
                            'name' => _("Number of tags to display"),
                            'type' => 'int',
                            'default' => 20));
        return $params;
    }

    function _title()
    {
        return _("Tag Cloud");
    }

    function _content()
    {
        require_once dirname(__FILE__) . '/../base.php';

        global $registry;

        /* Get the tags */
        $tags = Ansel_Tags::listTagInfo(null, $this->_params['count']);
        if (count($tags)) {
            $cloud = new Horde_Ui_TagCloud();
            foreach ($tags as $id => $tag) {
                $link = Ansel::getUrlFor('view', array('view' => 'Results',
                                                       'tag' => $tag['tag_name']));
                $cloud->addElement($tag['tag_name'], $link, $tag['total']);
            }
            $html = $cloud->buildHTML();
        } else {
            $html = '';
        }
        return $html;
    }

}
