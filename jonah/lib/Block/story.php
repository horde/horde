<?php

$block_name = _("Story");

/**
 * This class extends Horde_Block:: to provide the api to embed news
 * in other Horde applications.
 *
 * Copyright 2002-2007 Roel Gloudemans <roel@gloudemans.info>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Roel Gloudemans <roel@gloudemans.info>
 * @package Horde_Block
 */
class Horde_Block_Jonah_story extends Horde_Block {

    var $_app = 'jonah';

    var $_story = null;

    /**
     */
    function _params()
    {
        $channels = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannels();
        $channel_choices = array();
        foreach ($channels as $channel) {
            $channel_choices[$channel['channel_id']] = $channel['channel_name'];
        }
        natcasesort($channel_choices);

        return array('source' => array(
                         'name' => _("Feed"),
                         'type' => 'enum',
                         'values' => $channel_choices),
                     'story' => array(
                         'name' => _("Story"),
                         'type' => 'int'),
                     'countReads' => array(
                         'name' => _("Count reads of this story when this block is displayed"),
                         'type' => 'boolean',
                         'default' => false),
        );
    }

    /**
     */
    function _title()
    {
        if (empty($this->_params['source']) ||
            empty($this->_params['story'])) {
            return _("Story");
        }

        try {
            $story = $this->_fetch();
        } catch (Jonah_Exception $e) {
            return htmlspecialchars($e->getMessage(), ENT_COMPAT, $GLOBALS['registry']->getCharset());
        }
        return '<span class="storyDate">'
                . htmlspecialchars($story['updated_date'], ENT_COMPAT,$GLOBALS['registry']->getCharset())
                . '</span> '
                . htmlspecialchars($story['title'], ENT_COMPAT, $GLOBALS['registry']->getCharset());
    }

    /**
     */
    function _content()
    {
        if (empty($this->_params['source']) || empty($this->_params['story'])) {
            return _("No story is selected.");
        }

        try {
            $story = $this->_fetch();
        } catch (Jonah_Exception $e) {
            return sprintf(_("Error fetching story: %s"), $e->getMessage());
        }

        if (empty($story['body_type']) || $story['body_type'] == 'text') {
            $story['body'] =  $GLOBALS['injector']->getInstance('Horde_Text_Filter')->filter($story['body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        }

        $tag_html = array();
        foreach ($story['tags'] as $id => $tag) {
            $tag_html[] = Horde::url('results.php')->add(array('tag_id' => $id, 'channel_id' => $this->_prams['source']))->link() . $tag . '</a>';
        }

        return '<p class="storyTags">' . _("Tags: ")
            . implode(', ', $story['tags'])
            . '</p><p class="storySubtitle">'
            . htmlspecialchars($story['desc'])
            . '</p><div class="storyBody">' . $story['body']
            . '</div>';
    }

    /**
     * Get the story the block is configured for.
     */
    function _fetch()
    {
        if (is_null($this->_story)) {
            $this->_story = $GLOBALS['injector']->getInstance('Jonah_Driver')->getStory(
                    $this->_params['source'],
                    $this->_params['story'],
                    !empty($this->_params['countReads']));
        }

        return $this->_story;
    }

}
