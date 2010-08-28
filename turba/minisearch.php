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
    } catch (Turba_Exception $e) {
        $driver = null;
    }

    if ($driver) {
        $criteria['name'] = trim($search);
        $res = $driver->search($criteria);
        if ($res instanceof Turba_List) {
            while ($ob = $res->next()) {
                if ($ob->isGroup()) {
                    continue;
                }
                $att = $ob->getAttributes();
                foreach ($att as $key => $value) {
                    if (!empty($attributes[$key]['type']) &&
                        $attributes[$key]['type'] == 'email') {
                        $results[] = array('name' => $ob->getValue('name'),
                                           'email' => $value,
                                           'url' => $ob->url());
                        break;
                    }
                }
            }
        }
    }
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

        $mail_link = $GLOBALS['registry']->call(
            'mail/compose',
            array(array('to' => addslashes($contact['email']))));
        if (is_a($mail_link, 'PEAR_Error')) {
            $mail_link = 'mailto:' . urlencode($contact['email']);
            $target = '';
        } else {
            $target = strpos($mail_link, 'javascript:') === 0
                ? ''
                : ' target="_parent"';
        }

        echo Horde::link(Horde::applicationUrl($contact['url']),
                        _("View Contact"), '', '_parent')
            . Horde::img('contact.png', _("View Contact")) . '</a> '
            . '<a href="' . $mail_link . '"' . $target . '>'
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
