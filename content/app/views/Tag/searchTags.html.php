<ul>
<?php foreach ($this->results as $tag_id => $tag_name): ?>
 <li value="<?php echo $this->escape($tag_id) ?>"><?php echo $this->escape($tag_name) ?></li>
<?php endforeach ?>
</ul>
