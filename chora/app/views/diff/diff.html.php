<table cellspacing="0" class="diff">
<?php foreach ($diff as $section): ?>
<tbody>
<tr>
 <th><?php printf(_("Line %s"), $section['oldline']) ?></th>
 <th><?php printf(_("Line %s"), $section['newline']) ?></th>
</tr>
<?php
foreach ($section['contents'] as $change) {
    if ($this->hasContext() && $change['type'] != 'empty') {
        echo $this->diffContext();
    }

    $method = 'diff' . ucfirst($change['type']);
    echo $this->$method($change);
}

if ($this->hasContext()) {
    echo $this->diffContext();
}
?>
</tbody>
<?php endforeach ?>
</table>
