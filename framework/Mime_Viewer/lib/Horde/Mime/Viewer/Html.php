<?php
/**
 * The Horde_Mime_Viewer_Html class renders out HTML text with an effort to
 * remove potentially malicious code.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Anil Madhavapeddy <anil@recoil.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Mime_Viewer
 */
class Horde_Mime_Viewer_Html extends Horde_Mime_Viewer_Base
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
     * Temp array for storing data when parsing the HTML document.
     *
     * @var array
     */
    protected $_tmp = array();

    /**
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * browser - (Horde_Browser) A browser object.
     * external_callback - (callback) A callback function that a href URL is
     *                     passed through. The function must take the original
     *                     URL as the first parameter.
     *                     DEFAULT: No callback
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        $this->_required = array_merge($this->_required, array(
            'browser'
        ));

        parent::__construct($part, $conf);
    }

    /**
     * Return the full rendered version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _render()
    {
        $html = $this->_cleanHTML($this->_mimepart->getContents(), array('inline' => false));

        return $this->_renderReturn(
            $html,
            $this->_mimepart->getType(true)
        );
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        $html = $this->_cleanHTML($this->_mimepart->getContents(), array('inline' => true));

        return $this->_renderReturn(
            Horde_String::convertCharset($html, $this->_mimepart->getCharset(), 'UTF-8'),
            'text/html; charset=UTF-8'
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
     * 'charset' - (string) The charset of $data.
     *             DEFAULT: The base part charset.
     * 'inline' - (boolean) Are we viewing inline?
     *            DEFAULT: false
     * 'noprefetch' - (boolean) Disable DNS prefetching?
     *                DEFAULT: false
     * 'phishing' - (boolean) Do phishing highlighting even if not viewing
     *              inline.
     *              DEFAULT: false.
     * </pre>
     *
     * @return string  The cleaned HTML string.
     */
    protected function _cleanHTML($data, $options = array())
    {
        $browser = $this->getConfigParam('browser');
        $charset = isset($options['charset'])
            ? $options['charset']
            : $this->_mimepart->getCharset();
        $strip_style_attributes =
            (!empty($options['inline']) &&
             (($browser->isBrowser('mozilla') &&
              ($browser->getMajor() == 4)) ||
              $browser->isBrowser('msie')));

        $data = $this->_textFilter($data, array('cleanhtml', 'xss'), array(
            array(
                'charset' => $charset
            ),
            array(
                'charset' => $charset,
                'noprefetch' => !empty($options['noprefetch']),
                'return_dom' => true,
                'strip_styles' => (!empty($options['inline']) || $strip_style_attributes),
                'strip_style_attributes' => $strip_style_attributes
            )
        ));

        $this->_tmp = array(
            'base' => null,
            'inline' => !empty($options['inline']),
            'phish' => ((!empty($options['inline']) || !empty($options['phishing'])) && $this->getConfigParam('phishing_check'))
        );
        $this->_phishWarn = false;

        $this->_node($data->dom, $data->dom);

        return $data->returnHtml();
    }

    /**
     * Process DOM node.
     *
     * @param DOMDocument $doc  Document node.
     * @param DOMNode $node     Node.
     */
    protected function _node($doc, $node)
    {
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement) {
                    switch (strtolower($child->tagName)) {
                    case 'base':
                        /* Deal with <base> tags in the HTML, since they will
                         * screw up our own relative paths. */
                        if ($this->_tmp['inline'] &&
                            $child->hasAttribute('href')) {
                            $base = $child->getAttribute('href');
                            if (substr($base, -1) != '/') {
                                $base .= '/';
                            }

                            $this->_tmp['base'] = $base;
                            $child->removeAttribute('href');
                        }
                        break;
                    }

                    foreach ($child->attributes as $val) {
                        /* Attempt to fix paths that were relying on a <base>
                         * tag. */
                        if (!is_null($this->_tmp['base']) &&
                            in_array($val->name, array('href', 'src'))) {
                            $child->setAttribute($val->name, $this->_tmp['base'] . ltrim($val->value, '/'));
                        }

                        if ($val->name == 'href') {
                            if ($this->_tmp['phish'] &&
                                $this->_phishingCheck($val->value, $child->textContent)) {
                                $this->_phishWarn = true;
                                $child->setAttribute('style', ($child->hasAttribute('style') ? rtrim($child->getAttribute('style'), '; ') . ';' : '') . $this->_phishCss);
                            }

                            if (isset($this->_params['external_callback'])) {
                                /* Try to derefer all external references. */
                                $child->setAttribute('href', call_user_func($this->_params['external_callback'], $val->value));
                            }
                        }
                    }
                }

                $this->_nodeCallback($doc, $child);
                $this->_node($doc, $child);
            }
        }
    }

    /**
     * Process DOM node (callback).
     *
     * @param DOMDocument $doc  Document node.
     * @param DOMNode $node     Node.
     */
    protected function _nodeCallback($doc, $node)
    {
    }

    /**
     * Check for phishing exploits.
     *
     * @param string $href  The HREF value.
     * @param string $text  The text value of the link.
     *
     * @return boolean  True if phishing is detected.
     */
    protected function _phishingCheck($href, $text)
    {
        /* For phishing, we are checking whether the displayable text URL is
         * the same as the HREF URL. If we can't parse the text URL, then we
         * can't do phishing checks. */
        $text_url = @parse_url($text);
        if (!$text_url) {
            return false;
        }

        $href_url = parse_url($href);

        /* Only concern ourselves with HTTP and FTP links. */
        if (!isset($href_url['scheme']) ||
            !in_array($href_url['scheme'], array('ftp', 'http', 'https'))) {
            return false;
        }

        /* Check for case where text is just the domain name. */
        if (!isset($text_url['host'])) {
            if (!isset($text_url['path'])) {
                return false;
            }

            /* Path info may include path, so remove that. */
            if (($pos = strpos($text_url['path'], '/')) !== false) {
                $text_url['path'] = substr($text_url['path'], 0, $pos);
            }

            if (!preg_match("/^[^\.\s\/]+(?:\.[^\.\s]+)+$/", $text_url['path'])) {
                return false;
            }

            $text_url['host'] = $text_url['path'];
        }

        /* If port exists on link, and text link has scheme or port defined,
         * do extra checks:
         * 1. If port exists on text link, and doesn't match, this is
         * phishing.
         * 2. If port doesn't exist on text link, and port does not match
         * defaults, this is phishing. */
        if (isset($href_url['port']) &&
            (isset($text_url['scheme']) || isset($text_url['port']))) {
            if (!isset($text_url['port'])) {
                switch ($text_url['scheme']) {
                case 'ftp':
                    $text_url['port'] = 25;
                    break;

                case 'http':
                    $text_url['port'] = 80;
                    break;

                case 'https':
                    $text_url['port'] = 443;
                    break;
                }
            }

            if ($href_url['port'] != $text_url['port']) {
                return false;
            }
        }

        if (strcasecmp($href_url['host'], $text_url['host']) === 0) {
            return false;
        }

        /* Don't consider the link a phishing link if the domain is the same
         * on both links (e.g. adtracking.example.com & www.example.com). */
        $host1 = explode('.', $href_url['host']);
        $host2 = explode('.', $text_url['host']);

        return (strcasecmp(implode('.', array_slice($host1, -2)), implode('.', array_slice($host2, -2))) !== 0);
    }

}
