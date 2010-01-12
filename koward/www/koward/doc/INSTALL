To complete the installation of Koward for an apache server you need to add a
small configuration section to your web server configuration:

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond   %{REQUEST_FILENAME}  !-d
    RewriteCond   %{REQUEST_FILENAME}  !-f
    RewriteRule ^(.*)$ koward.php [QSA,L]
</IfModule>

This configuration snippet must apply to the "koward/app" directory. The easiest
option is to add an .htaccess file with the content provided above in that
directory.
