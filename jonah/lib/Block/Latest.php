<?php
/**
 * Provide the api to embed the lates news story in other Horde applications.
 *
 * Copyright 2002-2010 Roel Gloudemans <roel@gloudemans.info>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author  Roel Gloudemans <roel@gloudemans.info>
 * @package Jonah
 */
class Jonah_Block_Latest extends Horde_Block
{
    /**
     */
    protected $_story = null;

    /**
     */
    public function getName()
    {
        return _("Latest News");
    }

    /**
     */
    protected function _params()
    {
        $params['source'] = array('name' => _("News Source"),
                                  'type' => 'enum',
                                  'values' => array());

        $channels = $GLOBALS['injector']->getInstance('Jonah_Driver')->getChannels();
        foreach ($channels as $channel) {
            $params['source']['values'][$channel['channel_id']] = $channel['channel_name'];
        }
        natcasesort($params['source']['values']);

        // Get first news source.
        $channel = reset($channels);
        $params['source']['default'] = $channel['channel_id'];

        $params['countReads'] = array(
            'name' => _("Count reads of the latest story when this block is displayed"),
            'type' => 'boolean',
            'default' => false);

        return $params;
    }

    /**
     */
    protected function _title()
    {
        if (empty($this->_params['source'])) {
            return $this->getName();
        }

        try {
            $story = $this->_fetch();
        } catch (Exception $e) {
            return htmlspecialchars($e->getMessage());
        }

        return '<span class="storyDate">'
               . htmlspecialchars($story['updated_date'])
               . '</span> '
               . htmlspecialchars($story['title']);
    }

    /**
     */
    protected function _content()
    {
        if (empty($this->_params['source'])) {
            return _("No channel specified.");
        }

        try {
            $story = $this->_fetch();
        } catch (Exception $e) {
            return sprintf(_("Error fetching story: %s"), $e->getMessage());
        }

        if (empty($story['body_type']) || $story['body_type'] == 'text') {
            $story['body'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($story['body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        }

        return '<p class="storySubtitle">' . htmlspecialchars($story['description']) . '</p><div class="storyBody">' . $story['body'] . '</div>';
    }

    /**
     * Get the latest story.
     */
    private function _fetch()
    {
        if (empty($this->_params['source'])) {
            return;
        }

        if (is_null($this->_story)) {
            $driver = $GLOBALS['injector']->getInstance('Jonah_Driver');
            $this->_story = $driver->getStory($this->_params['source'],
                                            $driver->getLatestStoryId($this->_params['source']),
                                            !empty($this->_params['countReads']));
        }

        return $this->_story;
    }

}
