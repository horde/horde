<?php
/**
 * The Horde_Mime_Viewer_Html class renders out HTML text with an effort to
 * remove potentially malicious code.
 *
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Anil Madhavapeddy <anil@recoil.org>
 * @author  Jon Parise <jon@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime_Viewer
 */
class Horde_Mime_Viewer_Html extends Horde_Mime_Viewer_Driver
{
    /**
     * This driver's display capabilities.
     *
     * @var array
     */
    protected $_capability = array(
        'full' => true,
        'info' => false,
        'inline' => true,
        'raw' => false
    );

    /**
     * The CSS used to display the phishing warning.
     *
     * @var string
     */
    protected $_phishCss = 'padding: 1px;margin-bottom: 3px;font-size: 90%;border: 1px solid #800;background: #e81222;color: #fff;width: 100%;';

    /**
     * Phishing status of last call to _phishingCheck().
     *
     * @var boolean
     */
    protected $_phishWarn = false;

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _render()
    {
        $html = $this->_cleanHTML($this->_mimepart->getContents(), array('inline' => false));

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => $html['html'],
                'status' => array(),
                'type' => $this->_mimepart->getType(true)
            )
        );
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        $html = $this->_cleanHTML($this->_mimepart->getContents(), array('inline' => true));

        return array(
            $this->_mimepart->getMimeId() => array(
                'data' => Horde_String::convertCharset($html['data'], $this->_mimepart->getCharset()),
                'status' => $html['status'],
                'type' => 'text/html; charset=' . $GLOBALS['registry']->getCharset()
            )
        );
    }

    /**
     * Filters active content, dereferences external links, detects phishing,
     * etc.
     *
     * @todo Use IP checks from
     * http://lxr.mozilla.org/mailnews/source/mail/base/content/phishingDetector.js.
     *
     * @param string $data    The HTML data.
     * @param array $options  Additional options:
     * <pre>
     * 'charset' => (string) The charset of $data.
     *              DEFAULT: The base part charset.
     * 'inline' => (boolean) Are we viewing inline?
     *             DEFAULT: false
     * 'noprefetch' => (boolean) Disable DNS prefetching?
     *                 DEFAULT: false
     * 'phishing' => (boolean) Do phishing highlighting even if not viewing
     *               inline.
     *               DEFAULT: false.
     * </pre>
     *
     * @return string  The cleaned HTML string.
     */
    protected function _cleanHTML($data, $options = array())
    {
        global $browser;

        /* Deal with <base> tags in the HTML, since they will screw up our own
         * relative paths. */
        if (!empty($options['inline']) &&
            preg_match('/<base\s+href="?([^"> ]*)"? ?\/?>/i', $data, $matches)) {
            $base = $matches[1];
            if (substr($base, -1) != '/') {
                $base .= '/';
            }

            /* Recursively call _cleanHTML() to prevent clever fiends from
             * sneaking nasty things into the page via $base. */
            $base = $this->_cleanHTML($base, $options);

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

        $strip_style_attributes = !empty($options['inline']) &&
                                 (($browser->isBrowser('mozilla') &&
                                   $browser->getMajor() == 4) ||
                                   $browser->isBrowser('msie'));
        $strip_styles = !empty($options['inline']) || $strip_style_attributes;

        $data = Horde_Text_Filter::filter($data, array('cleanhtml', 'xss'), array(
            array(
                'charset' => isset($options['charset']) ? $options['charset'] : $this->_mimepart->getCharset()
            ),
            array(
                'body_only' => !empty($options['inline']),
                'noprefetch' => !empty($options['noprefetch']),
                'strip_styles' => $strip_styles,
                'strip_style_attributes' => $strip_style_attributes
            )
        ));

        /* Check for phishing exploits. */
        if (!empty($options['inline']) || !empty($options['phishing'])) {
            $data = $this->_phishingCheck($data);
        }

        /* Try to derefer all external references. */
        $data = preg_replace_callback('/href\s*=\s*(["\'])?((?(1)[^\1]*?|[^\s>]+))(?(1)\1|)/i', array($this, '_dereferCallback'), $data);

        return $data;
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

    /**
     * Check for phishing exploits.
     *
     * @param string $data       The html data.
     * @param boolean $scanonly  Only scan data; don't replace anything.
     *
     * @return string  The string, with phishing links highlighted.
     */
    protected function _phishingCheck($data, $scanonly = false)
    {
        $this->_phishWarn = false;

        if (!$this->getConfigParam('phishing_check')) {
            return $data;
        }

        if (preg_match('/href\s*=\s*["\']?\s*(http|https|ftp):\/\/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?:[^>]*>\s*(?:\\1:\/\/)?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})[^<]*<\/a)?/i', $data, $m)) {
            /* Check 1: Check for IP address links, but ignore if the link
             * text has the same IP address. */
            if (!isset($m[3]) || ($m[2] != $m[3])) {
                if (isset($m[3]) && !$scanonly) {
                    $data = preg_replace('/href\s*=\s*["\']?\s*(http|https|ftp):\/\/' . preg_quote($m[2], '/') . '(?:[^>]*>\s*(?:$1:\/\/)?' . preg_quote($m[3], '/') . '[^<]*<\/a)?/i', 'style="' . $this->_phishCss . '" $0', $data);
                }
                $this->_phishWarn = true;
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
                    /* Don't consider the link a phishing link if the domain
                     * is the same on both links (e.g. adtracking.example.com
                     * & www.example.com). */
                    preg_match('/\.?([^\.\/]+\.[^\.\/]+)[\/?]/', $link, $host1);
                    preg_match('/\.?([^\.\/]+\.[^\.\/ ]+)([\/ ].*)?$/s', $target, $host2);
                    if (!(count($host1) && count($host2)) ||
                        (strcasecmp($host1[1], $host2[1]) !== 0)) {
                        if (!$scanonly) {
                            $data = preg_replace('/href\s*=\s*["\']?\s*(?:http|https|ftp):\/\/' . preg_quote($m[1][$i], '/') . '["\']?[^>]*>\s*(?:(?:http|https|ftp):\/\/)?' . preg_quote($m[2][$i], '/') . '<\/a/is', 'style="' . $this->_phishCss . '" $0', $data);
                        }
                        $this->_phishWarn = true;
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Returns any phishing warnings that should be shown to the user.
     *
     * @return array  The status array.
     */
    protected function _phishingStatus()
    {
        if (!$this->_phishWarn) {
            return array();
        }

        return array(
            'class' => 'mimestatuswarning',
            'text' => array(
                sprintf(_("%s: This message may not be from whom it claims to be. Beware of following any links in it or of providing the sender with any personal information."), _("Warning")),
            _("The links that caused this warning have this background color:") . ' <span style="' . $this->_phishCss . '">' . _("EXAMPLE") . '.</span>'
            )
        );
    }

}
