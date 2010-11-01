<?php
/**
 * @category Horde
 * @package  Crypt
 */

if (!is_executable('/usr/bin/gpg')) {
    echo 'skip No GPG binary at /usr/bin/gpg';
}
