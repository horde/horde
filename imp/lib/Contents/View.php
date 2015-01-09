<?php
/**
 * Copyright 2012-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Provides logic to format message content for delivery to the browser.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2015 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contents_View
{
    const VIEW_TOKEN_PARAM = 'view_token';

    /**
     * @var IMP_Contents
     */
    protected $_contents;

    /**
     * @param IMP_Indices $indices
     */
    public function __construct(IMP_Indices $indices)
    {
        try {
            $this->_contents = $GLOBALS['injector']->getInstance('IMP_Factory_Contents')->create($indices);
        } catch (Exception $e) {
            exit;
        }
    }

    /**
     */
    public function downloadAll()
    {
        global $page_output, $session;

        $headers = $this->_contents->getHeader();
        $zipfile = trim(preg_replace('/[^\pL\pN-+_. ]/u', '_', $headers['Subject']), ' _');
        if (empty($zipfile)) {
            $zipfile = _("attachments.zip");
        } else {
            $zipfile .= '.zip';
        }

        $page_output->disableCompression();
        $session->close();

        $tosave = array();
        foreach ($this->_contents->downloadAllList() as $val) {
            if (!($mime = $this->_getRawDownloadPart($val))) {
                continue;
            }
            if (!($name = $mime->getName(true))) {
                $name = sprintf(_("part %s"), $val);
            }
            $tosave[] = array(
                'data' => $mime->getContents(array('stream' => true)),
                'name' => $name
            );
        }

        if (empty($tosave)) {
            return array();
        }

        return array(
            'data' => Horde_Compress::factory('Zip')->compress($tosave, array(
                'stream' => true
            )),
            'name' => $zipfile,
            'type' => 'application/zip'
        );
    }

    /**
     */
    public function downloadAttach($id)
    {
        global $session;

        $session->close();

        if (!($mime = $this->_getRawDownloadPart($id))) {
            return array();
        }

        return array(
            'data' => $mime->getContents(array('stream' => true)),
            'name' => $this->_contents->getPartName($mime),
            'type' => $mime->getType(true)
        );
    }

    /**
     */
    public function downloadRender($id, $mode, $ctype = null)
    {
        global $session;

        $session->close();

        $render = $this->_contents->renderMIMEPart(
            $id,
            $mode,
            array(
                'type' => $ctype
            )
        );
        return reset($render);
    }

    /**
     * @throws IMP_Exception
     */
    public function viewAttach($id, $mode, $autodetect = false, $ctype = null)
    {
        global $session;

        $session->close();

        $render = $this->_contents->renderMIMEPart(
            $id,
            $mode,
            array(
                'autodetect' => $autodetect,
                'type' => $ctype
            )
        );

        if (!empty($render)) {
            return reset($render);
        } elseif ($autodetect) {
            $e = new IMP_Exception(_("Could not auto-determine data type."));
            $e->logged = true;
            throw $e;
        }

        return array();
    }

    /**
     */
    public function viewSource()
    {
        global $session;

        $session->close();

        $charset = $this->_contents->getMIMEMessage()->getCharset();

        return array(
            'data' => $this->_contents->fullMessageText(array(
                'stream' => true
            )),
            'name' => _("Message Source"),
            'type' => 'text/plain; charset=' . (is_null($charset) ? 'UTF-8' : $charset)
        );
    }

    /**
     */
    public function saveMessage()
    {
        global $session;

        $session->close();

        $name = ($subject = $this->_contents->getHeader()->getHeader('Subject'))
            ? trim(preg_replace('/[^\pL\pN-+_. ]/u', '_', $subject), ' _')
            : 'saved_message';

        return array(
            'data' => $this->_contents->fullMessageText(array(
                'stream' => true
            )),
            'name' => $name . '.eml',
            'type' => 'message/rfc822'
        );
    }

    /**
     */
    public function printAttach($id)
    {
        global $injector, $page_output, $prefs, $registry;

        if (is_null($id) ||
            !($render = $this->_contents->renderMIMEPart($id, IMP_Contents::RENDER_FULL))) {
            return array();
        }

        $part = reset($render);

        /* Directly render part if this is not an HTML part or it is empty. */
        if ((stripos($part['type'], 'text/html')) !== 0 ||
            !strlen($part['data'])) {
            return $part;
        }

        $imp_ui_mbox = new IMP_Mailbox_Ui();
        $basic_headers = $injector->getInstance('IMP_Message_Ui')->basicHeaders();
        unset($basic_headers['bcc']);
        $headerob = $this->_contents->getHeader();

        $headers = array();
        foreach ($basic_headers as $key => $val) {
            if ($hdr_val = $headerob[$key]) {
                /* Format date string. */
                if ($key == 'date') {
                    $date_ob = new IMP_Message_Date($hdr_val);
                    $hdr_val = $date_ob->format($date_ob::DATE_FORCE);
                }

                $headers[] = array(
                    'header' => $val,
                    'value' => $hdr_val
                );
            }
        }

        if ($prefs->getValue('add_printedby')) {
            $user_identity = $injector->getInstance('IMP_Identity');
            $headers[] = array(
                'header' => _("Printed By"),
                'value' => $user_identity->getFullname() ?: $registry->getAuth()
            );
        }

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/print'
        ));
        $view->addHelper('Text');

        $view->headers = $headers;

        $ctype = new Horde_Mime_Headers_ContentParam(
            'Content-Type',
            $part['type']
        );
        $header_dom = new Horde_Domhtml(
            Horde_String::convertCharset($view->render('headers'), 'UTF-8', $ctype['charset']),
            $ctype['charset']
        );
        $elt = $header_dom->dom->getElementById('headerblock');
        $elt->removeAttribute('id');

        if ($elt->hasAttribute('class')) {
            $selectors = array('body');
            foreach (explode(' ', $elt->getAttribute('class')) as $val) {
                if (strlen($val = trim($val))) {
                    $selectors[] = '.' . $val;
                }
            }

            /* Cache CSS. */
            $cache_ob = $injector->getInstance('Horde_Cache');

            $css_list = $page_output->css->getStylesheets();
            $ctx = hash_init('md5');
            foreach ($css_list as $val) {
                hash_update($ctx, $val['fs']);
                hash_update($ctx, filemtime($val['fs']));
            }
            $cache_id = 'imp_printcss_' . hash_final($ctx);

            if (($style = $cache_ob->get($cache_id, 0)) === false) {
                try {
                    $css_parser = new Horde_Css_Parser(
                        $page_output->css->loadCssFiles(
                            $page_output->css->getStylesheets()
                        )
                    );

                    $style = '';

                    foreach ($css_parser->doc->getContents() as $val) {
                        if (($val instanceof Sabberworm\CSS\RuleSet\DeclarationBlock) &&
                            array_intersect($selectors, array_map('strval', $val->getSelectors()))) {
                            $style .= implode('', array_map('strval', $val->getRules()));
                        }
                    }

                    $cache_ob->set($cache_id, $style, 86400);
                } catch (Exception $e) {
                    // Ignore CSS if it can't be parsed.
                }
            }

            if (strlen($style)) {
                $elt->setAttribute('style', ($elt->hasAttribute('style') ? rtrim($elt->getAttribute('style'), ' ;') . ';' : '') . $style);
            }
        }

        $elt->removeAttribute('class');

        /* Need to wrap headers in another DIV. */
        $newdiv = new DOMDocument();
        $div = $newdiv->createElement('div');
        $div->appendChild($newdiv->importNode($elt, true));

        $doc = new Horde_Domhtml($part['data'], $ctype['charset']);

        $bodyelt = $doc->dom->getElementsByTagName('body')->item(0);
        $bodyelt->insertBefore($doc->dom->importNode($div, true), $bodyelt->firstChild);

        /* Make the title the e-mail subject. */
        $headelt = $doc->getHead();
        foreach ($headelt->getElementsByTagName('title') as $node) {
            $headelt->removeChild($node);
        }
        $headelt->appendChild($doc->dom->createElement('title', htmlspecialchars($imp_ui_mbox->getSubject($headerob['Subject']))));

        return array(
            'data' => $doc->returnHtml(),
            'name' => $part['name'],
            'type' => $part['type']
        );
    }

    /**
     * Check for a download token.
     *
     * @param Horde_Variables $vars  Form variables.
     *
     * @throws Horde_Exception  Exception on incorrect token.
     */
    public function checkToken(Horde_Variables $vars)
    {
        $GLOBALS['session']->checkToken($vars->get(self::VIEW_TOKEN_PARAM));
    }

    /* Static methods. */

    /**
     * Returns a URL to be used for downloading data.
     * IMP adds token data, since the data displayed is coming from a remote
     * source.
     *
     * @see Horde_Registry#downloadUrl()
     *
     * @param string $filename  The filename of the download data.
     * @param array $params     Additional URL parameters needed.
     *
     * @return Horde_Url  The download URL.
     */
    public static function downloadUrl($filename, array $params = array())
    {
        global $registry;

        return $registry->downloadUrl($filename, self::addToken($params));
    }

    /**
     * Adds the view token to a parameter list.
     *
     * @param array $params  URL parameters.
     *
     * @return array  Parameter list with token added.
     */
    public static function addToken(array $params = array())
    {
        global $session;

        $params[self::VIEW_TOKEN_PARAM] = $session->getToken();

        return $params;
    }

    /* Internal methods. */

    /**
     * Get a MIME Part for use in creating download.
     *
     * @param string $id  MIME ID.
     *
     * @return Horde_Mime_Part  MIME part, or null on error.
     */
    protected function _getRawDownloadPart($id)
    {
        if (!($mime = $this->_contents->getMimePart($id))) {
            return null;
        }

        if ($this->_contents->canDisplay($id, IMP_Contents::RENDER_RAW)) {
            $render = $this->_contents->renderMIMEPart($id, IMP_Contents::RENDER_RAW);
            $part = reset($render);
            $mime->setContents($part['data'], array(
                'encoding' => 'binary'
            ));
        }

        return $mime;
    }

}
