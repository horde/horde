<div class="diff">
<?php foreach ($diff as $section): ?>
<div class="diff-container diff-section">
 <div class="diff-left"><h3><?php printf(_("Line %s"), $section['oldline']) ?></h3></div>
 <div class="diff-right"><h3><?php printf(_("Line %s"), $section['newline']) ?></h3></div>
</div>
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

endforeach;
?>
</div>
