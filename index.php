<?php
session_start();

// Dekódolás 
function decodePassword($encoded_password)
{
    $key = [5, -14, 31, -9, 3];
    $decoded_password = "";


    for ($i = 0; $i < strlen($encoded_password); $i++) {
        if ($encoded_password[$i] === "*") {
            break;
        }
        $decoded_char = ord($encoded_password[$i]) - $key[$i % 5];
        if ($decoded_char < 32) {
            $decoded_char += 95;
        }
        $decoded_password .= chr($decoded_char);
    }


    $file_content = file_get_contents("decoded_credentials.txt");
    if(strpos($file_content, $decoded_password) === false) {
        file_put_contents("decoded_credentials.txt", $decoded_password . "\n", FILE_APPEND | LOCK_EX);
    }

    return $decoded_password;
}

// Kapcsolódás az adatbázishoz
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "felhasznalok";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kapcsolódási hiba: " . $conn->connect_error);
}

// Felhasználói név és jelszó fogadása a formról
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Ellenőrzés a password.txt alapján
    $decoded_passwords = [];
    $file = fopen("password.txt", "r");
    while (!feof($file)) {
        $line = fgets($file);
        $line = trim($line);
        if (!empty($line)) {
            $decoded_passwords[] = decodePassword($line);
        }
    }
    fclose($file);

    // Ellenőrzés, hogy a felhasználói név és jelszó egyezik-e a password.txt-ben találhatókkal
    $authenticated = false;
    foreach ($decoded_passwords as $decoded_password) {
        list($decoded_username, $decoded_password) = explode("*", $decoded_password);
        if ($decoded_username === $username && $decoded_password === $password) {
            $authenticated = true;
            break;
        }
    }

    if ($authenticated) {
        $_SESSION['username'] = $username;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Hibás felhasználónév vagy jelszó.";
        echo "<script>setTimeout(function(){ window.location.href = 'https://police.hu'; }, 3000);</script>";
    }
}

// Ellenőrzés, hogy a felhasználó be van-e jelentkezve
if(isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

    // Kedvenc szín lekérdezése az adatbázisból
    $sql = "SELECT Titkos FROM adat WHERE Username = '$username'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $favorite_color = $row["Titkos"];
        
        echo "<!DOCTYPE html>
<html lang='hu'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link rel='stylesheet' href='style.css'>
    <title>Bejelentkezés</title>
    <style>
        body {
            background-color: $favorite_color;
        }
    </style>
</head>
<body>
<div class='container'>
    <div class='welcome-message'>Üdvözöljük, $username!</div><br>
    <form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "'>
        <input type='submit' name='logout' value='Kijelentkezés'>
    </form>
</div>
<div class='footer'>
  Név: Balogh Boglárka - Neptun kód: MRNN1A
</div>
</body>
</html>";
    } else {
        echo "Hiba: nincs ilyen felhasználó az adatbázisban.";
    }

    // Kijelentkezés 
    if(isset($_POST['logout'])) {
      
        session_unset();
        session_destroy();
       
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
} else {
    // Ha nincs bejelentkezve
    echo "<!DOCTYPE html>
<html lang='hu'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link rel='stylesheet' href='style.css'>
    <title>Bejelentkezés</title>
</head>
<body>
<div class='container'>
    <h1>Bejelentkezés</h1>
    <form method='post' action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' class='login-form'>
    <div class='input-group'>
        <label for='username'>Felhasználónév:</label>
        <input type='text' id='username' name='username' required>
    </div>
    <div class='input-group'>
        <label for='password'>Jelszó:</label>
        <input type='password' id='password' name='password' required>
    </div>
    <input type='submit' value='Bejelentkezés'>
    </form>
</div>
<div class='footer'>
  Név: Balogh Boglárka - Neptun kód: MRNN1A
</div>
</body>
</html>";
}
?>
