<?php
/**
 * Turba data.php.
 *
 * Copyright 2001-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Jan Schneider <jan@horde.org>
 */

function _cleanupData()
{
    $GLOBALS['import_step'] = 1;
    return Horde_Data::IMPORT_FILE;
}

/**
 * Remove empty attributes from attributes array.
 *
 * @param mixed $val  Value from attributes array.
 *
 * @return boolean  Boolean used by array_filter.
 */
function _emptyAttributeFilter($var)
{
    if (!is_array($var)) {
        return ($var != '');
    }

    foreach ($var as $v) {
        if ($v == '') {
            return false;
        }
    }

    return true;
}

/**
 * Static function to make a given email address rfc822 compliant.
 *
 * @param string $address  An email address.
 * @param boolean $allow_multi  Allow multiple email addresses.
 *
 * @return string  The RFC822-formatted email address.
 */
function _getBareEmail($address, $allow_multi = false)
{
    // Empty values are still empty.
    if (!$address) {
        return $address;
    }

    $rfc822 = new Horde_Mail_Rfc822();

    // Split multiple email addresses
    if ($allow_multi) {
        $addrs = Horde_Mime_Address::explode($address);
    } else {
        $addrs = array($address);
    }

    $result = array();
    foreach ($addrs as $addr) {
        $addr = trim($addr);

        if ($rfc822->validateMailbox($addr)) {
            $result[] = Horde_Mime_Address::writeAddress($addr->mailbox, $addr->host);
        }
    }

    return implode(', ', $result);
}

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

if (!$conf['menu']['import_export']) {
    require TURBA_BASE . '/index.php';
    exit;
}

/* If there are absolutely no valid sources, abort. */
if (!$cfgSources) {
    $notification->push(_("No Address Books are currently available. Import and Export is disabled."), 'horde.error');
    require $registry->get('templates', 'horde') . '/common-header.inc';
    require TURBA_TEMPLATES . '/menu.inc';
    require $registry->get('templates', 'horde') . '/common-footer.inc';
    exit;
}

/* Importable file types. */
$file_types = array('csv'      => _("CSV"),
                    'tsv'      => _("TSV"),
                    'vcard'    => _("vCard"),
                    'mulberry' => _("Mulberry Address Book"),
                    'pine'     => _("Pine Address Book"),
                    'ldif'     => _("LDIF Address Book"));

/* Templates for the different import steps. */
$templates = array(
    Horde_Data::IMPORT_FILE => array(TURBA_TEMPLATES . '/data/export.inc'),
    Horde_Data::IMPORT_CSV => array($registry->get('templates', 'horde') . '/data/csvinfo.inc'),
    Horde_Data::IMPORT_TSV => array($registry->get('templates', 'horde') . '/data/tsvinfo.inc'),
    Horde_Data::IMPORT_MAPPED => array($registry->get('templates', 'horde') . '/data/csvmap.inc'),
    Horde_Data::IMPORT_DATETIME => array($registry->get('templates', 'horde') . '/data/datemap.inc')
);

/* Initial values. */
$import_step = Horde_Util::getFormData('import_step', 0) + 1;
$actionID = Horde_Util::getFormData('actionID');
$next_step = Horde_Data::IMPORT_FILE;
$app_fields = $time_fields = array();
$error = false;
$outlook_mapping = array(
    'Title' => 'namePrefix',
    'First Name' => 'firstname',
    'Middle Name' => 'middlenames',
    'Last Name' => 'lastname',
    'Nickname' => 'nickname',
    'Suffix' => 'nameSuffix',
    'Company' => 'company',
    'Department' => 'department',
    'Job Title' => 'title',
    'Business Street' => 'workStreet',
    'Business City' => 'workCity',
    'Business State' => 'workProvince',
    'Business Postal Code' => 'workPostalCode',
    'Business Country' => 'workCountry',
    'Home Street' => 'homeStreet',
    'Home City' => 'homeCity',
    'Home State' => 'homeProvince',
    'Home Postal Code' => 'homePostalCode',
    'Home Country' => 'homeCountry',
    'Business Fax' => 'workFax',
    'Business Phone' => 'workPhone',
    'Home Phone' => 'homePhone',
    'Mobile Phone' => 'cellPhone',
    'Pager' => 'pager',
    'Anniversary' => 'anniversary',
    'Assistant\'s Name' => 'assistant',
    'Birthday' => 'birthday',
    'Business Address PO Box' => 'workPOBox',
    'Categories' => 'category',
    'Children' => 'children',
    'E-mail Address' => 'email',
    'Home Address PO Box' => 'homePOBox',
    'Initials' => 'initials',
    'Internet Free Busy' => 'freebusyUrl',
    'Language' => 'language',
    'Notes' => 'notes',
    'Profession' => 'role',
    'Office Location' => 'office',
    'Spouse' => 'spouse',
    'Web Page' => 'website',
);
$import_mapping = array(
    'e-mail' => 'email',
    'homeaddress' => 'homeAddress',
    'businessaddress' => 'workAddress',
    'homephone' => 'homePhone',
    'businessphone' => 'workPhone',
    'mobilephone' => 'cellPhone',
    'businessfax' => 'fax',
    'jobtitle' => 'title',
    'internetfreebusy' => 'freebusyUrl',

    // Entourage on MacOS
    'Dept' => 'department',
    'Work Street Address' => 'workStreet',
    'Work City' => 'workCity',
    'Work State' => 'workProvince',
    'Work Zip' => 'workPostalCode',
    'Work Country/Region' => 'workCountry',
    'Home Street Address' => 'homeStreet',
    'Home City' => 'homeCity',
    'Home State' => 'homeProvince',
    'Home Zip' => 'homePostalCode',
    'Home Country/Region' => 'homeCountry',
    'Work Fax' => 'workFax',
    'Work Phone 1' => 'workPhone',
    'Home Phone 1' => 'homePhone',
    'Instant Messaging 1' => 'instantMessenger',

    // Thunderbird
    'Primary Email' => 'email',
    'Fax Number' => 'fax',
    'Pager Number' => 'pager',
    'Mobile Number' => 'Mobile Phone',
    'Home Address' => 'homeStreet',
    'Home ZipCode' => 'homePostalCode',
    'Work Address' => 'workStreet',
    'Work ZipCode' => 'workPostalCode',
    'Work Country' => 'workCountry',
    'Work Phone' => 'workPhone',
    'Organization' => 'company',
    'Web Page 1' => 'website',
);
$param = array('time_fields' => $time_fields,
               'file_types'  => $file_types,
               'import_mapping' => array_merge($outlook_mapping, $import_mapping));
$import_format = Horde_Util::getFormData('import_format', '');
if ($import_format == 'mulberry' || $import_format == 'pine') {
    $import_format = 'tsv';
}
if ($actionID != 'select') {
    array_unshift($templates[Horde_Data::IMPORT_FILE], TURBA_TEMPLATES . '/data/import.inc');
}

/* Loop through the action handlers. */
switch ($actionID) {
case 'export':
    $sources = array();
    if (Horde_Util::getFormData('selected')) {
        foreach (Horde_Util::getFormData('objectkeys') as $objectkey) {
            list($source, $key) = explode(':', $objectkey, 2);
            if (!isset($sources[$source])) {
                $sources[$source] = array();
            }
            $sources[$source][] = $key;
        }
    } else {
        $source = Horde_Util::getFormData('source');
        if (!isset($source) && !empty($cfgSources)) {
            reset($cfgSources);
            $source = key($cfgSources);
        }
        $sources[$source] = array();
    }

    $exportType = Horde_Util::getFormData('exportID');
    $vcard = $exportType == Horde_Data::EXPORT_VCARD ||
        $exportType == 'vcard30';
    if ($vcard) {
        $version = $exportType == 'vcard30' ? '3.0' : '2.1';
    }

    $data = array();
    $all_fields = array();
    foreach ($sources as $source => $objectkeys) {
        /* Create a Turba storage instance. */
        try {
            $driver = $injector->getInstance('Turba_Driver')->getDriver($source);
        } catch (Turba_Exception $e) {
            $notification->push($e, 'horde.error');
            $error = true;
            break;
        }

        /* Get the full, sorted contact list. */
        try {
            $results = count($objectkeys)
                ? $driver->getObjects($objectkeys)
                : $driver->search(array())->objects;
        } catch (Turba_Exception $e) {
            $notification->push(sprintf(_("Failed to search the directory: %s"), $e->getMessage()), 'horde.error');
            $error = true;
            break;
        }

        $fields = array_keys($driver->map);
        $all_fields = array_merge($all_fields, $fields);
        $params = $driver->getParams();
        foreach ($results as $ob) {
            if ($vcard) {
                $data[] = $driver->tovCard($ob, $version, null, true);
            } else {
                $row = array();
                foreach ($fields as $field) {
                    if (substr($field, 0, 2) != '__') {
                        $attribute = $ob->getValue($field);
                        if ($attributes[$field]['type'] == 'date') {
                            $row[$field] = strftime('%Y-%m-%d', $attribute);
                        } elseif ($attributes[$field]['type'] == 'time') {
                            $row[$field] = strftime('%R', $attribute);
                        } elseif ($attributes[$field]['type'] == 'datetime') {
                            $row[$field] = strftime('%Y-%m-%d %R', $attribute);
                        } else {
                        $row[$field] = Horde_String::convertCharset($attribute, 'UTF-8', $params['charset']);
                        }
                    }
                }
                $data[] = $row;
            }
        }
    }
    if (!count($data)) {
        $notification->push(_("There were no addresses to export."), 'horde.message');
        $error = true;
        break;
    }

    /* Make sure that all rows have the same columns if exporting from
     * different sources. */
    if (!$vcard && count($sources) > 1) {
        for ($i = 0; $i < count($data); $i++) {
            foreach ($all_fields as $field) {
                if (!isset($data[$i][$field])) {
                    $data[$i][$field] = '';
                }
            }
        }
    }

    switch ($exportType) {
    case Horde_Data::EXPORT_CSV:
        $injector->getInstance('Horde_Core_Factory_Data')->create('Csv', array('cleanup' => '_cleanupData'))->exportFile(_("contacts.csv"), $data, true);
        exit;

    case Horde_Data::EXPORT_OUTLOOKCSV:
        $injector->getInstance('Horde_Core_Factory_Data')->create('Outlookcsv', array('cleanup' => '_cleanupData'))->exportFile(_("contacts.csv"), $data, true, array_flip($outlook_mapping));
        exit;

    case Horde_Data::EXPORT_TSV:
        $injector->getInstance('Horde_Core_Factory_Data')->create('Tsv', array('cleanup' => '_cleanupData'))->exportFile(_("contacts.tsv"), $data, true);
        exit;

    case Horde_Data::EXPORT_VCARD:
    case 'vcard30':
        $injector->getInstance('Horde_Core_Factory_Data')->create('Vcard', array('cleanup' => '_cleanupData'))->exportFile(_("contacts.vcf"), $data, true);
        exit;

    case 'ldif':
        $ldif = new Turba_Data_Ldif(
            array('browser' => $this->_injector->getInstance('Horde_Browser'),
                  'vars' => Horde_Variables::getDefaultVariables(),
                  'cleanup' => '_cleanupData'));
        $ldif->exportFile(_("contacts.ldif"), $data, true);
        exit;
    }
    break;

case Horde_Data::IMPORT_FILE:
    $dest = Horde_Util::getFormData('dest');
    try {
        $driver = $injector->getInstance('Turba_Driver')->getDriver($dest);
    } catch (Turba_Exception $e) {
        $notification->push($e, 'horde.error');
        $error = true;
        break;
    }

    /* Check permissions. */
    $max_contacts = Turba::getExtendedPermission($driver, 'max_contacts');
    if ($max_contacts !== true &&
        $max_contacts <= count($driver)) {
        Horde::permissionDeniedError(
            'turba',
            'max_contacts',
            sprintf(_("You are not allowed to create more than %d contacts in \"%s\"."), $max_contacts, $driver->title)
        );
        $error = true;
        break;
    }

    $session->set('horde', 'import_data/target', $dest);
    $session->set('horde', 'import_data/purge', Horde_Util::getFormData('purge'));
    break;

case Horde_Data::IMPORT_MAPPED:
case Horde_Data::IMPORT_DATETIME:
    foreach ($cfgSources[$session->get('horde', 'import_data/target')]['map'] as $field => $null) {
        if (substr($field, 0, 2) != '__' && !is_array($null)) {
            if ($attributes[$field]['type'] == 'monthyear' ||
                $attributes[$field]['type'] == 'monthdayyear') {
                $time_fields[$field] = 'date';
            } elseif ($attributes[$field]['type'] == 'time') {
                $time_fields[$field] = 'time';
            }
        }
    }
    $param['time_fields'] = $time_fields;
    break;
}

if (!$error && !empty($import_format)) {
    // TODO
    try {
        if ($import_format == 'ldif') {
            $data = new Turba_Data_Ldif(array(
                'browser' => $this->_injector->getInstance('Horde_Browser'),
                'vars' => Horde_Variables::getDefaultVariables(),
                'cleanup' => '_cleanupData'
            ));
        } else {
            $data = $injector->getInstance('Horde_Core_Factory_Data')->create($import_format, array('cleanup' => '_cleanupData'));
        }
    } catch (Turba_Exception $e) {
        $notification->push(_("This file format is not supported."), 'horde.error');
        $data = null;
        $next_step = Horde_Data::IMPORT_FILE;
    }

    if ($data) {
        try {
            $next_step = $data->nextStep($actionID, $param);

            /* Raise warnings if some exist. */
            if (method_exists($data, 'warnings')) {
                $warnings = $data->warnings();
                if (count($warnings)) {
                    foreach ($warnings as $warning) {
                        $notification->push($warning, 'horde.warning');
                    }
                    $notification->push(_("The import can be finished despite the warnings."), 'horde.message');
                }
            }
        } catch (Turba_Exception $e) {
            $notification->push($e, 'horde.error');
            $next_step = $data->cleanup();
        }
    }
}

/* We have a final result set. */
if (is_array($next_step)) {
    /* Create a category manager. */
    $cManager = new Horde_Prefs_CategoryManager();
    $categories = $cManager->get();

    /* Create a Turba storage instance. */
    $dest = $session->get('horde', 'import_data/target');
    try {
        $driver = $injector->getInstance('Turba_Driver')->getDriver($dest);
    } catch (Turba_Exception $e) {
        $notification->push($e, 'horde.error');
        $driver = null;
    }

    if (!count($next_step)) {
        $notification->push(sprintf(_("The %s file didn't contain any contacts."),
                                    $file_types[$session->get('horde', 'import_data/format')]), 'horde.error');
    } elseif ($driver) {
        /* Purge old address book if requested. */
        if ($session->get('horde', 'import_data/purge')) {
            try {
                $driver->deleteAll();
                $notification->push(_("Address book successfully purged."), 'horde.success');
            } catch (Turba_Exception $e) {
                $notification->push(sprintf(_("The address book could not be purged: %s"), $e->getMessage()), 'horde.error');
            }
        }

        $error = false;
        foreach ($next_step as $row) {
            if ($row instanceof Horde_Icalendar_Vcard) {
                $row = $driver->toHash($row);
            }

            /* Don't search for empty attributes. */
            try {
                $result = $driver->search(array_filter($row, '_emptyAttributeFilter'));
            } catch (Turba_Exception $e) {
                $notification->push($e, 'horde.error');
                $error = true;
                break;
            }

            if (count($result)) {
                $result->reset();
                $object = $result->next();
                $notification->push(sprintf(_("\"%s\" already exists and was not imported."),
                                            $object->getValue('name')), 'horde.message');
            } else {
                /* Check for, and validate, any email fields */
                foreach (array_keys($row) as $field) {
                    if ($attributes[$field]['type'] == 'email') {
                        $allow_multi = is_array($attributes[$field]['params']) &&
                            !empty($attributes[$field]['params']['allow_multi']);
                        $row[$field] = _getBareEmail($row[$field], $allow_multi);
                    }
                }
                $row['__owner'] = $driver->getContactOwner();

                try {
                    $driver->add($row);
                } catch (Turba_Exception $e) {
                    $notification->push(sprintf(_("There was an error importing the data: %s"), $e->getMessage()), 'horde.error');
                    $error = true;
                    break;
                }

                if (!empty($row['category']) &&
                    !in_array($row['category'], $categories)) {
                    $cManager->add($row['category']);
                    $categories[] = $row['category'];
                }
            }
        }
        if (!$error) {
            $notification->push(sprintf(_("%s file successfully imported."),
                                        $file_types[$session->get('horde', 'import_data/format')]), 'horde.success');
        }
    }
    $next_step = $data->cleanup();
}

switch ($next_step) {
case Horde_Data::IMPORT_MAPPED:
case Horde_Data::IMPORT_DATETIME:
    foreach ($cfgSources[$session->get('horde', 'import_data/target')]['map'] as $field => $null) {
        if (substr($field, 0, 2) != '__' && !is_array($null)) {
            $app_fields[$field] = $attributes[$field]['label'];
        }
    }
    break;
}

$title = _("Import/Export Address Books");
require $registry->get('templates', 'horde') . '/common-header.inc';
require TURBA_TEMPLATES . '/menu.inc';

$default_source = $prefs->getValue('default_dir');
if ($next_step == Horde_Data::IMPORT_FILE) {
    /* Build the directory sources select widget. */
    $unique_source = '';
    $source_options = array();
    foreach (Turba::getAddressBooks() as $key => $entry) {
        if (!empty($entry['export'])) {
            $source_options[] = '<option value="' . htmlspecialchars($key) . '">' .
                htmlspecialchars($entry['title']) . "</option>\n";
            $unique_source = $key;
        }
    }

    /* Build the directory destination select widget. */
    $unique_dest = '';
    $dest_options = array();
    $hasWriteable = false;
    foreach (Turba::getAddressBooks(Horde_Perms::EDIT) as $key => $entry) {
        $selected = ($key == $default_source) ? ' selected="selected"' : '';
        $dest_options[] = '<option value="' . htmlspecialchars($key) . '" ' . $selected . '>' .
            htmlspecialchars($entry['title']) . "</option>\n";
        $unique_dest = $key;
        $hasWriteable = true;
    }

    if (!$hasWriteable) {
        array_shift($templates[$next_step]);
    }

    /* Build the charset options. */
    $charsets = $registry->nlsconfig['encodings'];
    asort($charsets);
    $all_charsets = $registry->nlsconfig['charsets'];
    natcasesort($all_charsets);
    foreach ($all_charsets as $charset) {
        if (!isset($charsets[$charset])) {
            $charsets[$charset] = $charset;
        }
    }
    $my_charset = $GLOBALS['registry']->getLanguageCharset();
}

foreach ($templates[$next_step] as $template) {
    require $template;
}
require $registry->get('templates', 'horde') . '/common-footer.inc';
