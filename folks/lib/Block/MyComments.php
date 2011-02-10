<?php
/**
 * Horde_Block_folks_my_comments:: Implementation of the Horde_Block API to
 * display last comments on users videos.
 */
class Folks_Block_MyComments extends Horde_Block
{
    /**
     */
    public function getName()
    {
        return _("Last comments on my profile");
    }

    /**
     */
    protected function _params()
    {
        return array(
            'limit' => array(
                'name' => _("Number of comments to display"),
                'type' => 'int',
                'default' => 10
            )
        );
    }

    /**
     */
    protected function _title()
    {
        return $this->getName();
    }

    /**
     */
    protected function _content()
    {
        if (!$GLOBALS['registry']->isAuthenticated()) {
            return '';
        }

        $GLOBALS['cache'] = $GLOBALS['injector']->getInstance('Horde_Cache');

        $cache_key = 'folks_myscommetns_' . $this->_params['limit'];
        $threads = $GLOBALS['cache']->get($cache_key, $GLOBALS['conf']['cache']['default_lifetime']);
        if ($threads) {
            return $threads;
        }

        Horde::addScriptFile('tables.js', 'horde');
        $html = '<table class="sortable striped" id="my_comment_list" style="width: 100%">'
              . '<thead><tr><th>' . _("Title") . '</th>'
              . '<th>' . _("User") . '</th></tr></thead>';

        try {
            $threads = $GLOBALS['registry']->call('forums/getThreadsByForumOwner',
                                                  array($GLOBALS['registry']->getAuth(), 'message_timestamp', 1, false,
                                                  'folks', 0, $this->_params['limit']));
        } catch (Horde_Exception $e) {
            return $e->getMessage();
        }

        $url = Folks::getUrlFor('user', $GLOBALS['registry']->getAuth());
        foreach ($threads as $message) {
            $html .= '<tr><td>'
                  . '<a href="' . $url . '" title="' . $message['message_date']. '">'
                  . $message['message_subject'] . '</a> '
                  . '</td><td>'
                  . $message['message_author'] . '</td></tr>';
        }
        $html .= '</table>';

        $GLOBALS['cache']->set($cache_key, $html);
        return $html;
    }
}
