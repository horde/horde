<?php
/**
 * Turba minisearch.php.
 *
 * Copyright 2000-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once __DIR__ . '/lib/Application.php';
Horde_Registry::appInit('turba');

$search = Horde_Util::getFormData('search');
$addressbooks = explode(';', Horde_Util::getFormData('addressbooks'));
$results = array();

foreach ($addressbooks as $addressbook) {
    // Do the search if we have one.
    if (!is_null($search)) {
        try {
            $driver = $injector->getInstance('Turba_Factory_Driver')->create($addressbook);
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
}

$page_output->header(array(
    'body_class' => 'summary'
));

if (count($results)) {
    echo '<ul id="turba_minisearch_results">';
    foreach ($results as $contact) {
        echo '<li class="linedRow">';

        try {
            $mail_link = $registry->call('mail/compose', array(
                array('to' => addslashes($contact['email']))
            ));
        } catch (Horde_Exception $e) {
            $mail_link = 'mailto:' . urlencode($contact['email']);
        }

        echo Horde::link(Horde::url($contact['url']),
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
<?php

$page_output->footer();
