<?php
/**
 * The Horde_Mime_Viewer_html class renders out HTML text with an effort to
 * remove potentially malicious code.
 *
 * Copyright 1999-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Jon Parise <jon@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_html extends Horde_Mime_Viewer_Driver
{
    /**
     * Can this driver render various views?
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'full' => true,
        'info' => false,
        'inline' => true,
    );

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    public function _render()
    {
        $html = $this->_cleanHTML($this->_mimepart->getContents(), false);

        return array(
            'data' => $html['data'],
            'status' => $html['status'],
            'type' => $this->_mimepart->getType(true)
        );
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    public function _renderInline()
    {
        $html = $this->_cleanHTML($this->_mimepart->getContents(), true);

        return array(
            'data' => String::convertCharset($html['data'], $this->_mimepart->getCharset()),
            'status' => $html['status']
        );
    }

    /**
     * Filters active content, dereferences external links, detects phishing,
     * etc.
     *
     * @todo Use IP checks from
     * http://lxr.mozilla.org/mailnews/source/mail/base/content/phishingDetector.js.
     *
     * @param string $data     The HTML data.
     * @param boolean $inline  Are we viewing inline?
     *
     * @return array  Two elements: 'html' and 'status'.
     */
    protected function _cleanHTML($data, $inline)
    {
        global $browser, $prefs;

        $phish_warn = false;

        /* Deal with <base> tags in the HTML, since they will screw up our own
         * relative paths. */
        if (preg_match('/<base href="?([^"> ]*)"? ?\/?>/i', $data, $matches)) {
            $base = $matches[1];
            if (substr($base, -1) != '/') {
                $base .= '/';
            }

            /* Recursively call _cleanHTML() to prevent clever fiends from
             * sneaking nasty things into the page via $base. */
            $base = $this->_cleanHTML($base, $inline);

            /* Attempt to fix paths that were relying on a <base> tag. */
            if (!empty($base)) {
                $pattern = array('|src=(["\'])([^:"\']+)\1|i',
                                 '|src=([^: >"\']+)|i',
                                 '|href= *(["\'])([^:"\']+)\1|i',
                                 '|href=([^: >"\']+)|i');
                $replace = array('src=\1' . $base . '\2\1',
                                 'src=' . $base . '\1',
                                 'href=\1' . $base . '\2\1',
                                 'href=' . $base . '\1');
                $data = preg_replace($pattern, $replace, $data);
            }
        }

        require_once 'Horde/Text/Filter.php';
        $strip_style_attributes = (($browser->isBrowser('mozilla') &&
                                    $browser->getMajor() == 4) ||
                                   $browser->isBrowser('msie'));
        $strip_styles = $inline || $strip_style_attributes;
        $data = Text_Filter::filter($data, 'xss',
                                    array('body_only' => $inline,
                                          'strip_styles' => $strip_styles,
                                          'strip_style_attributes' => $strip_style_attributes));

        /* Check for phishing exploits. */
        if ($this->getConfigParam('phishing_check')) {
            if (preg_match('/href\s*=\s*["\']?\s*(http|https|ftp):\/\/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?:[^>]*>\s*(?:\\1:\/\/)?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})[^<]*<\/a)?/i', $data, $m)) {
                /* Check 1: Check for IP address links, but ignore if the link
                 * text has the same IP address. */
                if (!isset($m[3]) || ($m[2] != $m[3])) {
                    if (isset($m[3])) {
                        $data = preg_replace('/href\s*=\s*["\']?\s*(http|https|ftp):\/\/' . preg_quote($m[2], '/') . '(?:[^>]*>\s*(?:$1:\/\/)?' . preg_quote($m[3], '/') . '[^<]*<\/a)?/i', 'class="mimeStatusWarning" $0', $data);
                    }
                    $phish_warn = true;
                }
            } elseif (preg_match_all('/href\s*=\s*["\']?\s*(?:http|https|ftp):\/\/([^\s"\'>]+)["\']?[^>]*>\s*(?:(?:http|https|ftp):\/\/)?(.*?)<\/a/is', $data, $m)) {
                /* $m[1] = Link; $m[2] = Target
                 * Check 2: Check for links that point to a different host than
                 * the target url; if target looks like a domain name, check it
                 * against the link. */
                for ($i = 0, $links = count($m[0]); $i < $links; ++$i) {
                    $link = strtolower(urldecode($m[1][$i]));
                    $target = strtolower(preg_replace('/^(http|https|ftp):\/\//', '', strip_tags($m[2][$i])));
                    if (preg_match('/^[-._\da-z]+\.[a-z]{2,}/i', $target) &&
                        (strpos($link, $target) !== 0) &&
                        (strpos($target, $link) !== 0)) {
                        /* Don't consider the link a phishing link if the
                         * domain is the same on both links (e.g.
                         * adtracking.example.com & www.example.com). */
                        preg_match('/\.?([^\.\/]+\.[^\.\/]+)[\/?]/', $link, $host1);
                        preg_match('/\.?([^\.\/]+\.[^\.\/ ]+)([\/ ].*)?$/', $target, $host2);
                        if (!(count($host1) && count($host2)) ||
                            (strcasecmp($host1[1], $host2[1]) !== 0)) {
                            $data = preg_replace('/href\s*=\s*["\']?\s*(?:http|https|ftp):\/\/' . preg_quote($m[1][$i], '/') . '["\']?[^>]*>\s*(?:(?:http|https|ftp):\/\/)?' . preg_quote($m[2][$i], '/') . '<\/a/is', 'class="mimeStatusWarning" $0', $data);
                            $phish_warn = true;
                        }
                    }
                }
            }
        }

        /* Try to derefer all external references. */
        $data = preg_replace_callback('/href\s*=\s*(["\'])?((?(1)[^\1]*?|[^\s>]+))(?(1)\1|)/i', array($this, '_dereferCallback'), $data);

        /* Get phishing warning. */
        $status = array();
        if ($phish_warn) {
            $phish_warning = sprintf(_("%s: This message may not be from whom it claims to be. Beware of following any links in it or of providing the sender with any personal information.") . ' ' . _("The links that caused this warning have the same background color as this message."), _("Warning"));
            if (!$inline) {
                $phish_warning = String::convertCharset($phish_warning, NLS::getCharset(), $this->_mimepart->getCharset());
            }

            $status[] = array(
                'text' => $phish_warning,
                'type' => 'warning'
            );
        }

        return array('html' => $data, 'status' => $status);
    }

    /**
     * TODO
     *
     * @param string $m  TODO
     *
     * @return string  TODO
     */
    protected function _dereferCallback($m)
    {
        return 'href="' . Horde::externalUrl($m[2]) . '"';
    }
}
