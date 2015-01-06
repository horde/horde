<?php
/**
 * Copyright 2014-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Parse HTML signature data.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2014-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose_HtmlSignature
{
    /** Signature data attribute name. */
    const HTMLSIG_ATTR = 'imp_htmlsig';

    /**
     * DOM object containing HTML signature data.
     *
     * @var Horde_Domhtml
     */
    public $dom;

    /**
     * Constructor.
     *
     * @param string $sig  HTML signature data.
     *
     * @throws IMP_Exception
     */
    public function __construct($sig)
    {
        global $conf, $injector;

        /* Scrub HTML. */
        $this->dom = $injector->getInstance('Horde_Core_Factory_TextFilter')->filter(
            $sig,
            'Xss',
            array(
                'charset' => 'UTF-8',
                'return_dom' => true,
                'strip_style_attributes' => false
            )
        );

        $img_limit = intval($conf['compose']['htmlsig_img_size']);

        $xpath = new DOMXPath($this->dom->dom);
        foreach ($xpath->query('//*[@src]') as $node) {
            $src = $node->getAttribute('src');

            if (Horde_Url_Data::isData($src)) {
                if (strcasecmp($node->tagName, 'IMG') === 0) {
                    $data_url = new Horde_Url_Data($src);
                    if ($img_limit &&
                        ($img_limit -= strlen($data_url->data)) < 0) {
                        throw new IMP_Exception(_("The total size of your HTML signature image data has exceeded the maximum allowed."));
                    }

                    $node->setAttribute(self::HTMLSIG_ATTR, 1);
                } else {
                    /* Don't allow any other non-image data URLs. */
                    $node->removeAttribute('src');
                }
            }
        }
    }

    /**
     * Determine if node contains HTML signature image data.
     *
     * @param DOMNode $node   The node to check.
     * @param boolean $strip  Strip attribute from the node?
     *
     * @return boolean  True if node contains image data.
     */
    static public function isSigImage(DOMNode $node, $strip = false)
    {
        if ((strcasecmp($node->tagName, 'IMG') === 0) &&
            $node->hasAttribute(self::HTMLSIG_ATTR)) {
            if ($strip) {
                $node->removeAttribute(self::HTMLSIG_ATTR);
            }
            return true;
        }

        return false;
    }

}
