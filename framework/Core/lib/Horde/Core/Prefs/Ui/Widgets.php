<?php
/**
 * Collection of prefs UI widgets for use with application-specific (a/k/a
 * 'special') configuration.
 *
 * Copyright 2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @package  Core
 */
class Horde_Core_Prefs_Ui_Widgets
{
    /* Source selection widget. */

    /**
     * Code to run on init.
     */
    static public function sourceInit()
    {
        Horde::addScriptFile('sourceselect.js', 'horde');
    }

    /**
     * Create code needed for source selection.
     *
     * Returns data in a form variable named 'sources'. If only one source
     * was originally given, this variable will contain the list of selected
     * values (JSON encoded). If multiple sources were given, this variable
     * will contain a list of arrays; each subarray contains the source name
     * and the list of selected values (JSON encoded).
     *
     * @param array $data  Data items:
     * <pre>
     * 'mainlabel' - (string) Main label.
     * 'selectlabel' - (array) Selected label.
     * 'sourcelabel' - (string) [OPTIONAL] Source selection label.
     * 'sources' - (array) List of sources - keys are source names. Each
     *             source is an array with two entries - selected and
     *             unselected.
     * 'unselectlabel' - (array) Unselected label.
     * </pre>
     *
     * @return string  HTML UI code.
     */
    static public function source($data)
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');

        $t->set('mainlabel', $data['mainlabel']);
        $t->set('selectlabel', $data['selectlabel']);
        $t->set('unselectlabel', $data['unselectlabel']);

        $sources = array();
        foreach ($data['sources'] as $key => $val) {
            $selected = $unselected = array();

            foreach ($val['selected'] as $key2 => $val2) {
                $selected[] = array(
                    'l' => $val2,
                    'v' => $key2
                );
            }

            foreach ($val['unselected'] as $key2 => $val2) {
                $unselected[] = array(
                    'l' => $val2,
                    'v' => $key2
                );
            }

            $sources[$key] = array($selected, $unselected);
        }

        if (count($sources) == 1) {
            $val = reset($sources);
            $t->set('selected', $val[0]);
            $t->set('unselected', $val[1]);
        } else {
            $js = array();
            foreach ($sources as $key => $val) {
                $js[] = array(
                    'selected' => $val[0],
                    'source' => $key,
                    'unselected' => $val[1]
                );
            }
            Horde::addInlineScript(array(
                'HordeSourceSelectPrefs.source_list = ' . Horde_Serialize::serialize($js, Horde_Serialize::JSON, Horde_Nls::getCharset())
            ));
        }

        $t->set('addimg', Horde::img(isset($GLOBALS['nls']['rtl'][$GLOBALS['language']]) ? 'lhand.png' : 'rhand.png', _("Add source")));
        $t->set('removeimg', Horde::img(isset($GLOBALS['nls']['rtl'][$GLOBALS['language']]) ? 'rhand.png' : 'lhand.png', _("Remove source")));

        $t->set('upimg', Horde::img('nav/up.png', _("Move up")));
        $t->set('downimg', Horde::img('nav/down.png', _("Move down")));

        return $t->fetch(HORDE_TEMPLATES . '/prefs/source.html');
    }

    /* Addressbook selection widget. Extends the source widget to handle
     * the special case of addressbook selection. */

    /**
     * Code to run on init for addressbook selection.
     */
    static public function addressbooksInit()
    {
        self::sourceInit();
        Horde::addScriptFile('addressbooksprefs.js', 'horde');
    }

    /**
     * Create code needed for addressbook selection.
     *
     * Returns data in form variables named sources and search_fields.
     * Sources contains the list of selected addressbooks (JSON encoded).
     * search_fields contains a hash containing sources as keys and an array
     * of search fields as the value.
     *
     * @param array $data  Data items:
     * <pre>
     * 'fields' - (array) Hash containing addressbook sources as keys and an
     *            array of search fields as values.
     * 'sources' - (array) List of selected addressbooks.
     * </pre>
     *
     * @return string  HTML UI code.
     */
    static public function addressbooks($data)
    {
        global $prefs, $registry;

        $selected = $unselected = array();
        $out = '';

        if (!$registry->hasMethod('contacts/sources') ||
            $prefs->isLocked('search_sources')) {
            return;
        }

        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        try {
            $readable = $registry->call('contacts/sources');
        } catch (Horde_Exception $e) {
            $readable = array();
        }

        try {
            $writeable = $registry->call('contacts/sources', array(true));
        } catch (Horde_Exception $e) {
            $writeable = array();
        }

        if (count($readable) == 1) {
            // Only one source, no need to display the selection widget
            $data['sources'] = array_keys($readable);
        }

        foreach ($data['sources'] as $source) {
            if (!empty($readable[$source])) {
                $selected[$source] = $readable[$source];
            }
        }

        foreach (array_diff(array_keys($readable), $data['sources']) as $val) {
            $unselected[$val] = $readable[$val];
        }

        if (!empty($selected) || !empty($unselected)) {
            $out = Horde_Core_Prefs_Ui_Widgets::source(array(
                  'mainlabel' => _("Choose the order of address books to search when expanding addresses."),
                  'selectlabel' => _("Selected address books:"),
                  'sources' => array(array(
                      'selected' => $selected,
                      'unselected' => $unselected
                  )),
                  'unselectlabel' => _("Available address books:")
             ));

            $t->set('selected', count($unselected) > 1);

            $js = array();
            foreach (array_keys($readable) as $source) {
                $tmp = $tmpsel = array();

                try {
                    foreach ($registry->call('contacts/fields', array($source)) as $field) {
                        if ($field['search']) {
                            $tmp[] = array(
                                'name' => $field['name'],
                                'label' => $field['label']
                            );
                            if (isset($data['fields'][$source]) &&
                                in_array($field['name'], $data['fields'][$source])) {
                                $tmpsel[] = $field['name'];
                            }
                        }
                    }
                } catch (Horde_Exception $e) {}

                $js[$source] = array(
                    'entries' => $tmp,
                    'selected' => $tmpsel
                );
            }

            Horde::addInlineScript(array(
                'HordeAddressbooksPrefs.fields = ' . Horde_Serialize::serialize($js, Horde_Serialize::JSON, Horde_Nls::getCharset()),
                'HordeAddressbooksPrefs.nonetext = ' . Horde_Serialize::serialize(_("No address book selected."), Horde_Serialize::JSON, Horde_Nls::getCharset())
            ));
        }

        return $out . $t->fetch(HORDE_TEMPLATES . '/prefs/addressbooks.html');
    }

}
