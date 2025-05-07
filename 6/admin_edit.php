<?php
header('Content-Type: text/html; charset=UTF-8');

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

// 🔹 Проверка HTTP Basic Auth
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    die('Требуется авторизация');
}

$stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
$stmt->execute([$_SERVER['PHP_AUTH_USER']]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    die('Неверные учетные данные');
}

// 🔹 Получение данных пользователя для редактирования
$form_id = $_GET['form_id'] ?? null;
if (!$form_id) {
    die('Не указан ID формы');
}

$stmt = $db->prepare("
    SELECT f.*, u.login, GROUP_CONCAT(lc.language_id) as lang_ids
    FROM form f
    LEFT JOIN users u ON u.form_id = f.id
    LEFT JOIN lang_check lc ON lc.check_id = f.id
    WHERE f.id = ?
    GROUP BY f.id
");
$stmt->execute([$form_id]);
$user_data = $stmt->fetch();

if (!$user_data) {
    die('Пользователь не найден');
}

// 🔹 Получение списка всех языков
$languages = $db->query("SELECT * FROM lang")->fetchAll();

// 🔹 Обновление данных
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name_fio = $_POST['name_fio'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $selected_langs = $_POST['languages'] ?? [];

    $db->beginTransaction();
    try {
        // Обновляем основную форму
        $db->prepare("UPDATE form SET name_fio = ?, phone = ?, email = ? WHERE id = ?")
           ->execute([$name_fio, $phone, $email, $form_id]);

        // Обновляем языки
        $db->prepare("DELETE FROM lang_check WHERE check_id = ?")->execute([$form_id]);
        foreach ($selected_langs as $lang_id) {
            $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)")
               ->execute([$form_id, $lang_id]);
        }

        $db->commit();
        $message = "Данные успешно обновлены";
    } catch (PDOException $e) {
        $db->rollBack();
        $error = "Ошибка при обновлении: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<title>Редактирование пользователя</title>
</head>

<body>
	<h1>Редактирование пользователя</h1>

	<?php if (isset($message)): ?>
	<div style="color: green;"><?= $message ?></div>
	<?php endif; ?>

	<?php if (isset($error)): ?>
	<div style="color: red;"><?= $error ?></div>
	<?php endif; ?>

	<form method="POST">
		<div>
			<label>ФИО:</label>
			<input type="text" name="name_fio" value="<?= htmlspecialchars($user_data['name_fio']) ?>" required>
		</div>
		<div>
			<label>Телефон:</label>
			<input type="text" name="phone" value="<?= htmlspecialchars($user_data['phone']) ?>" required>
		</div>
		<div>
			<label>Email:</label>
			<input type="email" name="email" value="<?= htmlspecialchars($user_data['email']) ?>" required>
		</div>
		<div>
			<label>Любимые языки:</label>
			<?php foreach ($languages as $lang): ?>
			<div>
				<input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>"
					<?= in_array($lang['id'], explode(',', $user_data['lang_ids'])) ? 'checked' : '' ?>>
				<?= htmlspecialchars($lang['name_lang']) ?>
			</div>
			<?php endforeach; ?>
		</div>
		<button type="submit">Сохранить</button>
	</form>
	<a href="admin.php">Назад</a>
</body>

</html>