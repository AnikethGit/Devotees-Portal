<?php
$hashInDb = '$2y$10$wfVHIF2.LZwp/bum/U1R6OvxPnIa83A7QMODXVoYyMFIQyulZ.j/C';
echo 'Test for admin123: ';
var_dump(password_verify('admin123', $hashInDb)); // should be true
?>
