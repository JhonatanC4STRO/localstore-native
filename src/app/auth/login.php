<?php
require_once "../../config/conexion.php";?>

<form action="../../controller/process_login.php" method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="save">Login</button>
</form>