<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

$pass = '4643907'; 
$user = 'u68770';
$db = new PDO('mysql:host=localhost;dbname=u68770', $user, $pass,
    [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);


if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login']) && isset($_POST['password'])) {
    $login = $_POST['login'];
    $password = $_POST['password'];
    
    $stmt = $db->prepare("SELECT u.*, f.* FROM users u JOIN form f ON u.form_id = f.id WHERE u.login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['form_id'];
        $_SESSION['login'] = $user['login'];
				$_SESSION['form_id'] = $user['form_id']; 
				$_SESSION['form_data'] = $user;
        header('Location: index.php?edit=1');
				
        exit();
    } else {
        $errors['auth'] = 'Неверный логин или пароль';
        include('login_form.php');
        exit();
    }
}

if (isset($_GET['auth'])) {
    $login = $_GET['login'] ?? '';
    include('login_form.php');
    exit();
}

if (isset($_GET['edit']) && isset($_SESSION['user_id'])) {
    $form_id = $_SESSION['user_id'];
    
    $stmt = $db->prepare("SELECT * FROM form WHERE id = ?");
    $stmt->execute([$form_id]);
    $form_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT language_id FROM lang_check WHERE check_id = ?");
    $stmt->execute([$form_id]);
    $languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $oldValues = [
        'user-fio' => $form_data['name_fio'],
        'user-phone' => $form_data['phone'],
        'user-email' => $form_data['email'],
        'data' => $form_data['date_r'],
        'gender' => $form_data['gender'],
        'biograf' => $form_data['biograf'],
        'agree' => $form_data['contract_accepted'] ? 'yes' : '',
        'languages' => $languages
    ];
    
    $_SESSION['form_data'] = array_merge($_SESSION['form_data'] ?? [], $form_data);
    $_SESSION['form_data']['languages'] = $languages;
    
    include('form.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['user_id'])) {
    $form_id = $_SESSION['user_id'];
    $errors = [];
    $oldValues = $_POST;

		if (empty($_POST['user-fio'])) {
			$errors['user-fio'] = 'Укажите ФИО.';
	} elseif (!preg_match("/^[\p{L}\s\-]{5,}$/u", $_POST['user-fio'])) {
			$errors['user-fio'] = 'ФИО должно содержать только буквы, пробелы и дефисы (минимум 5 символов)';
	}

	if (empty($_POST['user-phone'])) {
			$errors['user-phone'] = 'Укажите номер телефона.';
	} elseif (!preg_match("/^8\d{10}$/", $_POST['user-phone'])) {
			$errors['user-phone'] = 'Телефон должен быть в формате 89999999999 (11 цифр, начинается с 8)';
	}
	
	if (empty($_POST['user-email'])) {
			$errors['user-email'] = 'Укажите email.';
	} elseif (!filter_var($_POST['user-email'], FILTER_VALIDATE_EMAIL)) {
			$errors['user-email'] = 'Некорректный формат email';
	}

	if (empty($_POST['data'])) {
			$errors['data'] = 'Укажите дату рождения.';
	} else {
			$date = DateTime::createFromFormat('Y-m-d', $_POST['data']);
			if (!$date) {
					$errors['data'] = 'Некорректный формат даты';
			} elseif ($date > new DateTime()) {
					$errors['data'] = 'Дата рождения не может быть в будущем';
			}
	}

	if (empty($_POST['gender'])) {
			$errors['gender'] = 'Укажите пол.';
	} elseif (!in_array($_POST['gender'], ['male', 'female'])) {
			$errors['gender'] = 'Некорректное значение пола';
	}

	if (empty($_POST['languages'])) {
			$errors['languages'] = 'Выберите хотя бы один язык.';
	} else {
			$validLanguages = range(1, 11);
			foreach ($_POST['languages'] as $lang) {
					if (!in_array($lang, $validLanguages)) {
							$errors['languages'] = 'Некорректный выбор языков';
							break;
					}
			}
	}

	if (empty($_POST['biograf'])) {
			$errors['biograf'] = 'Заполните биографию.';
	} elseif (strlen($_POST['biograf']) < 10) {
			$errors['biograf'] = 'Биография должна содержать минимум 10 символов';
	}

	if (empty($_POST['agree']) || $_POST['agree'] !== 'yes') {
			$errors['agree'] = 'Необходимо подтвердить ознакомление с контрактом';
	}

	if (empty($errors)) {	
    try {
			$db->beginTransaction();
			
			// 1. Обновляем основную форму
			$stmt = $db->prepare("UPDATE form SET 
					name_fio = :fio, 
					phone = :phone, 
					email = :email, 
					date_r = :date_r, 
					gender = :gender, 
					biograf = :biograf, 
					contract_accepted = :agree 
					WHERE id = :id");
			
			$stmt->execute([
					':fio' => $_POST['user-fio'],
					':phone' => $_POST['user-phone'],
					':email' => $_POST['user-email'],
					':date_r' => $_POST['data'],
					':gender' => $_POST['gender'],
					':biograf' => $_POST['biograf'],
					':agree' => ($_POST['agree'] === 'yes') ? 1 : 0,
					':id' => $form_id
			]);
			
			$db->prepare("DELETE FROM lang_check WHERE check_id = :id")->execute([':id' => $form_id]);
			if (!empty($_POST['languages'])) {
					$stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (:id, :lang)");
					foreach ($_POST['languages'] as $lang_id) {
						$stmt->execute([':id' => $form_id, ':lang' => $lang_id]);
					}
			}
			
			$db->commit();
			
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
			
			foreach ($_POST as $key => $value) {
				if ($key !== 'agree') {
						$val = is_array($value) ? json_encode($value) : $value;
						setcookie('persistent_'.$key, $val, time() + 86400, '/');
						$_COOKIE['persistent_'.$key] = $val;
				}
			}

		header('Location: index.php?edit=1&save=1');
		exit();

		} catch (PDOException $e) {
		$db->rollBack();
		error_log("UPDATE ERROR: ".$e->getMessage());
		$_SESSION['error'] = "Ошибка сохранения: ".$e->getMessage();
		header('Location: index.php?edit=1');
		exit();
		}
		} else {
		setcookie('form_errors', json_encode($errors), 0, '/');
		setcookie('old_values', json_encode($oldValues), 0, '/');
		header('Location: index.php?edit=1');
		exit();
		}
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    $oldValues = $_POST;

    if (empty($_POST['user-fio'])) {
			$errors['user-fio'] = 'Укажите ФИО.';
	} elseif (!preg_match("/^[\p{L}\s\-]{5,}$/u", $_POST['user-fio'])) {
			$errors['user-fio'] = 'ФИО должно содержать только буквы, пробелы и дефисы (минимум 5 символов)';
	}

	if (empty($_POST['user-phone'])) {
			$errors['user-phone'] = 'Укажите номер телефона.';
	} elseif (!preg_match("/^8\d{10}$/", $_POST['user-phone'])) {
			$errors['user-phone'] = 'Телефон должен быть в формате 89999999999 (11 цифр, начинается с 8)';
	}
	
	if (empty($_POST['user-email'])) {
			$errors['user-email'] = 'Укажите email.';
	} elseif (!filter_var($_POST['user-email'], FILTER_VALIDATE_EMAIL)) {
			$errors['user-email'] = 'Некорректный формат email';
	}

	if (empty($_POST['data'])) {
			$errors['data'] = 'Укажите дату рождения.';
	} else {
			$date = DateTime::createFromFormat('Y-m-d', $_POST['data']);
			if (!$date) {
					$errors['data'] = 'Некорректный формат даты';
			} elseif ($date > new DateTime()) {
					$errors['data'] = 'Дата рождения не может быть в будущем';
			}
	}

	if (empty($_POST['gender'])) {
			$errors['gender'] = 'Укажите пол.';
	} elseif (!in_array($_POST['gender'], ['male', 'female'])) {
			$errors['gender'] = 'Некорректное значение пола';
	}

	if (empty($_POST['languages'])) {
			$errors['languages'] = 'Выберите хотя бы один язык.';
	} else {
			$validLanguages = range(1, 11);
			foreach ($_POST['languages'] as $lang) {
					if (!in_array($lang, $validLanguages)) {
							$errors['languages'] = 'Некорректный выбор языков';
							break;
					}
			}
	}

	if (empty($_POST['biograf'])) {
			$errors['biograf'] = 'Заполните биографию.';
	} elseif (strlen($_POST['biograf']) < 10) {
			$errors['biograf'] = 'Биография должна содержать минимум 10 символов';
	}

	if (empty($_POST['agree']) || $_POST['agree'] !== 'yes') {
			$errors['agree'] = 'Необходимо подтвердить ознакомление с контрактом';
	}

	if (!empty($errors)) {
			foreach ($_POST as $key => $value) {
					if ($key !== 'agree') {
							setcookie('persistent_'.$key, '', time() - 3600, '/');
					}
			}
			
			setcookie('form_errors', json_encode($errors), 0, '/');
			setcookie('old_values', json_encode($oldValues), 0, '/');
			header('Location: index.php');
			exit();
	}

    if (!empty($errors)) {
        setcookie('form_errors', json_encode($errors), 0, '/');
        setcookie('old_values', json_encode($oldValues), 0, '/');
        header('Location: index.php');
        exit();
    }

    try {
        $stmt = $db->prepare("INSERT INTO form (name_fio, phone, email, date_r, gender, biograf, contract_accepted) 
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
        
        if (isset($_POST['languages'])) {
            foreach ($_POST['languages'] as $language_id) {
                $stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)");
                $stmt->execute([$form_id, $language_id]);
            }
        }
        
        $login = uniqid('user_');
        $password = bin2hex(random_bytes(4)); // Генерируем случайный пароль
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (form_id, login, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$form_id, $login, $password_hash]);
        
        include('credentials.php');
        exit();
        
    } catch (PDOException $e) {
        $errors['database'] = 'Ошибка при сохранении данных: ' . $e->getMessage();
        setcookie('form_errors', json_encode($errors), 0, '/');
        header('Location: index.php');
        exit();
    }
}

include('form.php');
?>