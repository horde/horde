<?php
/**
 * Copyright 1999-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 */

require_once dirname(__FILE__) . '/../lib/Application.php';
Horde_Registry::appInit('horde', array('admin' => true));

$title = _("SQL Shell");
Horde::addScriptFile('stripe.js', 'horde');
require HORDE_TEMPLATES . '/common-header.inc';
require HORDE_TEMPLATES . '/admin/menu.inc';

?>
<div>
<h1 class="header"><?php echo $title ?></h1>
<form name="sqlshell" action="sqlshell.php" method="post">
<?php Horde_Util::pformInput() ?>

<?php

$db = $injector->getInstance('Horde_Db_Adapter');
$q_cache = $session->retrieve('horde', 'sql_query_cache', Horde_Session::TYPE_ARRAY);

if (Horde_Util::getFormData('list-tables')) {
    $description = 'LIST TABLES';
    $result = $db->tables();
    $command = null;
} elseif ($command = trim(Horde_Util::getFormData('sql'))) {
    // Keep a cache of prior queries for convenience.
    if (($key = array_search($command, $q_cache)) !== false) {
        unset($q_cache[$key]);
    }
    $q_cache[] = $command;
    $q_cache = array_slice($q_cache, -20);
    $session->set('horde', 'sql_query_cache', $q_cache);

    // Parse out the query results.
    $result = $db->execute(Horde_String::convertCharset($command, 'UTF-8', $conf['sql']['charset']));
}

if (isset($result)) {
    if ($command) {
        echo '<h1 class="header">' . _("Query") . '</h1><pre class="text">' . htmlspecialchars($command) . '</pre>';
    }

    echo '<h1 class="header">' . _("Results") . '</h1>';

    if (is_object($result)) {
        echo '<table cellspacing="1" class="item striped">';
        $first = true;
        $i = 0;
        while ($row = $result->fetch()) {
            if ($first) {
                echo '<tr>';
                foreach ($row as $key => $val) {
                    echo '<th align="left">' . (!strlen($key) ? '&nbsp;' : htmlspecialchars(Horde_String::convertCharset($key, $conf['sql']['charset'], 'UTF-8'))) . '</th>';
                }
                echo '</tr>';
                $first = false;
            }
            echo '<tr>';
            foreach ($row as $val) {
                echo '<td class="fixed">' . (!strlen($val) ? '&nbsp;' : htmlspecialchars(Horde_String::convertCharset($val, $conf['sql']['charset'], 'UTF-8'))) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    } elseif (is_array($result)) {
        echo '<table cellspacing="1" class="item striped">';
        $first = true;
        $i = 0;
        foreach ($result as $val) {
            if ($first) {
                echo '<tr><th align="left">' . (isset($description) ? htmlspecialchars($description) : '&nbsp;') . '</th></tr>';
                $first = false;
            }
            echo '<tr><td class="fixed">' . (!strlen($val) ? '&nbsp;' : htmlspecialchars(Horde_String::convertCharset($val, $conf['sql']['charset'], 'UTF-8'))) . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<strong>' . _("Success") . '</strong>';
    }
}
?>

<?php if (count($q_cache)): ?>
  <label for="query_cache" class="hidden"><?php echo ("Query cache") ?></label>
  <select id="query_cache" name="query_cache" onchange="document.sqlshell.sql.value = document.sqlshell.query_cache[document.sqlshell.query_cache.selectedIndex].value;">
  <?php foreach ($q_cache as $query): ?>
    <option value="<?php echo htmlspecialchars($query) ?>"><?php echo htmlspecialchars($query) ?></option>
  <?php endforeach; ?>
  </select>
  <input type="button" value="<?php echo _("Paste") ?>" class="button" onclick="document.sqlshell.sql.value = document.sqlshell.query_cache[document.sqlshell.query_cache.selectedIndex].value;">
  <input type="button" value="<?php echo _("Run") ?>" class="button" onclick="document.sqlshell.sql.value = document.sqlshell.query_cache[document.sqlshell.query_cache.selectedIndex].value; document.sqlshell.submit();">
<?php endif; ?>

<label for="sql" class="hidden"><?php echo ("SQL Query") ?></label>
<textarea class="fixed" id="sql" name="sql" rows="10" cols="80">
<?php if (strlen($command)) echo htmlspecialchars($command) ?></textarea>

<input type="submit" class="button" value="<?php echo _("Execute") ?>">
<input type="button" class="button" value="<?php echo _("Clear Query") ?>" onclick="document.sqlshell.sql.value=''">
<?php if (strlen($command)): ?>
<input type="reset" class="button" value="<?php echo _("Restore Last Query") ?>">
<?php endif; ?>
<input type="submit" class="button" name="list-tables" value="<?php echo _("List Tables") ?>">
<?php echo Horde_Help::link('admin', 'admin-sqlshell') ?>

</form>
</div>
<?php

require HORDE_TEMPLATES . '/common-footer.inc';
