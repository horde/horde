<?php
/**
 * Ansel_Widget_links:: class to wrap the display of various feed links etc...
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Widget_Links extends Ansel_Widget_Base
{
    protected $_supported_views = array('Gallery', 'Image');

    public function __construct($params)
    {
        parent::__construct($params);
        $this->_title = _("Links");
    }

    public function html()
    {
        global $registry;

        $feedurl = Horde::url('rss.php', true);
        $owner = $this->_view->gallery->get('owner');
        $html = $this->_htmlBegin();
        $html .= Horde::link(Ansel::getUrlFor('rss_user', array('owner' => $owner))) . Horde::img('feed.png', '', '', $registry->getImageDir('horde')) . ' ' . sprintf(_("Recent photos by %s"), $owner) . '</a>';
        $slug = $this->_view->gallery->get('slug');
        $html .= '<br />' . Horde::link(Ansel::getUrlFor('rss_gallery', array('gallery' => $this->_view->gallery->id, 'slug' => $slug))) . ' ' .  Horde::img('feed.png', '', '', $registry->getImageDir('horde')) . ' ' . sprintf(_("Recent photos in %s"), htmlspecialchars($this->_view->gallery->get('name'), ENT_COMPAT, Horde_Nls::getCharset())) . '</a>';

        /* Embed html */
        if (empty($this->_view->_params['image_id'])) {
            /* Gallery view */
            $params = array('count' => 10);
            if (!empty($slug))  {
                $params['gallery_slug'] = $slug;
            } else {
                $params['gallery_id'] = $this->_view->gallery->id;
            }
        } else {
            // This is an image view
            $params = array('thumbsize' => 'screen',
                            'images' => $this->_view->_params['image_id'],
                            'count' => 10);

        }

        $embed = htmlentities(Ansel::embedCode($params));
        $html .= '<div class="embedInput">' . _("Embed: ") . '<br /><input type="text" readonly="readonly" value="' . $embed . '" /></div>';
        $html .= $this->_htmlEnd();

        return $html;
    }

}
