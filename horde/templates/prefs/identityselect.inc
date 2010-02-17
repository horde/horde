<?php if ($prefs->isLocked('default_identity')): ?>
<input type="hidden" name="identity" value="<?php echo intval($GLOBALS['identity']->getDefault()) ?>" />
<?php else: ?>
<?php
    $identities = $GLOBALS['identity']->getAll('id');
    $default_identity = $GLOBALS['identity']->getDefault();
    $members = $prefGroups['identities']['members'];

    $out = array();

    for ($i = 0, $icnt = count($identities); $i < $icnt; ++$i) {
        $tmp = array();
        foreach ($members as $member) {
            if ($member == 'default_identity' ||
                !empty($_prefs[$member]['locked']) ||
                $_prefs[$member]['type'] == 'special' ||
                $_prefs[$member]['type'] == 'link') {
                continue;
            }

            $val = $GLOBALS['identity']->getValue($member, $i);
            switch ($_prefs[$member]['type']) {
            case 'checkbox':
                $val2 = $val ? 'true' : 'false';
                break;

            case 'number':
                $val2 = intval($val);
                break;

            case 'textarea':
                if (is_array($val)) {
                    $val = implode("\n", $val);
                }
                // Fall-through

            default:
                $val2 = Horde_String::convertCharset($val, Horde_Nls::getCharset(), 'UTF-8');
            }

            $tmp[] = array(
                $member,
                $_prefs[$member]['type'],
                $val2
            );
        }

        $out[] = $tmp;
    }

    Horde::addScriptFile('identityselect.js', 'horde');
    echo Horde::wrapInlineScript(array(
        'IdentitySelect.identities = ' . Horde_Serialize::serialize($out, Horde_Serialize::JSON)
    ));
?>
<div>
 <?php echo Horde::label('identity', _("Select the identity you want to change:")) ?>
</div>
<div>
 <select name="identity" id="identity">
  <option value="-2" selected="selected"><?php echo _("None") ?></option>
  <option value="-1"><?php echo _("Create a new one") ?></option>
<?php for ($i = 0, $icnt = count($identities); $i < $icnt; ++$i): ?>
  <option value="<?php echo $i ?>"<?php if ($i == $default_identity) echo ' selected="selected"' ?>><?php echo htmlspecialchars($identities[$i]) ?></option>
<?php endfor; ?>
 </select>
</div>
<?php endif; ?>
