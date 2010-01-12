<?php

if ($row['attachments']) {
    echo '<br /> <br />' . News::format_attached($id);
}
