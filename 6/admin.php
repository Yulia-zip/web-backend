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

// 🔹 Обработка действий (удаление, редактирование)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete'])) {
        $form_id = $_POST['form_id'];
        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM lang_check WHERE check_id = ?")->execute([$form_id]);
            $db->prepare("DELETE FROM users WHERE form_id = ?")->execute([$form_id]);
            $db->prepare("DELETE FROM form WHERE id = ?")->execute([$form_id]);
            $db->commit();
            $message = "Данные пользователя успешно удалены";
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Ошибка при удалении: " . $e->getMessage();
        }
    } elseif (isset($_POST['update'])) {
        header("Location: admin_edit.php?form_id=" . $_POST['form_id']);
        exit();
    }
}

// 🔹 Получение списка пользователей
$users = $db->query("
    SELECT f.*, u.login, GROUP_CONCAT(l.name_lang SEPARATOR ', ') as languages
    FROM form f
    LEFT JOIN users u ON u.form_id = f.id
    LEFT JOIN lang_check lc ON lc.check_id = f.id
    LEFT JOIN lang l ON l.id = lc.language_id
    GROUP BY f.id
")->fetchAll();

// 🔹 Получение статистики по языкам
$stats = $db->query("
    SELECT l.name_lang, COUNT(lc.check_id) as user_count
    FROM lang l
    LEFT JOIN lang_check lc ON l.id = lc.language_id
    GROUP BY l.id
    ORDER BY user_count DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<title>Панель администратора</title>
	<style>
	table {
		width: 100%;
		border-collapse: collapse;
	}

	th,
	td {
		padding: 8px;
		border: 1px solid #ddd;
		text-align: left;
	}

	th {
		background-color: #f2f2f2;
	}

	.stats {
		margin-top: 30px;
	}

	.user-form-btn {
		padding: 5px 10px;
		background: #2196F3;
		color: white;
		border-radius: 3px;
		text-decoration: none;
	}

	.logout-btn {
		padding: 5px 10px;
		background: #f44336;
		color: white;
		border-radius: 3px;
		text-decoration: none;
	}
	</style>
</head>

<body>
	<div style="position: absolute; top: 10px; right: 10px;">
		<a href="index.php" class="user-form-btn">Форма пользователя</a>
	</div>
	<h1>Панель администратора</h1>

	<?php if (isset($message)): ?>
	<div style="color: green;"><?= $message ?></div>
	<?php endif; ?>

	<?php if (isset($error)): ?>
	<div style="color: red;"><?= $error ?></div>
	<?php endif; ?>

	<h2>Все пользователи</h2>
	<table>
		<tr>
			<th>ID</th>
			<th>ФИО</th>
			<th>Телефон</th>
			<th>Email</th>
			<th>Любимые языки</th>
			<th>Действия</th>
		</tr>
		<?php foreach ($users as $user): ?>
		<tr>
			<td><?= htmlspecialchars($user['id']) ?></td>
			<td><?= htmlspecialchars($user['name_fio']) ?></td>
			<td><?= htmlspecialchars($user['phone']) ?></td>
			<td><?= htmlspecialchars($user['email']) ?></td>
			<td><?= htmlspecialchars($user['languages']) ?></td>
			<td>
				<form method="POST" style="display: inline;">
					<input type="hidden" name="form_id" value="<?= $user['id'] ?>">
					<button type="submit" name="update">Редактировать</button>
				</form>
				<form method="POST" style="display: inline;" onsubmit="return confirm('Удалить этого пользователя?')">
					<input type="hidden" name="form_id" value="<?= $user['id'] ?>">
					<button type="submit" name="delete">Удалить</button>
				</form>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>

	<div class="stats">
		<h2>Статистика по языкам</h2>
		<table>
			<tr>
				<th>Язык программирования</th>
				<th>Количество пользователей</th>
			</tr>
			<?php foreach ($stats as $stat): ?>
			<tr>
				<td><?= htmlspecialchars($stat['name_lang']) ?></td>
				<td><?= $stat['user_count'] ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
	</div>
</body>

</html>