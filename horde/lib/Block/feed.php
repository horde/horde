<?php

$block_name = _("Syndicated Feed");

/**
 * @package Horde_Block
 */
class Horde_Block_Horde_feed extends Horde_Block
{
    protected $_app = 'horde';

    private $_feed = null;

    protected function _params()
    {
        return array('uri' => array('type' => 'text',
                                    'name' => _("Feed Address")),
                     'limit' => array('name' => _("Number of articles to display"),
                                      'type' => 'int',
                                      'default' => 10),
                     'interval' => array('name' => _("How many seconds before we check for new articles?"),
                                         'type' => 'int',
                                         'default' => 86400),
                     'details' => array('name' => _("Show extra detail?"),
                                        'type' => 'boolean',
                                        'default' => 20));
    }

    /**
     * The title to go in this block.
     *
     * @return string   The title text.
     */
    protected function _title()
    {
        $this->_read();
        if (is_a($this->_feed, 'Horde_Feed_Base')) {
            return $this->_feed->title();
        }

        return _("Feed");
    }

    /**
     * The content to go in this block.
     *
     * @return string   The content
     */
    protected function _content()
    {
        $this->_read();
        if (is_a($this->_feed, 'Horde_Feed_Base')) {
            $html = '';
            $count = 0;
            foreach ($this->_feed as $entry) {
                if ($count++ > $this->_params['limit']) {
                    break;
                }
                $html .= '<a href="' . $entry->link. '"';
                if (empty($this->_params['details'])) {
                    $html .= ' title="' . htmlspecialchars(strip_tags($entry->description())) . '"';
                }
                $html .= '>' . htmlspecialchars($entry->title) . '</a>';
                if (!empty($this->_params['details'])) {
                    $html .= '<br />' .  htmlspecialchars(strip_tags($entry->description())). "<br />\n";
                }
                $html .= '<br />';
            }
            return $html;
        } elseif (is_string($this->_feed)) {
            return $this->_feed;
        } else {
            return '';
        }
    }

    private function _read()
    {
        if (empty($this->_params['uri'])) {
            return;
        }

        $key = md5($this->_params['uri']);
        $cache = $GLOBALS['injector']->getInstance('Horde_Cache');
        $feed = $cache->get($key, $this->_params['interval']);
        if (!empty($feed)) {
            $this->_feed = unserialize($feed);
        }

        try {
            $client = $GLOBALS['injector']
              ->getInstance('Horde_Http_Client')
              ->getClient();
            $feed = Horde_Feed::readUri($this->_params['uri'], $client);
            $cache->set($key, serialize($feed));
            $this->_feed = $feed;
        } catch (Exception $e) {
            $this->_feed = $e->getMessage();
        }
    }

}
