<?php
/**
 * Copyright 2013-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Process incoming compose arguments and generate compose links.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP_Compose_Link
{
    /**
     * @var array
     */
    public $args = array();

    /**
     * @param mixed $in
     */
    public function __construct($in = null)
    {
        $fields = array(
            'to', 'cc', 'bcc', 'message', 'body', 'subject'
        );

        if (is_string($in)) {
            if (($pos = strpos($in, '?')) !== false) {
                parse_str(substr($in, $pos + 1), $this->args);
                $this->args['to'] = substr($in, 0, $pos);
            } else {
                $this->args['to'] = $in;
            }
        } elseif ($in instanceof Horde_Variables) {
            foreach ($fields as $val) {
                if (isset($in->$val)) {
                    $this->args[$val] = $in->$val;
                }
            }
        } elseif (is_array($in)) {
            $this->args = $in;
        }

        if (isset($this->args['to']) &&
            (strpos($this->args['to'], 'mailto:') === 0)) {
            $mailto = @parse_url($this->args['to']);
            if (is_array($mailto)) {
                $this->args['to'] = isset($mailto['path'])
                    ? $mailto['path']
                    : '';
                if (!empty($mailto['query'])) {
                    parse_str($mailto['query'], $vals);
                    foreach ($fields as $val) {
                        if (isset($vals[$val])) {
                            $this->args[$val] = $vals[$val];
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns the appropriate link to call the message composition script.
     *
     * @param string $simplejs  Use simple JS (instead of HordePopup JS)?
     *
     * @return Horde_Url  The link to the message composition script.
     */
    public function link($simplejs = false)
    {
        global $browser, $prefs, $registry;

        $args = $this->args;
        $callback = $raw = false;
        $view = $registry->getView();

        if ($view == Horde_Registry::VIEW_SMARTMOBILE) {
            $url = new Horde_Core_Smartmobile_Url(Horde::url('smartmobile.php'));
            $url->setAnchor('compose');
        } elseif ($simplejs || ($view == Horde_Registry::VIEW_DYNAMIC)) {
            $args['popup'] = 1;

            $url = ($view == Horde_Registry::VIEW_DYNAMIC)
                ? IMP_Dynamic_Compose::url()
                : IMP_Basic_Compose::url();
            $raw = true;
            $callback = array($this, 'composeLinkSimpleCallback');
        } elseif ($prefs->getValue('compose_popup') &&
                  $browser->hasFeature('javascript')) {
            $url = IMP_Basic_Compose::url();
            $callback = array($this, 'composeLinkJsCallback');
        } else {
            $url = IMP_Basic_Compose::url();
        }

        if (isset($args['mailbox'])) {
            $url = IMP_Mailbox::get($args['mailbox'])->url($url, $args['buid']);
            unset($args['buid'], $args['mailbox']);
        } elseif (!($url instanceof Horde_Url)) {
            $url = Horde::url($url);
        }

        $url->setRaw($raw)->add($args);
        if ($callback) {
            $url->toStringCallback = $callback;
        }

        return $url;
    }

    /**
     * Callback for Horde_Url when generating "simple" compose links. Simple
     * links don't require exterior javascript libraries.
     *
     * @param Horde_Url $url  URL object.
     *
     * @return string  URL string representation.
     */
    public function composeLinkSimpleCallback($url)
    {
        return "javascript:void(window.open('" . strval($url) . "','','width=820,height=610,status=1,scrollbars=yes,resizable=yes'))";
    }

    /**
     * Callback for Horde_Url when generating javascript compose links.
     *
     * @param Horde_Url $url  URL object.
     *
     * @return string  URL string representation.
     */
    public function composeLinkJsCallback($url)
    {
        return 'javascript:' . Horde::popupJs(strval($url), array('urlencode' => true));
    }

}
