<?php
/**
 * Ulaform Fields File
 *
 * This file contains the fields allowed in ulaform and the parameters which
 * can be set for each field. If you wish to disable any particular field you
 * can delete it or comment it out in this file.
 *
 * Generally it is best to leave these as they are.
 */

/* A header field. */
$fields['header']                 = true;

/* A descriptive field. */
$fields['description']            = true;

/* A field to display unaltered html. */
$fields['html']                   = true;

/* A number field. */
$fields['number']                 = true;

/* An integer field. */
$fields['int']                    = true;

/* A comma separated integer array field. */
$fields['intlist']                = true;

/* Plain text field. */
$fields['text']                   = true;

/* A textarea input field. */
$fields['longtext']['params']     = array(
    'rows'       => array('label'    => _("Rows"),
                          'type'     => 'int'),
    'cols'       => array('label'    => _("Columns"),
                          'type'     => 'int')
);

$fields['countedtext']['params']  = array(
    'rows'       => array('label'    => _("Rows"),
                          'type'     => 'int'),
    'cols'       => array('label'    => _("Columns"),
                          'type'     => 'int'),
    'chars'      => array('label'    => _("Characters"),
                          'type'     => 'int')
);

/* A file input field. */
$fields['file']                   = true;

/* A boolean input field. */
$fields['boolean']                = true;

/* A link field. */
$fields['link']['params']         = array(
    'values'     => array('label'    => _("Values"),
                          'type'     => 'stringlist',
                          'required' => true,
                          'desc'     => _("Enter a comma separated list of values."))
);

/* An email input field. */
$fields['email']                  = true;

/* An email input field with a confirm email box. */
$fields['emailconfirm']           = true;

/* A password input field. */
$fields['password']               = true;

/* A password input field with a confirm password box. */
$fields['passwordconfirm']        = true;

/* A selection field. */
$fields['enum']['params']         = array(
    'values'     => array('label'    => _("Values"),
                          'type'     => 'stringlist',
                          'required' => true,
                          'desc'     => _("Enter a comma separated list of values."))
);

/* A multiple selection field. */
$fields['multienum']['params']    = array(
    'values'     => array('label'    => _("Values"),
                          'type'     => 'stringlist',
                          'required' => true,
                          'desc'     => _("Enter a comma separated list of values."))
);

/* A radio selection field. */
$fields['radio']['params']        = array(
    'values'     => array('label'    => _("Values"),
                          'type'     => 'stringlist',
                          'required' => true,
                          'desc'     => _("Enter a comma separated list of values."))
);

/* A set???? field. */
$fields['set']['params']          = array(
    'values'     => array('label'    => _("Values"),
                          'type'     => 'stringlist',
                          'required' => true,
                          'desc'     => _("Enter a comma separated list of values."))
);

/* A date input field. */
$fields['date']                   = true;

/* A time input field. */
$fields['time']                   = true;

/* A month / year input field. */
$fields['monthyear']['params']    = array(
    'startyear'  => array('label'    => _("Start year"),
                          'type'     => 'int'),
    'endyear'    => array('label'    => _("End year"),
                          'type'     => 'int')
);

/* A month, day and year selection input field. */
$fields['monthdayyear']['params'] = array(
    'startyear'  => array('label'    => _("Start year"),
                          'type'     => 'int'),
    'endyear'    => array('label'    => _("End year"),
                          'type'     => 'int'),
    'picker'     => array('label'    => _("Show the date picker"),
                          'type'     => 'boolean'),
    'format_in'  => array('label'    => _("Format used to submit"),
                          'type'     => 'text'),
    'format_out' => array('label'    => _("Format used to display"),
                          'type'     => 'text')
);

/* A field to input a colour or pick from a palette. */
$fields['colorpicker']            = true;

/* A sorter field, to sort an array of values. */
$fields['sorter']['params']       = array(
    'values'     => array('label'    => _("Values"),
                          'type'     => 'stringlist',
                          'required' => true,
                          'desc'     => _("Enter a comma separated list of values."))
);

/* Credit card input. */
$fields['creditcard']             = true;

/* Input of a comma separated string list. */
$fields['stringlist']             = true;

/* Database lookup. */
$fields['dblookup']               = true;
