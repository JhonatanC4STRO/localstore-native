<?php
require_once "../../config/conexion.php";?>

<form action="../../controller/process_register.php" method="POST">
    <input type="text" name="full_name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="save">Register</button>
</form>
