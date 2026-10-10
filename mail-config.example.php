<?php
// Copy to mail-config.php on the server and insert the real password.
// Never commit or publish mail-config.php.
return [
    'host' => 'smtp.ionos.co.uk',
    'port' => 587,
    'username' => 'info@srexsolutions.com',
    'password' => 'REPLACE_WITH_SMTP_PASSWORD',
    'from_email' => 'info@srexsolutions.com',
    'from_name' => 'SREX Solutions Website',
    'to_email' => 'info@srexsolutions.com',
];
