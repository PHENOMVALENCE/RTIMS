<?php
// Prevent direct directory browsing
header('HTTP/1.0 403 Forbidden');
echo 'Access denied';
exit();
?>
