<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    echo "<p>Hash gerado para a senha '$password':</p>";
    echo "<pre>$hashed_password</pre>";
    echo "<p>Copie o hash acima para usar no SQL.</p>";
} else {
    ?>
    <form method="POST">
        <label>Digite a senha para gerar o hash:</label>
        <input type="text" name="password" required>
        <input type="submit" value="Gerar Hash">
    </form>
    <?php
}
?>