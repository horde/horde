<?php
/**
 * This file defines the templates used in various parts of Whups.
 *
 * IMPORTANT: Local overrides should be placed in templates.local.php, or
 * templates-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 *
 * Hopefully this will all migrate to a database in the future, but
 * for now, this is it.
 *
 * More docs coming as this gets fleshed out and used more.
 */

$_templates['html-simple'] = array(
    'type' => 'searchresults',
    'filename' => 'report.html',
    'name' => _("Simple HTML Report"),
    'sortby' => array('type_name', 'timestamp'),
    'sortdir' => array(0, 1),
    'template' => '<table>
<tr>
  <th>#</th>
  <th>Type</th>
  <th>Owners</th>
  <th>Open Date</th>
  <th>Description</th>
</tr>

<loop:tickets>
<tr>
  <td><a href="<tag:tickets.link />"><tag:tickets.id /></a></td>
  <td><tag:tickets.type_name /></td>
  <td><tag:tickets.owner_name /></td>
  <td><tag:tickets.date_created /></td>
  <td><tag:tickets.summary /></td>
</tr>
</loop:tickets>

</table>'
);

$_templates['csv'] = array(
    'type' => 'searchresults',
    'name' => _("Comma Separated Values (CSV file)"),
    'filename' => 'report.csv',
    'callback' => '_csvQuote',
    'template' => 'ID,Summary,State,Type,Priority,Queue,Version,Owners,Created,Assigned,Resolved<loop:tickets>
<tag:tickets.id />,<tag:tickets.summary />,<tag:tickets.state_name />,<tag:tickets.type_name />,<tag:tickets.priority_name />,<tag:tickets.queue_name />,<tag:tickets.version_name />,<tag:tickets.owner_name />,<tag:tickets.date_created />,<tag:tickets.date_assigned />,<tag:tickets.date_resolved />
</loop:tickets>
'
);

if (!function_exists('_csvQuote')) {
    function _csvQuote(&$data, $key)
    {
        if (strpos($data, ',') !== false) {
            $data = '"' . str_replace('"', '\"', $data) . '"';
        }
    }
}
