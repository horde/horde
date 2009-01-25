<?php

if ($row['attachments']) {
    echo '<br /> <br />' . $news->format_attached($id);
}
