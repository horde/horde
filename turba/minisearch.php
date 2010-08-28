<?php
/**
 * Turba minisearch.php.
 *
 * Copyright 2000-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('turba');

$search = Horde_Util::getFormData('search');
$results = array();

// Make sure we have a source.
$source = Horde_Util::getFormData('source', Turba::getDefaultAddressBook());

// Do the search if we have one.
if (!is_null($search)) {
    try {
        $driver = $injector->getInstance('Turba_Driver')->getDriver($source);
        $criteria['name'] = trim($search);
        $res = $driver->search($criteria);

        while ($ob = $res->next()) {
            if ($ob->isGroup()) {
                continue;
            }

            $att = $ob->getAttributes();
            foreach ($att as $key => $value) {
                if (!empty($attributes[$key]['type']) &&
                    ($attributes[$key]['type'] == 'email')) {
                    $results[] = array(
                        'name' => $ob->getValue('name'),
                        'email' => $value,
                        'url' => $ob->url()
                    );
                    break;
                }
            }
        }
    } catch (Turba_Exception $e) {}
}

Horde::addScriptFile('prototype.js', 'horde');
$bodyClass = 'summary';
require TURBA_TEMPLATES . '/common-header.inc';

?>
<?php
if (count($results)) {
    echo '<ul id="turba_minisearch_results">';
    foreach ($results as $contact) {
        echo '<li class="linedRow">';

        try {
            $mail_link = $registry->call('mail/compose', array(
                array('to' => addslashes($contact['email']))
            ));
        } catch (Turba_Exception $e) {
            $mail_link = 'mailto:' . urlencode($contact['email']);
        }

        echo Horde::link(Horde::applicationUrl($contact['url']),
                        _("View Contact"), '', '_parent')
            . Horde::img('contact.png', _("View Contact")) . '</a> '
            . '<a href="' . $mail_link . '">'
            . htmlspecialchars($contact['name'] . ' <' . $contact['email'] . '>')
            . '</a></li>';
    }
    echo '</ul>';
} elseif (!is_null($search)) {
    echo _("No contacts found");
}
?>
<script type="text/javascript">
var status = parent.$('turba_minisearch_searching');
if (status) {
    status.hide();
}
var iframe = parent.$('turba_minisearch_iframe');
if (iframe) {
    iframe.setStyle({
        height: Math.min($('turba_minisearch_results').getHeight(), 150) + 'px'
    });
}
</script>
</body>
</html>
