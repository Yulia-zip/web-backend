<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<title>Вход в систему</title>
	<style>
	.error-message {
		color: red;
	}

	.form-container {
		max-width: 400px;
		margin: 0 auto;
		padding: 20px;
	}

	.form-group {
		margin-bottom: 15px;
	}

	label {
		display: block;
		margin-bottom: 5px;
	}

	input {
		width: 100%;
		padding: 8px;
		box-sizing: border-box;
	}

	button {
		padding: 10px 15px;
		background: #4CAF50;
		color: white;
		border: none;
	}
	</style>
</head>

<body>
	<div class="form-container">
		<h1>Вход в систему</h1>
		<?php if (isset($errors['auth'])): ?>
		<div class="error-message"><?= $errors['auth'] ?></div>
		<?php endif; ?>

		<form action="index.php" method="POST">
			<div class="form-group">
				<label for="login">Логин:</label>
				<input id="login" name="login" type="text" value="<?= htmlspecialchars($login ?? '') ?>" required>
			</div>
			<div class="form-group">
				<label for="password">Пароль:</label>
				<input id="password" name="password" type="password" required>
			</div>
			<button type="submit">Войти</button>
		</form>
	</div>
</body>

</html>