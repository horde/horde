<?php
/**
 * The Horde_Core_Ui_Pager:: provides links to individual pages.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Ben Chavet <ben@chavet.net>
 * @author   Joel Vandal <joel@scopserv.com>
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Ui_Pager extends Horde_Core_Ui_Widget
{
    /**
     * Constructor.
     *
     * TODO
     */
    public function __construct($name, $vars, $config)
    {
        $config = array_merge(array(
            'page_limit' => 10,
            'perpage' => 100
        ), $config);

        parent::__construct($name, $vars, $config);

        // @todo Make sure 'url' argument is a Horde_Url object.
        if (!($this->_config['url'] instanceof Horde_Url)) {
            $this->_config['url'] = new Horde_Url($this->_config['url']);
        }
    }

    /**
     * Render the pager.
     *
     * @return string  HTML code containing a centered table with the pager
     *                 links.
     */
    public function render($data = null)
    {
        global $prefs, $registry, $conf;

        $num = $this->_config['num'];
        $url = $this->_config['url'];

        $page_limit = $this->_config['page_limit'];
        $perpage = $this->_config['perpage'];

        $current_page = $this->_vars->get($this->_name);

        // Figure out how many pages there will be.
        $pages = ($num / $perpage);
        if (is_integer($pages)) {
            $pages--;
        }
        $pages = (int)$pages;

        // Return nothing if there is only one page.
        if ($pages == 0 || $num == 0) {
            return '';
        }

        $html = '<div class="pager">';

        if ($current_page > 0) {
            // Create the '<< Prev' link if we are not on the first page.
            $link = $this->_link($this->_addPreserved($url->copy()->add($this->_name, $current_page - 1)));

            $prev_text = isset($this->_config['previousHTML'])
                ? $this->_config['previousHTML']
                : htmlspecialchars(Horde_Core_Translation::t("<Previous"));

            $html .= Horde::link($link, '', 'prev') . $prev_text . '</a>';
        }

        // Figure out the top & bottom display limits.
        $bottom = max(0, $current_page - ($page_limit / 2) + 1);
        $top = $bottom + $page_limit - 1;
        if ($top - 1 > $pages) {
            $bottom -= ($top - 1) - $pages;
            $top = $pages + 1;
        }

        // Create bottom '[x-y]' link if necessary.
        if ($bottom > 0) {
            $link = $this->_link($this->_addPreserved($url->copy()->add($this->_name, $bottom - 1)));
            $html .= ' ' . Horde::link($link, '', 'prevRange') . '[' . ($bottom == 1 ? $bottom : '1-' . $bottom) . ']</a>';
        }

        // Create links to individual pages between limits.
        for ($i = $bottom; $i <= $top && $i <= $pages; ++$i) {
            if ($i == $current_page) {
                $html .= ' <strong>(' . ($i + 1) . ')</strong>';
            } elseif ($i >= 0 && $i <= $pages) {
                $link = $this->_link($this->_addPreserved($url->copy()->add($this->_name, $i)));
                $html .= ' ' . Horde::link($link) . ($i + 1) . '</a>';
            }
        }

        // Create top '[x-y]' link if necessary.
        if ($top < $pages) {
            $link = $this->_link($this->_addPreserved($url->copy()->add($this->_name, $top + 1)));

            $html .= ' ' . Horde::link($link, '', 'nextRange') . '[' .
                ($top + 2 == $pages + 1 ? $pages + 1 : ($top + 2) . '-' . ($pages + 1)) . ']</a>';
        }

        // Create the 'Next>>' link if we are not on the last page.
        if ($current_page < $pages) {
            $link = $this->_link($this->_addPreserved($url->copy()->add($this->_name, $current_page + 1)));

            $next_text = isset($this->_config['nextHTML'])
                ? $this->_config['nextHTML']
                : htmlspecialchars(Horde_Core_Translation::t("Next>"));

            $html .= ' ' . Horde::link($link, '', 'next') . $next_text . '</a>';
        }

        return $html . '</div>';
    }

}
