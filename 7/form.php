<?php
header('Content-Type: text/html; charset=UTF-8');
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

ini_set('display_errors', 0);
ini_set('log_errors', 1);?>
<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="./style.css" />

	<title>Форма</title>
	<style>
	.error-field {
		border: 2px solid red !important;
		box-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
	}

	.error-message {
		color: red;
		font-size: 0.9em;
		margin-top: 5px;
	}

	.success-message {
		color: green;
		text-align: center;
		margin-bottom: 15px;
		font-size: 30px;
	}

	.logout-btn {
		position: absolute;
		top: 10px;
		right: 10px;
		padding: 5px 10px;
		background: #f44336;
		color: white;
		border: none;
		border-radius: 3px;
		cursor: pointer;
	}
	</style>
</head>

<body>
	<?php

	if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
	$pass = '4643907'; 
	$user = 'u68770';
	$db = new PDO('mysql:host=localhost;dbname=u68770', $user, $pass,
			[PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);


	if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare("SELECT * FROM form WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $formData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($formData) {
            setcookie('persistent_user-fio', $formData['name_fio'], time()+3600, '/', '', true, true);
            setcookie('persistent_user-phone', $formData['phone'], time()+3600, '/', '', true, true);
            setcookie('persistent_user-email', $formData['email'], time()+3600, '/', '', true, true);
            setcookie('persistent_data', $formData['date_r'], time()+3600, '/', '', true, true);
            setcookie('persistent_gender', $formData['gender'], time()+3600, '/', '', true, true);
            setcookie('persistent_biograf', $formData['biograf'], time()+3600, '/', '', true, true);
            
            $stmt = $db->prepare("SELECT language_id FROM lang_check WHERE check_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
            setcookie('persistent_languages', json_encode($languages), time()+3600, '/', '', true, true);
        }
    } catch (PDOException $e) {
        error_log("Ошибка загрузки данных формы: " . $e->getMessage());
    }
		
	}
	?>
	<?php if (isset($_GET['save'])): ?>
	<div class="success-message">Данные успешно сохранены!</div>
	<?php endif; ?>

	<?php if (isset($_SESSION['user_id'])): ?>
	<a href="index.php?logout=1" class="logout-btn">Выйти</a>
	<?php endif; ?>
	<?php if (isset($_SESSION['success_message'])): ?>
	<div class="success-message">
		<?= htmlspecialchars($_SESSION['success_message'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></div>
	<?php unset($_SESSION['success_message']); ?>
	<?php endif; ?>

	<?php if (isset($_SESSION['error'])): ?>
	<div class="error-message"><?= htmlspecialchars($_SESSION['error'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?></div>
	<?php unset($_SESSION['error']); ?>
	<?php endif; ?>
	<?php if (!isset($_SESSION['user_id'])): ?>
	<a href="admin.php?rand=<?= time() ?>" class="admin-login-btn"
		style="position: absolute; top: 10px; right: 10px; padding: 5px 10px; background: #4CAF50; color: white; text-decoration: none; border-radius: 3px;">Вход
		для администратора</a>
	<?php endif; ?>

	<form class="decor" action="index.php" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="csrf_token"
			value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES); ?>">
		<?php if (isset($_SESSION['user_id'])): ?>
		<input type="hidden" name="edit_mode" value="1">
		<?php endif; ?>
		<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
		<div class="form-left-decoration"></div>
		<div class="form-right-decoration"></div>
		<div class="circle"></div>
		<div class="form-inner">
			<?php
            $oldValues = isset($_COOKIE['old_values']) ? json_decode($_COOKIE['old_values'], true) : [];
            $errors = isset($_COOKIE['form_errors']) ? json_decode($_COOKIE['form_errors'], true) : [];
            
            setcookie('old_values', '', time() - 3600, '/');
            setcookie('form_errors', '', time() - 3600, '/');
            
            if (!empty($_GET['save'])) {
                echo '<p class="success-message">Спасибо, результаты '. 
                     htmlspecialchars((isset($_SESSION['user_id']) ? 'обновлены' : 'сохранены'), ENT_QUOTES | ENT_HTML5, 'UTF-8') .'.</p>';
            }
            ?>

			<h1 id="zag_form">
				<?= htmlspecialchars(isset($_SESSION['user_id']) ? 'Редактирование формы' : 'Заполнение формы', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
			</h1>

			<label for="user-fio">ФИО:</label><br />
			<input id="user-fio" name="user-fio" type="text" placeholder="Ваше полное имя"
				value="<?= htmlspecialchars($_SESSION['form_data']['name_fio'] ?? $_COOKIE['persistent_user-fio'] ?? $oldValues['user-fio'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
				class="<?= isset($errors['user-fio']) ? 'error-field' : '' ?>" />
			<?php if (isset($errors['user-fio'])): ?>
			<div class="error-message">
				<?= htmlspecialchars($errors['user-fio'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
			</div>
			<?php endif; ?>
			<br />

			<label for="user-phone">Номер телефона:</label><br />
			<input id="user-phone" name="user-phone" type="tel" placeholder="89999999999"
				value="<?= htmlspecialchars($_SESSION['form_data']['phone'] ?? $_COOKIE['persistent_user-phone'] ?? $oldValues['user-phone'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
				class="<?= isset($errors['user-phone']) ? 'error-field' : '' ?>" />
			<?php if (isset($errors['user-phone'])): ?>
			<div class="error-message">
				<?= htmlspecialchars($errors['user-phone'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
			</div>
			<?php endif; ?>
			<br />

			<label for="user-email">Электронная почта:</label><br />
			<input id="user-email" name="user-email" type="email" placeholder="example@example.example"
				value="<?= htmlspecialchars($_SESSION['form_data']['email'] ?? $_COOKIE['persistent_user-email'] ?? $oldValues['user-email'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
				class="<?= isset($errors['user-email']) ? 'error-field' : '' ?>" />
			<?php if (isset($errors['user-email'])): ?>
			<div class="error-message">
				<?= htmlspecialchars($errors['user-email'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
			</div>
			<?php endif; ?>
			<br />

			<label for="data">Дата рождения:</label><br />
			<input id="data" name="data" type="date"
				value="<?= htmlspecialchars($_SESSION['form_data']['date_r'] ?? $_COOKIE['persistent_data'] ?? $oldValues['data'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>"
				class="<?= isset($errors['data']) ? 'error-field' : '' ?>" />
			<?php if (isset($errors['data'])): ?>
			<div class="error-message">
				<?= htmlspecialchars($errors['data'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
			</div>
			<?php endif; ?>
			<br />

			<div class='pol'>
				<label>Ваш пол:</label>
				<div>
					<label for="male">Мужской</label>
					<input type="radio" id="male" name="gender" value="male"
						<?= ($_SESSION['form_data']['gender'] ?? $_COOKIE['persistent_gender'] ?? $oldValues['gender'] ?? '') === 'male' ? 'checked' : '' ?>
						class="<?= isset($errors['gender']) ? 'error-field' : '' ?>" />
				</div>
				<div>
					<label for="female">Женский</label>
					<input type="radio" id="female" name="gender" value="female"
						<?= ($_SESSION['form_data']['gender'] ?? $_COOKIE['persistent_gender'] ?? $oldValues['gender'] ?? '') === 'female' ? 'checked' : '' ?>
						class="<?= isset($errors['gender']) ? 'error-field' : '' ?>" />
				</div>
				<?php if (isset($errors['gender'])): ?>
				<div class="error-message">
					<?= htmlspecialchars($errors['gender'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
				</div>
				<?php endif; ?>
			</div>
			<br />

			<label for="languages">Любимые языки программирования:</label>
			<select id="languages" name="languages[]" multiple
				class="<?= isset($errors['languages']) ? 'error-field' : '' ?>">
				<?php 
                $selectedLangs = $_SESSION['form_data']['languages'] ?? 
                               (isset($_COOKIE['persistent_languages']) ? json_decode($_COOKIE['persistent_languages'], true) : 
                               ($oldValues['languages'] ?? []));
                ?>
				<option value="1" <?= in_array(1, $selectedLangs) ? 'selected' : '' ?>>Pascal</option>
				<option value="2" <?= in_array(2, $selectedLangs) ? 'selected' : '' ?>>C</option>
				<option value="3" <?= in_array(3, $selectedLangs) ? 'selected' : '' ?>>C++</option>
				<option value="4" <?= in_array(4, $selectedLangs) ? 'selected' : '' ?>>JavaScript</option>
				<option value="5" <?= in_array(5, $selectedLangs) ? 'selected' : '' ?>>PHP</option>
				<option value="6" <?= in_array(6, $selectedLangs) ? 'selected' : '' ?>>Python</option>
				<option value="7" <?= in_array(7, $selectedLangs) ? 'selected' : '' ?>>Java</option>
				<option value="8" <?= in_array(8, $selectedLangs) ? 'selected' : '' ?>>Haskell</option>
				<option value="9" <?= in_array(9, $selectedLangs) ? 'selected' : '' ?>>Clojure</option>
				<option value="10" <?= in_array(10, $selectedLangs) ? 'selected' : '' ?>>Prolog</option>
				<option value="11" <?= in_array(11, $selectedLangs) ? 'selected' : '' ?>>Scala</option>
				<option value="12" <?= in_array(12, $selectedLangs) ? 'selected' : '' ?>>Go</option>
			</select>
			<?php if (isset($errors['languages'])): ?>
			<div class="error-message">
				<?= htmlspecialchars($errors['languages'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
			</div>
			<?php endif; ?>
			<br />

			<p>
				<label for="biograf">Биография:</label>
				<textarea id="biograf" name="biograf" rows="2" placeholder="Расскажите о себе"
					class="<?= isset($errors['biograf']) ? 'error-field' : '' ?>"><?= 
                    htmlspecialchars($_SESSION['form_data']['biograf'] ?? $_COOKIE['persistent_biograf'] ?? $oldValues['biograf'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') 
                ?></textarea>
				<?php if (isset($errors['biograf'])): ?>
			<div class="error-message">
				<?= htmlspecialchars($errors['biograf'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
			</div>
			<?php endif; ?>
			</p>

			<div class="sog">
				<label for="agree">с контрактом ознакомлен (а)</label>
				<input id="agree" name="agree" value="yes" type="checkbox"
					<?= ($_SESSION['form_data']['contract_accepted'] ?? isset($oldValues['agree']) || isset($_COOKIE['agree']) ? 'checked' : '' )?>
					class="<?= isset($errors['agree']) ? 'error-field' : '' ?>" />
				<?php if (isset($errors['agree'])): ?>
				<div class="error-message">
					<?= htmlspecialchars($errors['agree'] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8') ?>
				</div>
				<?php endif; ?>
			</div>

			<button type="submit" class="submit">Сохранить</button>
		</div>
	</form>
</body>

</html>