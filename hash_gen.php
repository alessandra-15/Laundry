<?php
// Change this to your desired password
$new_password = 'password123';

echo "<pre>";
echo "Plain password: <strong>{$new_password}</strong>\n\n";
echo "Copy the line below and paste into phpMyAdmin SQL:\n\n";
echo htmlspecialchars("'" . password_hash($new_password, PASSWORD_DEFAULT) . "'");
echo "</pre>";