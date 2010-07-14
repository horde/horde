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
        return array(
            'results_url' => array(
                'name' => _("Results URL"),
                'type' => 'text',
                'default' => Horde::applicationUrl('stories/results.php?tag_id=@id@')));
    }

    function _title()
    {
        return _("Tag Cloud");
    }

    function _content()
    {
        $news = Jonah_News::factory();

        /* Get the tags */
        $tags = $news->listTagInfo();
        if (count($tags)) {
            $cloud = new Horde_Core_Ui_TagCloud();
            foreach ($tags as $id => $tag) {
                $cloud->addElement($tag['tag_name'], str_replace(array('@id@', '@tag@'), array($id, $tag['tag_name']), $this->_params['results_url']), $tag['total']);
            }
            $html = $cloud->buildHTML();
        } else {
            $html = '';
        }
        return $html;
    }

}
