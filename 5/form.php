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
	$pass = '4643907'; 
	$user = 'u68770';
	$db = new PDO('mysql:host=localhost;dbname=u68770', $user, $pass, [
			PDO::ATTR_PERSISTENT => true,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
	]);

	if (isset($_SESSION['user_id'])) {
			try {
					$stmt = $db->prepare("SELECT * FROM form WHERE id = ?");
					$stmt->execute([$_SESSION['user_id']]);
					$formData = $stmt->fetch(PDO::FETCH_ASSOC);
					
					if ($formData) {
							setcookie('persistent_user-fio', $formData['name_fio'], time()+3600, '/');
							setcookie('persistent_user-phone', $formData['phone'], time()+3600, '/');
							setcookie('persistent_user-email', $formData['email'], time()+3600, '/');
							setcookie('persistent_data', $formData['date_r'], time()+3600, '/');
							setcookie('persistent_gender', $formData['gender'], time()+3600, '/');
							setcookie('persistent_biograf', $formData['biograf'], time()+3600, '/');
							
							$stmt = $db->prepare("SELECT language_id FROM lang_check WHERE check_id = ?");
							$stmt->execute([$_SESSION['user_id']]);
							$languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
							setcookie('persistent_languages', json_encode($languages), time()+3600, '/');
							
							$_COOKIE['persistent_user-fio'] = $formData['name_fio'];
							$_COOKIE['persistent_user-phone'] = $formData['phone'];
							$_COOKIE['persistent_user-email'] = $formData['email'];
							$_COOKIE['persistent_data'] = $formData['date_r'];
							$_COOKIE['persistent_gender'] = $formData['gender'];
							$_COOKIE['persistent_biograf'] = $formData['biograf'];
							$_COOKIE['persistent_languages'] = json_encode($languages);
					}
			} catch (PDOException $e) {
					error_log("Ошибка загрузки данных формы: " . $e->getMessage());
			}
	}
	?>
	<?php if (isset($_SESSION['error'])): ?>
	<div style="color: red; padding: 10px; margin: 10px; border: 1px solid red;">
		<?= htmlspecialchars($_SESSION['error']) ?>
	</div>
	<?php 
	unset($_SESSION['error']);
	endif; ?>

	<?php if (isset($_SESSION['user_id'])): ?>
	<a href="index.php?logout=1" class="logout-btn">Выйти</a>
	<?php endif; ?>


	<form class="decor" action="index.php<?= isset($_SESSION['user_id']) ? '?edit=1' : '' ?>" method="POST">
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
                echo '<p class="success-message">Спасибо, результаты '. (isset($_SESSION['user_id']) ? 'обновлены' : 'сохранены') .'.</p>';
            }
            ?>

			<h1 id="zag_form"><?= isset($_SESSION['user_id']) ? 'Редактирование формы' : 'Заполнение формы' ?></h1>

			<label for="user-fio">ФИО:</label><br />
			<input id="user-fio" name="user-fio" type="text" placeholder="Ваше полное имя" value="<?= !empty($_GET['save']) ? '' : htmlspecialchars(
                $_COOKIE['persistent_user-fio'] ?? 
                $oldValues['user-fio'] ?? 
                $_COOKIE['user-fio'] ?? 
                ''
            ) ?>" class="<?= isset($errors['user-fio']) ? 'error-field' : '' ?>" />
			<?php if (isset($errors['user-fio'])): ?>
			<div class="error-message"><?= $errors['user-fio'] ?></div>
			<?php endif; ?>
			<br />
			<label for="user-phone">Номер телефона:</label><br />
			<input id="user-phone" name="user-phone" type="tel" placeholder="89999999999" value="<?= !empty($_GET['save']) ? '' : htmlspecialchars(
        $_COOKIE['persistent_user-phone'] ?? 
        $oldValues['user-phone'] ?? 
        $_COOKIE['user-phone'] ?? 
        ''
    ) ?>" class="<?= isset($errors['user-phone']) ? 'error-field' : '' ?>" />
			<?php if (isset($errors['user-phone'])): ?>
			<div class="error-message"><?= $errors['user-phone'] ?></div>
			<?php endif; ?>
			<br />

			<label for="user-email">Электронная почта:</label><br />
			<input id="user-email" name="user-email" type="email" placeholder="example@example.example" value="<?= !empty($_GET['save']) ? '' : htmlspecialchars(
        $_COOKIE['persistent_user-email'] ?? 
        $oldValues['user-email'] ?? 
        $_COOKIE['user-email'] ?? 
        ''
    ) ?>" class="<?= isset($errors['user-email']) ? 'error-field' : '' ?>" />
			<?php if (isset($errors['user-email'])): ?>
			<div class="error-message"><?= $errors['user-email'] ?></div>
			<?php endif; ?>
			<br />

			<label for="data">Дата рождения:</label><br />
			<input id="data" name="data" type="date" value="<?= !empty($_GET['save']) ? '' : htmlspecialchars(
        $_COOKIE['persistent_data'] ?? 
        $oldValues['data'] ?? 
        $_COOKIE['data'] ?? 
        ''
    ) ?>" class="<?= isset($errors['data']) ? 'error-field' : '' ?>" />
			<?php if (isset($errors['data'])): ?>
			<div class="error-message"><?= $errors['data'] ?></div>
			<?php endif; ?>
			<br />

			<div class='pol'>
				<label>Ваш пол:</label>
				<div>
					<label for="male">Мужской</label>
					<input type="radio" id="male" name="gender" value="male"
						<?= !empty($_GET['save']) ? '' : (($_COOKIE['persistent_gender'] ?? $oldValues['gender'] ?? $_COOKIE['gender'] ?? '') === 'male' ? 'checked' : '') ?>
						class="<?= isset($errors['gender']) ? 'error-field' : '' ?>" />
				</div>
				<div>
					<label for="female">Женский</label>
					<input type="radio" id="female" name="gender" value="female"
						<?= !empty($_GET['save']) ? '' : (($_COOKIE['persistent_gender'] ?? $oldValues['gender'] ?? $_COOKIE['gender'] ?? '') === 'female' ? 'checked' : '') ?>
						class="<?= isset($errors['gender']) ? 'error-field' : '' ?>" />
				</div>
				<?php if (isset($errors['gender'])): ?>
				<div class="error-message"><?= $errors['gender'] ?></div>
				<?php endif; ?>
			</div>
			<br />

			<label for="languages">Любимые языки программирования:</label>
			<select id="languages" name="languages[]" multiple
				class="<?= isset($errors['languages']) ? 'error-field' : '' ?>">
				<?php 
						$selectedLangs = !empty($_GET['save']) ? [] : (
							isset($_COOKIE['persistent_languages']) ? 
							json_decode($_COOKIE['persistent_languages'], true) : 
							(isset($oldValues['languages']) ? 
							$oldValues['languages'] : 
							(isset($_COOKIE['languages']) ? 
							json_decode($_COOKIE['languages'], true) : []))
						);
    ?>
				<option value="1" <?= in_array('1', $selectedLangs) ? 'selected' : '' ?>>Pascal</option>
				<option value="2" <?= in_array('2', $selectedLangs) ? 'selected' : '' ?>>C</option>
				<option value="3" <?= in_array('3', $selectedLangs) ? 'selected' : '' ?>>C++</option>
				<option value="4" <?= in_array('4', $selectedLangs) ? 'selected' : '' ?>>JavaScript</option>
				<option value="5" <?= in_array('5', $selectedLangs) ? 'selected' : '' ?>>PHP</option>
				<option value="6" <?= in_array('6', $selectedLangs) ? 'selected' : '' ?>>Python</option>
				<option value="7" <?= in_array('7', $selectedLangs) ? 'selected' : '' ?>>Java</option>
				<option value="8" <?= in_array('8', $selectedLangs) ? 'selected' : '' ?>>Haskell</option>
				<option value="9" <?= in_array('9', $selectedLangs) ? 'selected' : '' ?>>Clojure</option>
				<option value="10" <?= in_array('10', $selectedLangs) ? 'selected' : '' ?>>Prolog</option>
				<option value="11" <?= in_array('11', $selectedLangs) ? 'selected' : '' ?>>Scala</option>
			</select>
			<?php if (isset($errors['languages'])): ?>
			<div class="error-message"><?= $errors['languages'] ?></div>
			<?php endif; ?>
			<br />

			<p>
				<label for="biograf">Биография:</label>
				<textarea id="biograf" name="biograf" rows="2" placeholder="Расскажите о себе"
					class="<?= isset($errors['biograf']) ? 'error-field' : '' ?>"><?= 
				!empty($_GET['save']) ? '' : htmlspecialchars(
					$_COOKIE['persistent_biograf'] ?? 
					$oldValues['biograf'] ?? 
					$_COOKIE['biograf'] ?? 
					''
			) 
		?></textarea>
				<?php if (isset($errors['biograf'])): ?>
			<div class="error-message"><?= $errors['biograf'] ?></div>
			<?php endif; ?>
			</p>

			<div class="sog">
				<label for="agree">с контрактом ознакомлен (а)</label>
				<input id="agree" name="agree" value="yes" type="checkbox"
					<?= !empty($_GET['save']) ? '' : (isset($oldValues['agree']) || isset($_COOKIE['agree']) ? 'checked' : '') ?>
					class="<?= isset($errors['agree']) ? 'error-field' : '' ?>" />
				<?php if (isset($errors['agree'])): ?>
				<div class="error-message"><?= $errors['agree'] ?></div>
				<?php endif; ?>
			</div>

			<button type="submit" class="submit">Сохранить</button>
	</form>
</body>

</html>