<?php

$host = 'localhost';
$database = 'forlagetconvallaria_dk_db';
$username = 'root';
$psw = '';

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $psw,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $error) {
    error_log($error->getMessage());
    http_response_code(500);
    exit('Kunne ikke oprette forbindelse til databasen.');
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $unavn = trim($_POST["navn"] ?? "");
    $password = trim($_POST["passw"] ?? "");

    if(!empty($unavn)){
        $login_sql="SELECT username, pass FROM login_orderlist WHERE username=:unavn";
        $stmt=$conn->prepare($login_sql);

        $stmt->execute([':unavn'=>$unavn]);
        $user=$stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['pass'])){
            if(session_status()===PHP_SESSION_NONE){
            session_start();
            }
            $_SESSION['logged_in']=true;
            $_SESSION['username']=$user['username'];

            header("Location: Public\orderlist.php");
            exit;
        }
        else{
            $error="Kunne ikke logge ind.";
        }
    }
} else{
    $error = "Skriv dit username";
}
?>

<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="Public/App/Style/stylesheet.css">
</head>

<body>
    <section name="login_form">
        <form method="POST">
            <div class="container">
                <label for="navn">Brugernavn</label>
                <input type="text" placeholder="Skriv dit brugernavn" name="navn" required>

                <label for="psw">Adgangskode</label>
                <input type="password" placeholder="Skriv din adgangskode" name="passw" required>

                <button type="submit" name="login">Login</button>
            </div>
        </form>
    </section>
</body>