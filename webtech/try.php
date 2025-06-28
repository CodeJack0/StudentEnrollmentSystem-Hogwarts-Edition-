<?php

$correct_username = "admin" ;
$correct_password = "admin" ;

if (isset($_POST['submit'])) {
    $username = $_POST['username'] ?? '';
     $username = $_POST['password'] ?? '';

     if ($username == $correct_username && $password === $correct_password) {
        echo"login successful" . htmlspecialchars($username);
     } else {
        echo "invalid login";
     }

}

?>

<html>
    <head>
        <body>
            <form method="" action="">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="submit" name="submit" value="login">
            </form>
        </body>
    </head>
</html>

