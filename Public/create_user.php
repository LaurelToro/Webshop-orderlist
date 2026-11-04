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

$notif = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $unavn = trim($_POST["navn"] ?? "");
    $plain_psw=trim($_POST["passw"] ?? "");

    if(!empty($unavn)){
        $check_sql="SELECT COUNT(*) FROM login_orderlist WHERE username=:unavn";
        $stmt=$conn->prepare($check_sql);

        $stmt->execute([':unavn'=>$unavn]);
        $exists=$stmt->fetchColumn()>0;

        if(!$exists){
        $password = password_hash($plain_psw, PASSWORD_DEFAULT);
        $create_sql="INSERT INTO login_orderlist(`username`,`pass`) VALUES(:uname,:upass)";
        $stmt=$conn->prepare($create_sql);

        $stmt->execute([':uname'=>$unavn,':upass'=>$password]);
        $notif = ("Oprettede bruger: ".$unavn);
        }else{
            $notif="Brugernavn er allerede i database";
        }

        }
    }
    else{
    $notif = "Skriv dit username";
}
?>

<!DOCTYPE html>
<html lang="da">
<head>
    <meta charset="UTF-8">
    <title>Opret bruger</title>
    <link rel="stylesheet" type="text/css" href="App/Style/stylesheet.css">
</head>
<header>
    <section id="navi">
        <nav>
            <ul>
                <li><a href="update_password.php">Opdater passwords</a></li>
                <li><a href="orderlist.php">Orderliste</a></li>
            </ul>
        </nav>
    </section>
</header>
<body>
    <section name="login_form">
        <form method="POST">
            <div class="container">
                <p><?php echo $notif?></p>
                <label for="navn">Brugernavn</label>
                <input type="text" placeholder="Skriv dit brugernavn" name="navn" required>

                <label for="psw">Adgangskode</label>
                <input type="password" placeholder="Skriv din adgangskode" name="passw" required>

                <button type="submit" name="create">Opret bruger</button>
            </div>
        </form>
    </section>
</body>