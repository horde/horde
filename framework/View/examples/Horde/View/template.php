<?php
/**
 * @package View
 */

if ($this->books):
?>

<!-- A table of some books. -->
<table>
    <tr>
        <th>Author</th>
        <th>Title</th>
    </tr>

<?php foreach ($this->books as $key => $val): ?>
    <tr>
<td><?php echo $this->escape($val['author']) ?></td>
<td><?php echo $this->escape($val['title']) ?></td>
    </tr>
<?php endforeach; ?>

</table>

<?php else: ?>
    <p>There are no books to display.</p>
<?php endif; ?>
