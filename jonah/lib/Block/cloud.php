<?php

$block_name = _("Tag Cloud");

/**
 * Display Tag Cloud
 *
 * Copyright 2007-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Rubinsky <mrubinsk@horde.org>
 * @package Horde_Block
 */
class Horde_Block_jonah_cloud extends Horde_Block {

    var $_app = 'jonah';

    /**
     */
    function _params()
    {
        return array();

    }

    function _title()
    {
        return _("Tag Cloud");
    }

    function _content()
    {
        /* Get the tags */
        $tags = $GLOBALS['injector']->getInstance('Jonah_Driver')->listTagInfo();
        if (count($tags)) {
            $url = Horde::url('stories/results.php');
            $cloud = new Horde_Ui_TagCloud();
            foreach ($tags as $id => $tag) {
                $cloud->addElement($tag['tag_name'], $url->copy()->add('tag_id', $id), $tag['total']);
            }
            $html = $cloud->buildHTML();
        } else {
            $html = '';
        }

        return $html;
    }

}
