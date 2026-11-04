<?php

require_once(__DIR__. '\App\check_session.php');

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

$text="";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $unavn = trim($_POST["navn"] ?? "");

    $sql="SELECT username, pass FROM login_orderlist WHERE username=:unavn";
    $stmt = $conn->prepare($sql);

    $stmt ->execute([':unavn'=>$unavn]);
    $brugere=$stmt->fetchAll(PDO::FETCH_ASSOC);

    $updated_pass =0;

    $update_sql="UPDATE login_orderlist SET pass = :hash WHERE username=:username";
    $update_stmt=$conn->prepare($update_sql);


    foreach ($brugere as $bruger){
        $uname=$bruger['username'];
        $rent_password=$bruger['pass'];

        if (strpos($rent_password, '$2y$')!==0){
            $ny_hash=password_hash($rent_password, PASSWORD_DEFAULT);

            $update_stmt->execute([
                ':hash'=>$ny_hash,
                ':username'=>$uname
            ]);

           $text="Hashed adgangskode for ".$uname;
        }
    }
}


?>
<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Opdate password</title>
    <link rel="stylesheet" type="text/css" href="App/Style/stylesheet.css">
</head>
<header>
    <section id="navi">
        <nav>
            <ul>
                <li><a href="orderlist.php">Orderliste</a></li>
                <li><a href="create_user.php">Lav en ny bruger</a></li>
            </ul>
        </nav>
    </section>
</header>
<body>
    <section name="update_password_sec">
        <form method="POST">
            <div class="container">
                <p><?php echo $text?></p>
                <p>Hvis en bruger har en adgangskode som IKKE er hashed, indtast dem her og få det ændret. Brugerne vil aldrig vide det.</p>
                <label for="navn">Brugernavn</label>
                <input type="text" placeholder="Indtast brugernavn" name="navn" required>

                <button type="submit" name="login">Ændre adgangskode til hash</button>
            </div>
        </form>
    </section>
</body>