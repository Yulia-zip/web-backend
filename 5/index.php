<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

$pass = '4643907'; 
$user = 'u68770';
$db = new PDO('mysql:host=localhost;dbname=u68770', $user, $pass,
    [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Выход из системы
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Обработка входа
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login']) && isset($_POST['password'])) {
    $login = $_POST['login'];
    $password = $_POST['password'];
    
    try {
        $stmt = $db->prepare("SELECT u.id, u.login, u.password_hash, u.form_id, f.* 
                            FROM users u 
                            JOIN form f ON u.form_id = f.id 
                            WHERE u.login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['form_id'];
            $_SESSION['login'] = $user['login'];
            $_SESSION['form_data'] = $user;
            header('Location: index.php?edit=1');
            exit();
        } else {
            $errors['auth'] = 'Неверный логин или пароль';
            include('login_form.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Ошибка входа: " . $e->getMessage());
        $errors['auth'] = 'Ошибка системы. Попробуйте позже.';
        include('login_form.php');
        exit();
    }
}

// Показ формы входа
if (isset($_GET['auth'])) {
    $login = $_GET['login'] ?? '';
    include('login_form.php');
    exit();
}

// Редактирование существующей формы
if (isset($_GET['edit']) && isset($_SESSION['user_id'])) {
    $form_id = $_SESSION['user_id'];
    
    try {
        $stmt = $db->prepare("SELECT * FROM form WHERE id = ?");
        $stmt->execute([$form_id]);
        $form_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("SELECT language_id FROM lang_check WHERE check_id = ?");
        $stmt->execute([$form_id]);
        $languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $form_data);
        $_SESSION['form_data']['languages'] = $languages;
        
        include('form.php');
        exit();
    } catch (PDOException $e) {
        error_log("Ошибка загрузки формы: " . $e->getMessage());
        $_SESSION['error'] = "Ошибка загрузки данных формы";
        header('Location: index.php');
        exit();
    }
}



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	error_log("Получены POST данные: " . print_r($_POST, true));
	
	if (empty($_POST)) {
			die("Данные формы не получены. Проверьте атрибуты формы.");
	}

	$errors = validateFormData($_POST);
	
	if (empty($errors)) {
			$is_edit = isset($_POST['edit_mode']) && isset($_SESSION['user_id']);
			
			if ($is_edit) {
					$form_id = $_SESSION['user_id'];
					error_log("Режим редактирования. Form ID: $form_id");
					
					try {
							$db->beginTransaction();
							
							// 1. Обновляем основную форму
							$stmt = $db->prepare("UPDATE form SET 
																	name_fio = ?, 
																	phone = ?, 
																	email = ?, 
																	date_r = ?, 
																	gender = ?, 
																	biograf = ?, 
																	contract_accepted = ? 
																	WHERE id = ?");
							$stmt->execute([
									$_POST['user-fio'],
									$_POST['user-phone'],
									$_POST['user-email'],
									$_POST['data'],
									$_POST['gender'],
									$_POST['biograf'],
									($_POST['agree'] === 'yes') ? 1 : 0,
									$form_id
							]);
							
							// 2. Обновляем языки (сначала удаляем старые)
							$db->prepare("DELETE FROM lang_check WHERE check_id = ?")
								 ->execute([$form_id]);
							
							if (!empty($_POST['languages'])) {
									$stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)");
									foreach ($_POST['languages'] as $lang_id) {
											$stmt->execute([$form_id, $lang_id]);
									}
							}
							
							$db->commit();
							
							// Обновляем данные в сессии
							$_SESSION['form_data'] = [
									'name_fio' => $_POST['user-fio'],
									'phone' => $_POST['user-phone'],
									'email' => $_POST['user-email'],
									'date_r' => $_POST['data'],
									'gender' => $_POST['gender'],
									'biograf' => $_POST['biograf'],
									'contract_accepted' => ($_POST['agree'] === 'yes') ? 1 : 0,
									'languages' => $_POST['languages'] ?? []
							];
							
							$_SESSION['success_message'] = "Данные успешно обновлены!";
							header('Location: index.php?edit=1&success=1');
							exit();
							
					} catch (PDOException $e) {
							$db->rollBack();
							error_log("Ошибка обновления: " . $e->getMessage());
							$_SESSION['error'] = "Ошибка сохранения: " . $e->getMessage();
							$_SESSION['old_values'] = $_POST;
							header('Location: index.php?edit=1');
							exit();
					}
			} else {
				try {
					$db->beginTransaction();
					
					// Создание основной формы
					$stmt = $db->prepare("INSERT INTO form 
															(name_fio, phone, email, date_r, gender, biograf, contract_accepted) 
															VALUES (?, ?, ?, ?, ?, ?, ?)");
					$stmt->execute([
							$_POST['user-fio'],
							$_POST['user-phone'],
							$_POST['user-email'],
							$_POST['data'],
							$_POST['gender'],
							$_POST['biograf'],
							($_POST['agree'] === 'yes') ? 1 : 0
					]);
					
					$form_id = $db->lastInsertId();
					
					// Добавление языков
					if (isset($_POST['languages'])) {
							foreach ($_POST['languages'] as $language_id) {
									$stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)");
									$stmt->execute([$form_id, $language_id]);
							}
					}
					
					// Создание учетной записи
					$login = uniqid('user_');
					$password = bin2hex(random_bytes(4));
					$password_hash = password_hash($password, PASSWORD_DEFAULT);
					
					$stmt = $db->prepare("INSERT INTO users (form_id, login, password_hash) VALUES (?, ?, ?)");
					$stmt->execute([$form_id, $login, $password_hash]);
					
					$db->commit();
					
					include('credentials.php');
					exit();
					
			} catch (PDOException $e) {
					$db->rollBack();
					error_log("Ошибка при создании формы: " . $e->getMessage());
					$errors['database'] = 'Ошибка при сохранении данных: ' . $e->getMessage();
					setcookie('form_errors', json_encode($errors), 0, '/');
					setcookie('old_values', json_encode($_POST), 0, '/');
					header('Location: index.php');
					exit();
			}
			}
	} else {
		if (isset($_SESSION['user_id'])) {
			$_SESSION['form_errors'] = $errors;
			$_SESSION['old_values'] = $_POST;
			header('Location: index.php?edit=1');
	} else {
			setcookie('form_errors', json_encode($errors), 0, '/');
			setcookie('old_values', json_encode($_POST), 0, '/');
			header('Location: index.php');
	}
	exit();
	}
}
include('form.php');

function validateFormData($data) {
    $errors = [];
    
    if (empty($data['user-fio'])) {
        $errors['user-fio'] = 'Укажите ФИО.';
    } elseif (!preg_match("/^[\p{L}\s\-]{5,}$/u", $data['user-fio'])) {
        $errors['user-fio'] = 'ФИО должно содержать только буквы, пробелы и дефисы (минимум 5 символов)';
    }

    if (empty($data['user-phone'])) {
        $errors['user-phone'] = 'Укажите номер телефона.';
    } elseif (!preg_match("/^8\d{10}$/", $data['user-phone'])) {
        $errors['user-phone'] = 'Телефон должен быть в формате 89999999999 (11 цифр, начинается с 8)';
    }
    
    if (empty($data['user-email'])) {
        $errors['user-email'] = 'Укажите email.';
    } elseif (!filter_var($data['user-email'], FILTER_VALIDATE_EMAIL)) {
        $errors['user-email'] = 'Некорректный формат email';
    }

    if (empty($data['data'])) {
        $errors['data'] = 'Укажите дату рождения.';
    } else {
        $date = DateTime::createFromFormat('Y-m-d', $data['data']);
        if (!$date) {
            $errors['data'] = 'Некорректный формат даты';
        } elseif ($date > new DateTime()) {
            $errors['data'] = 'Дата рождения не может быть в будущем';
        }
    }

    if (empty($data['gender'])) {
        $errors['gender'] = 'Укажите пол.';
    } elseif (!in_array($data['gender'], ['male', 'female'])) {
        $errors['gender'] = 'Некорректное значение пола';
    }

    if (empty($data['languages'])) {
        $errors['languages'] = 'Выберите хотя бы один язык.';
    } else {
        $validLanguages = range(1, 11);
        foreach ($data['languages'] as $lang) {
            if (!in_array($lang, $validLanguages)) {
                $errors['languages'] = 'Некорректный выбор языков';
                break;
            }
        }
    }

    if (empty($data['biograf'])) {
        $errors['biograf'] = 'Заполните биографию.';
    } elseif (strlen($data['biograf']) < 10) {
        $errors['biograf'] = 'Биография должна содержать минимум 10 символов';
    }

    if (empty($data['agree']) || $data['agree'] !== 'yes') {
        $errors['agree'] = 'Необходимо подтвердить ознакомление с контрактом';
    }

    return $errors;
}