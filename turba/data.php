<?php
/**
 * Turba data.php.
 *
 * Copyright 2001-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Jan Schneider <jan@horde.org>
 */

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

require_once __DIR__ . '/lib/Application.php';
$app_ob = Horde_Registry::appInit('turba');

if (!$conf['menu']['import_export']) {
    require TURBA_BASE . '/index.php';
    exit;
}

/* If there are absolutely no valid sources, abort. */
if (!$cfgSources) {
    $notification->push(_("No Address Books are currently available. Import and Export is disabled."), 'horde.error');
    $page_output->header();
    $notification->notify(array('listeners' => 'status'));
    $page_output->footer();
    exit;
}

/* Importable file types. */
$file_types = array(
    'csv'      => _("CSV"),
    'tsv'      => _("TSV"),
    'vcard'    => _("vCard"),
    'mulberry' => _("Mulberry Address Book"),
    'pine'     => _("Pine Address Book"),
    'ldif'     => _("LDIF Address Book")
);

/* Templates for the different import steps. */
$templates = array(
    Horde_Data::IMPORT_FILE => array(TURBA_TEMPLATES . '/data/export.inc'),
    Horde_Data::IMPORT_CSV => array($registry->get('templates', 'horde') . '/data/csvinfo.inc'),
    Horde_Data::IMPORT_TSV => array($registry->get('templates', 'horde') . '/data/tsvinfo.inc'),
    Horde_Data::IMPORT_MAPPED => array($registry->get('templates', 'horde') . '/data/csvmap.inc'),
    Horde_Data::IMPORT_DATETIME => array($registry->get('templates', 'horde') . '/data/datemap.inc')
);

/* Initial values. */
$vars = $injector->getInstance('Horde_Variables');
$import_step = $vars->get('import_step', 0) + 1;
$next_step = Horde_Data::IMPORT_FILE;
$app_fields = $bad_charset = $time_fields = array();
$error = false;
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
$param = array(
    'time_fields' => $time_fields,
    'file_types'  => $file_types,
    'import_mapping' => array_merge(
        $app_ob->getOutlookMapping(),
        $import_mapping
    )
);
if (in_array($vars->import_format, array('mulberry', 'pine'))) {
    $vars->import_format = 'tsv';
}
if ($vars->actionID != 'select') {
    array_unshift($templates[Horde_Data::IMPORT_FILE], TURBA_TEMPLATES . '/data/import.inc');
}

$storage = $injector->getInstance('Horde_Core_Data_Storage');

/* Loop through the action handlers. */
switch ($vars->actionID) {
case Horde_Data::IMPORT_FILE:
    try {
        $driver = $injector->getInstance('Turba_Factory_Driver')->create($vars->dest);
    } catch (Horde_Exception $e) {
        $notification->push($e, 'horde.error');
        $error = true;
        break;
    }

    /* Check permissions. */
    $max_contacts = Turba::getExtendedPermission($driver, 'max_contacts');
    if (($max_contacts !== true) && ($max_contacts <= count($driver))) {
        Horde::permissionDeniedError(
            'turba',
            'max_contacts',
            sprintf(_("You are not allowed to create more than %d contacts in \"%s\"."), $max_contacts, $driver->title)
        );
        $error = true;
    } else {
        $storage->set('target', $vars->dest);
        $storage->set('purge', $vars->purge);
    }
    break;

case Horde_Data::IMPORT_MAPPED:
case Horde_Data::IMPORT_DATETIME:
    foreach ($cfgSources[$storage->get('target')]['map'] as $field => $null) {
        if (substr($field, 0, 2) != '__' && !is_array($null)) {
            switch ($attributes[$field]['type']) {
            case 'monthyear':
            case 'monthdayyear':
                $time_fields[$field] = 'date';
                break;

            case 'time':
                $time_fields[$field] = 'time';
                break;
            }
        }
    }
    $param['time_fields'] = $time_fields;
    break;
}

if (!$error && $vars->import_format) {
    // TODO
    try {
        switch ($vars->import_format) {
        case 'ldif':
            $data = new Turba_Data_Ldif(array(
                'browser' => $injector->getInstance('Horde_Browser'),
                'cleanup' => array($app_ob, 'cleanupData'),
                'vars' => $vars
            ));
            break;

        case 'csv':
            $param['check_charset'] = true;
            // Fall-through

        default:
            $data = $injector->getInstance('Horde_Core_Factory_Data')->create($vars->import_format, array(
                'cleanup' => array($app_ob, 'cleanupData'),
            ));
            break;
        }
    } catch (Horde_Exception $e) {
        $notification->push(_("This file format is not supported."), 'horde.error');
        $data = null;
        $next_step = Horde_Data::IMPORT_FILE;
    }

    if ($data) {
        try {
            try {
                $next_step = $data->nextStep($vars->actionID, $param);

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
            } catch (Horde_Data_Exception_Charset $e) {
                if ($e->badCharset != 'UTF-8') {
                    $bad_charset[] = $e->badCharset;
                    throw $e;
                }

                $param['charset'] = 'windows-1252';
                try {
                    $next_step = $data->nextStep($vars->actionID, $param);
                } catch (Horde_Data_Exception_Charset $e) {
                    $bad_charset = array('UTF-8', 'windows-1252');
                    throw $e;
                }
            }
        } catch (Horde_Data_Exception $e) {
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
    $dest = $storage->get('target');
    try {
        $driver = $injector->getInstance('Turba_Factory_Driver')->create($dest);
    } catch (Horde_Exception $e) {
        $notification->push($e, 'horde.error');
        $driver = null;
    }

    if (!count($next_step)) {
        $notification->push(sprintf(_("The %s file didn't contain any contacts."), $file_types[$storage->get('format')]), 'horde.error');
    } elseif ($driver) {
        /* Purge old address book if requested. */
        if ($storage->get('purge')) {
            try {
                $driver->deleteAll();
                $notification->push(_("Address book successfully purged."), 'horde.success');
            } catch (Turba_Exception $e) {
                $notification->push(sprintf(_("The address book could not be purged: %s"), $e->getMessage()), 'horde.error');
            }
        }

        $error = false;
        $imported = 0;
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

                        $rfc822 = $injector->getInstance('Horde_Mail_Rfc822');
                        try {
                            $row[$field] = strval($rfc822->parseAddressList($row[$field], array(
                                'limit' => $allow_multi ? 0 : 1
                            )));
                        } catch (Horde_Mail_Exception $e) {
                            $row[$field] = '';
                        }
                    }
                }
                $row['__owner'] = $driver->getContactOwner();

                try {
                    $driver->add($row);
                    $imported++;
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
        if (!$error && $imported) {
            $notification->push(sprintf(_("%s file successfully imported."),
                                        $file_types[$storage->get('format')]), 'horde.success');
        }
    }
    $next_step = $data->cleanup();
}

switch ($next_step) {
case Horde_Data::IMPORT_MAPPED:
case Horde_Data::IMPORT_DATETIME:
    foreach ($cfgSources[$storage->get('target')]['map'] as $field => $null) {
        if (substr($field, 0, 2) != '__' && !is_array($null)) {
            $app_fields[$field] = $attributes[$field]['label'];
        }
    }
    break;
}

$page_output->header(array(
    'title' => _("Import/Export Address Books")
));
$notification->notify(array('listeners' => 'status'));

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
    $charsets = array();

    if (!empty($bad_charset)) {
        $charsets = $registry->nlsconfig->encodings_sort;
        foreach ($registry->nlsconfig->charsets as $charset) {
            if (!isset($charsets[$charset]) &&
                !in_array($charset, $bad_charset)) {
                $charsets[$charset] = $charset;
            }
        }
        $my_charset = $GLOBALS['registry']->getLanguageCharset();
    }
}

foreach ($templates[$next_step] as $template) {
    require $template;
}

$page_output->footer();
