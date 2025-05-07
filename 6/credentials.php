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
			<p><strong>Логин:</strong> <span class="credentials"><?= htmlspecialchars($login) ?></span></p>
			<p><strong>Пароль:</strong> <span class="credentials"><?= htmlspecialchars($password) ?></span></p>
		</div>

		<p class="warning">Внимание! Пароль нельзя восстановить, сохраните его в надежном месте!</p>

		<a href="index.php?auth=1&login=<?= urlencode($login) ?>">Перейти к форме входа</a>
	</div>
</body>

</html>