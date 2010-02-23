<?php
if (!$prefs->isLocked('default_tasklist')):
    $all_tasklists = Nag::listTasklists();
    $tasklists = array();
    foreach ($all_tasklists as $id => $tasklist) {
        if ($tasklist->get('owner') != Horde_Auth::getAuth() &&
            !empty($GLOBALS['conf']['share']['hidden']) &&
            !in_array($tasklist->getName(), $GLOBALS['display_tasklists'])) {
            continue;
        }
        $tasklists[$id] = $tasklist;
    }
    if (($default_tasklist = $prefs->getValue('default_tasklist')) == null ||
        !isset($tasklists[$default_tasklist])) {
        $default_tasklist = Horde_Auth::getAuth();
    }
?>

<?php echo Horde::label('default_tasklist', _("Your default task list:")) ?><br />
<select id="default_tasklist" name="default_tasklist">
<?php foreach ($tasklists as $id => $tasklist): ?>
    <option value="<?php echo htmlspecialchars($id) ?>"<?php if ($id == $default_tasklist) echo ' selected="selected"' ?>><?php echo htmlspecialchars($tasklist->get('name')) ?></option>
<?php endforeach; ?>
</select><br /><br />
<?php endif; ?>
