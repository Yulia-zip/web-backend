<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
session_start();

header('Content-Type: text/html; charset=UTF-8');
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("Referrer-Policy: no-referrer");
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$auth = isset($_GET['auth']) && $_GET['auth'] === '1' ? '1' : '';
$login = isset($_SESSION['generated_login']) ? $_SESSION['generated_login'] : (isset($_GET['login']) ?
trim($_GET['login']) : '');
$password = isset($_SESSION['generated_password']) ? $_SESSION['generated_password'] : '';
?>
<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<title>Ваши учетные данные</title>
	<style>
	.credentials-container {
		max-width: 600px;
		margin: 0 auto;
		padding: 20px;
		text-align: center;
	}

	.credentials-box {
		border: 1px solid #ccc;
		padding: 20px;
		margin: 20px 0;
		background: #f9f9f9;
	}

	.credentials {
		font-family: monospace;
		font-size: 1.2em;
	}

	.warning {
		color: red;
		font-weight: bold;
	}
	</style>
</head>

<body>
	<div class="credentials-container">
		<h1>Ваши учетные данные</h1>
		<p>Сохраните эти данные для входа в систему и редактирования формы:</p>

		<div class="credentials-box">
			<p><strong>Логин:</strong>
				<?= htmlspecialchars($_SESSION['generated_credentials']['login'] ?? 'Ошибка: логин не сгенерирован') ?></p>
			<p><strong>Пароль:</strong>
				<?= htmlspecialchars($_SESSION['generated_credentials']['password'] ?? 'Ошибка: пароль не сгенерирован') ?></p>
		</div>

		<p class="warning">Внимание! Пароль нельзя восстановить, сохраните его в надежном месте!</p>

		<a href="index.php?auth=1&login=<?= urlencode(htmlspecialchars($login, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?>">Перейти
			к форме входа</a>
	</div>
</body>

</html>