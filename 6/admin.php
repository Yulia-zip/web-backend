<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1>Требуется авторизация</h1>';
    exit();
}


$admin_login = $_SERVER['PHP_AUTH_USER'];
$admin_pass = $_SERVER['PHP_AUTH_PW'];

$stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
$stmt->execute([$admin_login]);
$hashed_password = $stmt->fetchColumn();

if (!$hashed_password || !password_verify($admin_pass, $hashed_password)) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1>Ошибка авторизации</h1><p>Неверный логин или пароль.</p>';
    exit();
}

// if (isset($_GET['logout'])) {
//     session_destroy();
//     header('Location: index.php');
//     exit();
// }


if (isset($_GET['logout'])) {
	session_destroy();
	header('WWW-Authenticate: Basic realm="Admin Panel"');
	header('HTTP/1.0 401 Unauthorized');
	header('Location: index.php');
	exit();	
}



function getAllForms($db) {
    $stmt = $db->query("
        SELECT f.*, u.login 
        FROM form f
        JOIN users u ON f.id = u.form_id
        ORDER BY f.id
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFormLanguages($db, $form_id) {
	$stmt = $db->prepare("
			SELECT l.id, l.name_lang 
			FROM lang_check lc
			JOIN lang l ON lc.language_id = l.id
			WHERE lc.check_id = ?
	");
	$stmt->execute([$form_id]);
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getLanguagesStatistics($db) {
	$stmt = $db->query("
			SELECT l.id, l.name_lang, COUNT(lc.check_id) as user_count
			FROM lang l
			LEFT JOIN lang_check lc ON l.id = lc.language_id
			GROUP BY l.id, l.name_lang
			ORDER BY user_count DESC
	");
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function getAllLanguages($db) {
	$stmt = $db->query("SELECT id, name_lang FROM lang ORDER BY name_lang");
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("DELETE FROM lang_check WHERE check_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("DELETE FROM users WHERE form_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("DELETE FROM form WHERE id = ?");
        $stmt->execute([$id]);
        
        $db->commit();
        
        header('Location: admin.php');
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка при удалении: ' . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_form'])) {
    $id = (int)$_POST['id'];
    $name_fio = trim($_POST['name_fio']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $date_r = $_POST['date_r'];
    $gender = $_POST['gender'];
    $biograf = trim($_POST['biograf']);
    $contract_accepted = isset($_POST['contract_accepted']) ? 1 : 0;
    $languages = isset($_POST['languages']) ? $_POST['languages'] : [];
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            UPDATE form SET 
            name_fio = ?, phone = ?, email = ?, date_r = ?, 
            gender = ?, biograf = ?, contract_accepted = ?
            WHERE id = ?
        ");
        $stmt->execute([$name_fio, $phone, $email, $date_r, $gender, $biograf, $contract_accepted, $id]);
        
        $stmt = $db->prepare("DELETE FROM lang_check WHERE check_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)");
        foreach ($languages as $language_id) {
            $stmt->execute([$id, $language_id]);
        }
        
        $db->commit();
        
        header("Location: admin.php");
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        die('Ошибка при обновлении: ' . $e->getMessage());
    }
}

$edit_data = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("
        SELECT * FROM form WHERE id = ?
    ");
    $stmt->execute([$id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($edit_data) {
        $stmt = $db->prepare("SELECT language_id FROM lang_check WHERE check_id = ?");
        $stmt->execute([$id]);
        $edit_data['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

$forms = getAllForms($db);
$statistics = getLanguagesStatistics($db);
$all_languages = getAllLanguages($db);
?>
<!DOCTYPE html>
<html lang="ru">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Административная панель</title>
	<link rel="stylesheet" href="style.css">
	<style>
	.admin-container {
		max-width: 1200px;
		margin: 0 auto;
		padding: 20px;
	}

	.admin-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 30px;
		padding-bottom: 15px;
		border-bottom: 1px solid #ddd;
	}

	table {
		width: 100%;
		border-collapse: collapse;
		margin-bottom: 30px;
	}

	th,
	td {
		padding: 12px;
		border: 1px solid #ddd;
		text-align: left;
	}

	th {
		background-color: #f2f2f2;
	}

	.edit-form {
		background-color: #f9f9f9;
		padding: 20px;
		margin-bottom: 30px;
		border-radius: 5px;
	}

	.form-group {
		margin-bottom: 15px;
	}

	.form-group label {
		display: block;
		margin-bottom: 5px;
		font-weight: bold;
	}

	.form-group input[type="text"],
	.form-group input[type="email"],
	.form-group input[type="tel"],
	.form-group input[type="date"],
	.form-group textarea {
		width: 100%;
		padding: 8px;
		border: 1px solid #ddd;
		border-radius: 4px;
	}

	.language-options {
		display: flex;
		flex-wrap: wrap;
		gap: 10px;
	}

	.language-option {
		display: flex;
		align-items: center;
	}

	.btn {
		padding: 8px 15px;
		border-radius: 4px;
		text-decoration: none;
		color: white;
		cursor: pointer;
		border: none;
	}

	.btn-primary {
		background-color: #0d6efd;
	}

	.btn-danger {
		background-color: #dc3545;
	}

	.btn-secondary {
		background-color: #6c757d;
	}
	</style>
</head>

<body>
	<div class="admin-container">
		<div class="admin-header">
			<h1>Административная панель</h1>
			<a href="admin.php?logout=1" class="btn btn-danger">Выйти</a>
		</div>

		<?php if ($edit_data): ?>
		<div class="edit-form">
			<h2>Редактирование формы #<?= htmlspecialchars($edit_data['id']) ?></h2>
			<form method="post">
				<input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']) ?>">
				<input type="hidden" name="edit_form" value="1">

				<div class="form-group">
					<label>ФИО:</label>
					<input type="text" name="name_fio" required value="<?= htmlspecialchars($edit_data['name_fio']) ?>">
				</div>

				<div class="form-group">
					<label>Телефон:</label>
					<input type="tel" name="phone" required value="<?= htmlspecialchars($edit_data['phone']) ?>">
				</div>

				<div class="form-group">
					<label>Email:</label>
					<input type="email" name="email" required value="<?= htmlspecialchars($edit_data['email']) ?>">
				</div>

				<div class="form-group">
					<label>Дата рождения:</label>
					<input type="date" name="date_r" required value="<?= htmlspecialchars($edit_data['date_r']) ?>">
				</div>

				<div class="form-group">
					<label>Пол:</label>
					<label><input type="radio" name="gender" value="male" <?= $edit_data['gender'] == 'male' ? 'checked' : '' ?>>
						Мужской</label>
					<label><input type="radio" name="gender" value="female"
							<?= $edit_data['gender'] == 'female' ? 'checked' : '' ?>> Женский</label>
				</div>

				<div class="form-group">
					<label>Биография:</label>
					<textarea name="biograf"><?= htmlspecialchars($edit_data['biograf']) ?></textarea>
				</div>

				<div class="form-group">
					<label>Языки программирования:</label>
					<div class="language-options">
						<?php foreach ($all_languages as $lang): ?>
						<div class="language-option">
							<input type="checkbox" name="languages[]" value="<?= $lang['id'] ?>"
								<?= in_array($lang['id'], $edit_data['languages']) ? 'checked' : '' ?>>
							<label><?= htmlspecialchars($lang['name_lang']) ?></label>
						</div>
						<?php endforeach; ?>
					</div>
				</div>


				<div class="form-group">
					<label>
						<input type="checkbox" name="contract_accepted" value="1"
							<?= $edit_data['contract_accepted'] ? 'checked' : '' ?>>
						Согласие на обработку данных
					</label>
				</div>

				<button type="submit" class="btn btn-primary">Сохранить</button>
				<a href="admin.php" class="btn btn-secondary">Отмена</a>
			</form>
		</div>
		<?php endif; ?>

		<h2>Все формы</h2>
		<div class="table-responsive">
			<table>
				<thead>
					<tr>
						<th>ID</th>
						<th>Логин</th>
						<th>ФИО</th>
						<th>Телефон</th>
						<th>Email</th>
						<th>Дата рождения</th>
						<th>Пол</th>
						<th>Языки</th>
						<th>Действия</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($forms as $form): ?>
					<tr>
						<td><?= htmlspecialchars($form['id']) ?></td>
						<td><?= htmlspecialchars($form['login']) ?></td>
						<td><?= htmlspecialchars($form['name_fio']) ?></td>
						<td><?= htmlspecialchars($form['phone']) ?></td>
						<td><?= htmlspecialchars($form['email']) ?></td>
						<td><?= htmlspecialchars($form['date_r']) ?></td>
						<td><?= $form['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
						<td>
							<?php 
    $langs = getFormLanguages($db, $form['id']);
    echo htmlspecialchars(implode(', ', array_column($langs, 'name_lang')));
    ?>
						</td>

						<td>
							<a href="admin.php?edit=<?= $form['id'] ?>" class="btn btn-primary">Редактировать</a>
							<a href="admin.php?delete=<?= $form['id'] ?>" class="btn btn-danger"
								onclick="return confirm('Вы уверены, что хотите удалить эту форму?')">Удалить</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<h2>Статистика по языкам программирования</h2>
		<table>
			<thead>
				<tr>
					<th>Язык</th>
					<th>Количество пользователей</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($statistics as $stat): ?>
				<tr>
					<td><?= htmlspecialchars($stat['name_lang']) ?></td>
					<td><?= htmlspecialchars($stat['user_count']) ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>

		</table>
	</div>
</body>

</html>