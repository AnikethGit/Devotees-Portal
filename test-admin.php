<?php
$mysqli = new mysqli('localhost', 'u701659873_task', '#5PzJjRE', 'u701659873_devoteesportal');
if ($mysqli->connect_errno) {
    die('Connect error: ' . $mysqli->connect_error);
}
$r = $mysqli->query("SELECT id,phone,password_hash FROM users WHERE phone='9999999999'");
if ($r->num_rows == 0) {
    echo "No user row found for 9999999999";
} else {
    $row = $r->fetch_assoc();
    echo "Row found. Verifying password...<br>";
    var_dump(password_verify('admin123', $row['password_hash']));
    if (password_verify('admin123', $row['password_hash'])) {
        echo "Password works.";
    } else {
        echo "Hash/password mismatch.";
    }
}
?>
