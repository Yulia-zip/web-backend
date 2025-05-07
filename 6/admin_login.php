<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// $pass = '4643907'; 
// $user = 'web_bek';
// $db = new PDO('mysql:host=localhost;dbname=mydd', $user, $pass, [
//     PDO::ATTR_PERSISTENT => true, 
//     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
// ]);
$pass = '4643907'; 
$user = 'u68770';
$db = new PDO('mysql:host=localhost;dbname=u68770', $user, $pass,
    [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Если форма отправлена
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
    $stmt->execute([$_POST['login']]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($_POST['password'], $admin['password_hash'])) {
        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_login'] = $admin['login'];
        header('Location: admin.php');
        exit();
    } else {
        $error = 'Неверный логин или пароль';
    }
}

// Если уже авторизован - редирект
if (isset($_SESSION['admin_auth'])) {
    header('Location: admin.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<title>Вход в панель администратора</title>
	<style>
	body {
		font-family: Arial, sans-serif;
	}

	.login-form {
		max-width: 400px;
		margin: 50px auto;
		padding: 20px;
		border: 1px solid #ddd;
	}

	.form-group {
		margin-bottom: 15px;
	}

	label {
		display: block;
		margin-bottom: 5px;
	}

	input[type="text"],
	input[type="password"] {
		width: 100%;
		padding: 8px;
		box-sizing: border-box;
	}

	.error {
		color: red;
		margin-bottom: 15px;
	}

	.submit-btn {
		background: #4CAF50;
		color: white;
		padding: 10px 15px;
		border: none;
		cursor: pointer;
	}
	</style>
</head>

<body>
	<div class="login-form">
		<h2>Вход в панель администратора</h2>

		<?php if (isset($error)): ?>
		<div class="error"><?= $error ?></div>
		<?php endif; ?>

		<form method="POST">
			<div class="form-group">
				<label for="login">Логин:</label>
				<input type="text" id="login" name="login" required>
			</div>

			<div class="form-group">
				<label for="password">Пароль:</label>
				<input type="password" id="password" name="password" required>
			</div>

			<button type="submit" class="submit-btn">Войти</button>
		</form>
	</div>
</body>

</html>