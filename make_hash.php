<?php
// make_hash.php — Burahin mo ito pagkatapos gamitin!
$password = 'staff123';  // ← Palitan mo kung gusto mong ibang password
echo password_hash($password, PASSWORD_DEFAULT);
?>