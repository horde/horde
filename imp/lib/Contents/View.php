<?php
/**
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Provides logic to format message content for delivery to the browser.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Contents_View
{
    const VIEW_TOKEN = 'imp.view';
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
        $headers = $this->_contents->getHeader();
        $zipfile = trim(preg_replace('/[^\pL\pN-+_. ]/u', '_', $headers->getValue('subject')), ' _');
        if (empty($zipfile)) {
            $zipfile = _("attachments.zip");
        } else {
            $zipfile .= '.zip';
        }

        $GLOBALS['page_output']->disableCompression();
        $this->_contents->fetchCloseSession = true;

        $tosave = array();
        foreach ($this->_contents->downloadAllList() as $val) {
            $mime = $this->_contents->getMIMEPart($val);
            $name = $mime->getName(true);
            if (!$name) {
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
    public function downloadAttach($id, $zip = false)
    {
        $mime = $this->_contents->getMIMEPart($id);
        if ($this->_contents->canDisplay($id, IMP_Contents::RENDER_RAW)) {
            $this->_contents->fetchCloseSession = true;
            $render = $this->_contents->renderMIMEPart($id, IMP_Contents::RENDER_RAW);
            $part = reset($render);
            $mime->setContents($part['data'], array(
                'encoding' => 'binary'
            ));
        }

        $name = $this->_contents->getPartName($mime);

        /* Compress output? */
        if ($zip) {
            $data = Horde_Compress::factory('Zip')->compress(array(
                array(
                    'data' => $mime->getContents(),
                    'name' => $name
                )
            ), array(
                'stream' => true
            ));
            $name .= '.zip';
            $type = 'application/zip';

            $GLOBALS['page_output']->disableCompression();
        } else {
            $data = $mime->getContents(array('stream' => true));
            $type = $mime->getType(true);
        }

        return array(
            'data' => $data,
            'name' => $name,
            'type' => $type
        );
    }

    /**
     */
    public function downloadRender($id, $mode, $ctype = null)
    {
        $this->_contents->fetchCloseSession = true;

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
        $this->_contents->fetchCloseSession = true;

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
        $this->_contents->fetchCloseSession = true;

        return array(
            'data' => $this->_contents->fullMessageText(array(
                'stream' => true
            )),
            'name' => _("Message Source"),
            'type' => 'text/plain'
        );
    }

    /**
     */
    public function saveMessage()
    {
        $name = ($subject = $this->_contents->getHeader()->getValue('subject'))
            ? trim(preg_replace('/[^\pL\pN-+_. ]/u', '_', $subject), ' _')
            : 'saved_message';

        $this->_contents->fetchCloseSession = true;

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

        /* Directly render part if this is not an HTML part. */
        if (stripos($part['type'], 'text/html') !== 0) {
            return $part;
        }

        $imp_ui_mbox = new IMP_Mailbox_Ui();
        $basic_headers = $injector->getInstance('IMP_Message_Ui')->basicHeaders();
        unset($basic_headers['bcc'], $basic_headers['reply-to']);
        $headerob = $this->_contents->getHeader();

        $d_param = Horde_Mime::decodeParam('content-type', $part['type']);

        $headers = array();
        foreach ($basic_headers as $key => $val) {
            if ($hdr_val = $headerob->getValue($key)) {
                /* Format date string. */
                if ($key == 'date') {
                    $hdr_val = $imp_ui_mbox->getDate($hdr_val, $imp_ui_mbox::DATE_FORCE | $imp_ui_mbox::DATE_FULL);
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
                'value' => $user_identity->getFullname() ? $user_identity->getFullname() : $registry->getAuth()
            );
        }

        $view = new Horde_View(array(
            'templatePath' => IMP_TEMPLATES . '/print'
        ));
        $view->addHelper('Text');

        $view->headers = $headers;

        $header_dom = new Horde_Domhtml(Horde_String::convertCharset($view->render('headers'), 'UTF-8', $d_param['params']['charset']), $d_param['params']['charset']);
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
            $cache_list = array();
            $cache_ob = $injector->getInstance('Horde_Cache');

            $css_list = $page_output->css->getStylesheets();
            foreach ($css_list as $val) {
                $cache_list[] = $val;
                $cache_list[] = filemtime($val);
            }
            $cache_id = hash('sha1', implode('', $cache_list));

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

        $pstring = Horde_Mime::decodeParam('content-type', $part['type']);

        $doc = new Horde_Domhtml($part['data'], $pstring['params']['charset']);

        $bodyelt = $doc->dom->getElementsByTagName('body')->item(0);
        $bodyelt->insertBefore($doc->dom->importNode($div, true), $bodyelt->firstChild);

        /* Make the title the e-mail subject. */
        $headelt = $doc->getHead();
        foreach ($headelt->getElementsByTagName('title') as $node) {
            $headelt->removeChild($node);
        }
        $headelt->appendChild($doc->dom->createElement('title', htmlspecialchars($imp_ui_mbox->getSubject($headerob->getValue('subject')))));

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
     * @throws Horde_Token_Exception  Exception on incorrect token.
     */
    public function checkToken(Horde_Variables $vars)
    {
        global $injector;

        $injector->getInstance('Horde_Token')->validate(
            $vars->get(self::VIEW_TOKEN_PARAM),
            self::VIEW_TOKEN
        );
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
    static public function downloadUrl($filename, array $params = array())
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
    static public function addToken(array $params = array())
    {
        global $injector;

        $params[self::VIEW_TOKEN_PARAM] =
            $injector->getInstance('Horde_Token')->get(self::VIEW_TOKEN);

        return $params;
    }

}
