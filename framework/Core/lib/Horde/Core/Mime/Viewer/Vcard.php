<?php
/**
 * The Horde_Core_Mime_Viewer_Vcard class renders out vCards in HTML format.
 *
 * Copyright 2002-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Jan Schneider <jan@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @package  Core
 */
class Horde_Core_Mime_Viewer_Vcard extends Horde_Mime_Viewer_Base
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
     * URL that can be used as a callback for displaying images.
     *
     * @var Horde_Url
     */
    protected $_imageUrl;

    /**
     * Constructor.
     *
     * @param Horde_Mime_Part $mime_part  The object with the data to be
     *                                    rendered.
     * @param array $conf                 Configuration:
     * <pre>
     * 'browser' - (Horde_Browser) Browser object.
     * 'notification' - (Horde_Notification_Base) Notification object.
     * 'prefs' - (Horde_Prefs) Prefs object.
     * 'registry' - (Horde_Registry) Registry object.
     * </pre>
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Horde_Mime_Part $part, array $conf = array())
    {
        $this->_required = array_merge($this->_required, array(
            'browser',
            'notification',
            'prefs',
            'registry'
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
        $ret = $this->_renderInline();

        if (!empty($ret)) {
            $templates = $this->getConfigParam('registry')->get('templates', 'horde');

            reset($ret);
            Horde::startBuffer();
            include $templates . '/common-header.inc';
            echo $ret[key($ret)]['data'];
            include $templates . '/common-footer.inc';
            $ret[key($ret)]['data'] = Horde::endBuffer();
        }

        return $ret;
    }

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See parent::render().
     */
    protected function _renderInline()
    {
        $browser = $this->getConfigParam('browser');
        $notification = $this->getConfigParam('notification');
        $prefs = $this->getConfigParam('prefs');
        $registry = $this->getConfigParam('registry');

        $app = false;
        $data = $this->_mimepart->getContents();
        $html = '';
        $import_msg = null;
        $title = _("vCard");

        $iCal = new Horde_Icalendar();
        if (!$iCal->parsevCalendar($data, 'VCALENDAR', $this->_mimepart->getCharset())) {
            $notification->push(_("There was an error reading the contact data."), 'horde.error');
        }

        if (Horde_Util::getFormData('import') &&
            Horde_Util::getFormData('source') &&
            $registry->hasMethod('contacts/import')) {
            $source = Horde_Util::getFormData('source');
            $count = 0;
            foreach ($iCal->getComponents() as $c) {
                if ($c->getType() == 'vcard') {
                    try {
                        $contacts = $registry->call('contacts/import', array($c, null, $source));
                        ++$count;
                    } catch (Horde_Exception $e) {
                        $notification->push(_("There was an error importing the contact data:") . ' ' . $e->getMessage(), 'horde.error');
                    }
                }
            }
            $notification->push(sprintf(ngettext(
                "%d contact was successfully added to your address book.",
                "%d contacts were successfully added to your address book.",
                $count),
                                        $count),
                                'horde.success');
        }

        $html .= '<table cellspacing="1" border="0" cellpadding="1">';

        foreach ($iCal->getComponents() as $i => $vc) {
            if ($i > 0) {
                $html .= '<tr><td colspan="2">&nbsp;</td></tr>';
            }

            $html .= '<tr><td colspan="2" class="header">';
            $fullname = $vc->getAttributeDefault('FN', false);
            if ($fullname !== false) {
                $html .= $fullname;
            }
            $html .= '</td></tr>';

            $n = $vc->printableName();
            if (!empty($n)) {
                $html .= $this->_row(_("Name"), $n);
            }

            try {
                $html .= $this->_row(_("Alias"), implode("\n", $vc->getAttributeValues('ALIAS'));
            } catch (Horde_Icalendar_Exception $e) {}

            try {
                $birthdays = $vc->getAttributeValues('BDAY');
                $birthday = new Horde_Date($birthdays[0]);
                $html .= $this->_row(
                    _("Birthday"),
                    $birthday->strftime($prefs->getValue('date_format')));
            } catch (Horde_Icalendar_Exception $e) {}

            $photos = $vc->getAllAttributes('PHOTO');
            foreach ($photos as $p => $photo) {
                if (isset($photo['params']['VALUE']) &&
                    Horde_String::upper($photo['params']['VALUE']) == 'URI') {
                    $html .= $this->_row(_("Photo"),
                                         '<img src="' . htmlspecialchars($photo['value']) . '" />',
                                         false);
                } elseif (isset($photo['params']['ENCODING']) &&
                          Horde_String::upper($photo['params']['ENCODING']) == 'B' &&
                          isset($photo['params']['TYPE'])) {
                    if ($browser->hasFeature('datauri') === true ||
                        $browser->hasFeature('datauri') >= strlen($photo['value'])) {
                        $html .= $this->_row(_("Photo"),
                                             '<img src="data:' . htmlspecialchars($photo['params']['TYPE'] . ';base64,' . $photo['value']) . '" />',
                                             false);
                    } elseif ($this->_imageUrl) {
                        $html .= $this->_row(_("Photo"),
                                             '<img src="' . htmlspecialchars($this->_imageUrl->add(array('c' => $i, 'p' => $p))) . '" />',
                                             false);
                    }
                }
            }

            $labels = $vc->getAllAttributes('LABEL');
            foreach ($labels as $label) {
                if (isset($label['params']['TYPE'])) {
                    if (!is_array($label['params']['TYPE'])) {
                        $label['params']['TYPE'] = array($label['params']['TYPE']);
                    }
                } else {
                    $label['params']['TYPE'] = array_keys($label['params']);
                }
                $types = array();
                foreach ($label['params']['TYPE'] as $type) {
                    switch(Horde_String::upper($type)) {
                    case 'HOME':
                        $types[] = _("Home Address");
                        break;

                    case 'WORK':
                        $types[] = _("Work Address");
                        break;

                    case 'DOM':
                        $types[] = _("Domestic Address");
                        break;

                    case 'INTL':
                        $types[] = _("International Address");
                        break;

                    case 'POSTAL':
                        $types[] = _("Postal Address");
                        break;

                    case 'PARCEL':
                        $types[] = _("Parcel Address");
                        break;

                    case 'PREF':
                        $types[] = _("Preferred Address");
                        break;
                    }
                }
                if (!count($types)) {
                    $types = array(_("Address"));
                }
                $html .= $this->_row(implode('/', $types), $label['value']);
            }

            $adrs = $vc->getAllAttributes('ADR');
            foreach ($adrs as $item) {
                if (isset($item['params']['TYPE'])) {
                    if (!is_array($item['params']['TYPE'])) {
                        $item['params']['TYPE'] = array($item['params']['TYPE']);
                    }
                } else {
                    $item['params']['TYPE'] = array_keys($item['params']);
                }

                $address = $item['values'];
                $a = array();
                $a_list = array(
                    Horde_Icalendar_Vcard::ADR_STREET,
                    Horde_Icalendar_Vcard::ADR_LOCALITY,
                    Horde_Icalendar_Vcard::ADR_REGION,
                    Horde_Icalendar_Vcard::ADR_POSTCODE,
                    Horde_Icalendar_Vcard::ADR_COUNTRY
                );

                foreach ($a_list as $val) {
                    if (isset($address[$val])) {
                        $a[] = $address[$val];
                    }
                }

                $types = array();
                foreach ($item['params']['TYPE'] as $type) {
                    switch(Horde_String::upper($type)) {
                    case 'HOME':
                        $types[] = _("Home Address");
                        break;

                    case 'WORK':
                        $types[] = _("Work Address");
                        break;

                    case 'DOM':
                        $types[] = _("Domestic Address");
                        break;

                    case 'INTL':
                        $types[] = _("International Address");
                        break;

                    case 'POSTAL':
                        $types[] = _("Postal Address");
                        break;

                    case 'PARCEL':
                        $types[] = _("Parcel Address");
                        break;

                    case 'PREF':
                        $types[] = _("Preferred Address");
                        break;
                    }
                }
                if (!count($types)) {
                    $types = array(_("Address"));
                }
                $html .= $this->_row(implode('/', $types), implode("\n", $a));
            }

            $numbers = $vc->getAllAttributes('TEL');

            foreach ($numbers as $number) {
                if (isset($number['params']['TYPE'])) {
                    if (!is_array($number['params']['TYPE'])) {
                        $number['params']['TYPE'] = array($number['params']['TYPE']);
                    }
                    foreach ($number['params']['TYPE'] as $type) {
                        $number['params'][Horde_String::upper($type)] = true;
                    }
                }
                if (isset($number['params']['FAX'])) {
                    $html .= $this->_row(_("Fax"), $number['value']);
                } else {
                    if (isset($number['params']['HOME'])) {
                        $html .= $this->_row(_("Home Phone"),
                                             $number['value']);
                    } elseif (isset($number['params']['WORK'])) {
                        $html .= $this->_row(_("Work Phone"),
                                             $number['value']);
                    } elseif (isset($number['params']['CELL'])) {
                        $html .= $this->_row(_("Cell Phone"),
                                             $number['value']);
                    } else {
                        $html .= $this->_row(_("Phone"),
                                             $number['value']);
                    }
                }
            }

            $addresses = $vc->getAllAttributes('EMAIL');
            $emails = array();
            foreach ($addresses as $address) {
                if (isset($address['params']['TYPE'])) {
                    if (!is_array($address['params']['TYPE'])) {
                        $address['params']['TYPE'] = array($address['params']['TYPE']);
                    }
                    foreach ($address['params']['TYPE'] as $type) {
                        $address['params'][Horde_String::upper($type)] = true;
                    }
                }
                $email = '<a href="';
                if ($registry->hasMethod('mail/compose')) {
                    $email .= $registry->call(
                        'mail/compose',
                        array(array('to' => $address['value'])));
                } else {
                    $email .= 'mailto:' . htmlspecialchars($address['value']);
                }
                $email .= '">' . htmlspecialchars($address['value']) . '</a>';
                if (isset($address['params']['PREF'])) {
                    array_unshift($emails, $email);
                } else {
                    $emails[] = $email;
                }
            }

            if (count($emails)) {
                $html .= $this->_row(_("Email"), implode("\n", $emails), false);
            }

            try {
                $title = $vc->getAttributeValues('TITLE');
                $html .= $this->_row(_("Title"), $title[0]);
            } catch (Horde_Icalendar_Exception $e) {}

            try {
                $role = $vc->getAttributeValues('ROLE');
                $html .= $this->_row(_("Role"), $role[0]);
            } catch (Horde_Icalendar_Exception $e) {}

            try {
                $org = $vc->getAttributeValues('ORG');
                $html .= $this->_row(_("Company"), $org[0]);
                if (isset($org[1])) {
                    $html .= $this->_row(_("Department"), $org[1]);
                }
            } catch (Horde_Icalendar_Exception $e) {}

            try {
                $notes = $vc->getAttributeValues('NOTE');
                $html .= $this->_row(_("Notes"), $notes[0]);
            } catch (Horde_Icalendar_Exception $e) {}

            try {
                $url = $vc->getAttributeValues('URL');
                $html .= $this->_row(
                    _("URL"),
                    '<a href="' . htmlspecialchars($url[0])
                        . '" target="_blank">' . htmlspecialchars($url[0])
                        . '</a>',
                    false);
            } catch (Horde_Icalendar_Exception $e) {}
        }

        if ($registry->hasMethod('contacts/import') &&
            $registry->hasMethod('contacts/sources')) {
            $html .= '<tr><td colspan="2" class="smallheader"><form action="'
                . Horde::selfUrl() . '" method="get" name="vcard_import">'
                . Horde_Util::formInput();
            foreach ($_GET as $key => $val) {
                $html .= '<input type="hidden" name="' . htmlspecialchars($key)
                    . '" value="' . htmlspecialchars($val) . '" />';
            }

            $sources = $registry->call('contacts/sources', array(true));
            if (count($sources) > 1) {
                $html .=
                    '<input type="submit" class="button" name="import" value="'
                    . _("Add to address book:") . '" />'
                    . '<label for="add_source" class="hidden">'
                    . _("Address Book") . '</label>'
                    . '<select id="add_source" name="source">';
                foreach ($sources as $key => $label) {
                    $selected = ($key == $prefs->getValue('add_source'))
                        ? ' selected="selected"' : '';
                    $html .= '<option value="' . htmlspecialchars($key) . '"'
                        . $selected . '>' . htmlspecialchars($label)
                        . '</option>';
                }
            } else {
                reset($sources);
                $html .=
                    '<input type="submit" class="button" name="import" value="'
                    . _("Add to my address book") . '" />'
                    . '<input type="hidden" name="source" value="'
                    . htmlspecialchars(key($sources)) . '" />';
            }

            $html .= '</form></td></tr><tr><td>&nbsp;</td></tr>';
        }

        $html .=  '</table>';

        Horde::startBuffer();
        $notification->notify(array('listeners' => 'status'));

        return $this->_renderReturn(
            Horde::endBuffer() . $html,
            'text/html; charset=' . $this->getConfigParam('charset')
        );
    }

    /**
     * TODO
     */
    protected function _row($label, $value, $encode = true)
    {
        if ($encode) {
            $label = htmlspecialchars($label);
            $value = htmlspecialchars($value);
        }
        return '<tr><td class="item" valign="top">' . $label .
            '</td><td class="item" valign="top">' . nl2br($value) .
            "</td></tr>\n";
    }

}
