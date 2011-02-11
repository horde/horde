<?php
/**
 */
class Horde_Block_Feed extends Horde_Block
{
    /**
     */
    private $_feed = null;

    /**
     */
    public function __construct($app, $params = array())
    {
        parent::__construct($app, $params);

        $this->enabled = class_exists('Horde_Feed');
    }

    /**
     */
    public function getName()
    {
        return _("Syndicated Feed");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'uri' => array(
                'type' => 'text',
                'name' => _("Feed Address")
            ),
            'limit' => array(
                'name' => _("Number of articles to display"),
                'type' => 'int',
                'default' => 10
            ),
            'interval' => array(
                'name' => _("How many seconds before we check for new articles?"),
                'type' => 'int',
                'default' => 86400
            ),
            'details' => array(
                'name' => _("Show extra detail?"),
                'type' => 'boolean',
                'default' => 20
            )
        );
    }

    /**
     */
    protected function _title()
    {
        $this->_read();

        return ($this->_feed instanceof Horde_Feed_Base)
            ? $this->_feed->title()
            : _("Feed");
    }

    /**
     */
    protected function _content()
    {
        $this->_read();

        if ($this->_feed instanceof Horde_Feed_Base) {
            $html = '';
            $count = 0;
            foreach ($this->_feed as $entry) {
                if (++$count > $this->_params['limit']) {
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
        }

        return is_string($this->_feed)
            ? $this->_feed
            : '';
    }

    /**
     */
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
              ->getInstance('Horde_Core_Factory_HttpClient')
              ->create();
            $feed = Horde_Feed::readUri($this->_params['uri'], $client);
            $cache->set($key, serialize($feed));
            $this->_feed = $feed;
        } catch (Exception $e) {
            $this->_feed = $e->getMessage();
        }
    }

}
