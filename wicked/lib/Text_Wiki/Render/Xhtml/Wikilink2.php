<?php

require_once 'Text/Wiki/Render/Xhtml/Wikilink.php';

/**
 * @package Wicked
 */
class Text_Wiki_Render_Xhtml_Wikilink2 extends Text_Wiki_Render_Xhtml_Wikilink
{
    /**
     * Renders a token into XHTML.
     *
     * @access public
     *
     * @param array $options The "options" portion of the token (second
     * element).
     *
     * @return string The text rendered from the token options.
     */
    public function token($options)
    {
        // make nice variable names (page, anchor, text)
        extract($options);

        // is there a "page existence" callback?
        // we need to access it directly instead of through
        // getConf() because we'll need a reference (for
        // object instance method callbacks).
        if (isset($this->conf['exists_callback'])) {
            $callback =& $this->conf['exists_callback'];
        } else {
            $callback = false;
        }

        if ($callback) {
            // use the callback function
            $exists = call_user_func($callback, $page);
        } else {
            // no callback, go to the naive page array.
            $list = $this->getConf('pages');
            if (is_array($list)) {
                // yes, check against the page list
                $exists = in_array($page, $list);
            } else {
                // no, assume it exists
                $exists = true;
            }
        }

        $anchor = $this->urlEncode(substr($anchor, 1));
        if (strlen($anchor)) {
            $anchor = '#' . $anchor;
        }

        // Does the page exist?
        if ($exists) {
            $href = sprintf($this->getConf('view_url'), $GLOBALS['conf']['urls']['pretty'] == 'rewrite' ? htmlspecialchars($page) : $this->urlEncode($page)) . $anchor;

            // get the CSS class and generate output
            $css = ' class="'.$this->textEncode($this->getConf('css')).'"';

            $start = '<a'.$css.' href="'.$this->textEncode($href).'">';
            $end = '</a>';
        } else {
            $new_url = $this->getConf('new_url');
            if (!$new_url) {
                return $this->textEncode($text);
            }

            $href = sprintf($new_url, (!empty($GLOBALS['conf']['options']['use_mod_rewrite']) ? htmlspecialchars($page) : $this->urlEncode($page)));

            // get the appropriate CSS class and new-link text
            $css = ' class="'.$this->textEncode($this->getConf('css_new')).'"';
            $new = $this->getConf('new_text');

            // what kind of linking are we doing?
            $pos = $this->getConf('new_text_pos');
            if (! $pos || ! $new) {
                // no position (or no new_text), use css only on the page name

                $start = '<a'.$css.' href="'.$this->textEncode($href).'">';
                $end = '</a>';
            } elseif ($pos == 'before') {
                // use the new_text BEFORE the page name
                $start = '<a'.$css.' href="'.$this->textEncode($href).'">'.$this->textEncode($new).'</a>';
                $end = '';
            } else {
                // default, use the new_text link AFTER the page name
                $start = '';
                $end = '<a'.$css.' href="'.$this->textEncode($href).'">'.$this->textEncode($new).'</a>';
            }
        }
        if (!strlen($text)) {
            $start .= $this->textEncode($page);
        }
        if (isset($type)) {
            switch ($type) {
            case 'start':
                $output = $start;
                break;
            case 'end':
                $output = $end;
                break;
            }
        } else {
            $output = $start.$this->textEncode($text).$end;
        }
        return $output;
    }
}
