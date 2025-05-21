<?php
error_reporting(0);
ini_set('display_errors', 0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); 
ini_set('session.use_strict_mode', 1);

header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");
session_start();

$pass = '4643907'; 
$user = 'u68770';
$db = new PDO('mysql:host=localhost;dbname=u68770', $user, $pass, [
    PDO::ATTR_PERSISTENT => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false
]);


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login']) && isset($_POST['password'])) {
    if ($_SESSION['login_attempts'] > 5) {
        die('Too many login attempts. Try again later.');
    }

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Недействительный CSRF-токен');
    }

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
            $_SESSION['login_attempts'] = 0;
            header('Location: index.php?edit=1');
            exit();
        } else {
            sleep(1); 
            $_SESSION['login_attempts']++;
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

if (isset($_GET['auth'])) {
    $login = isset($_GET['login']) ? htmlspecialchars($_GET['login'], ENT_QUOTES) : '';
    include('login_form.php');
    exit();
}

if (isset($_GET['edit']) && isset($_SESSION['user_id'])) {
    $form_id = (int)$_SESSION['user_id'];
    
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
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        die('Недействительный CSRF-токен. Пожалуйста, обновите страницу и попробуйте снова.');
    }

    $errors = validateFormData($_POST);
    
    if (empty($errors)) {
        $is_edit = isset($_POST['edit_mode']) && isset($_SESSION['user_id']);
        
        if ($is_edit) {
            $form_id = (int)$_SESSION['user_id'];
            
            try {
                $db->beginTransaction();
                
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
                    htmlspecialchars($_POST['user-fio'], ENT_QUOTES),
                    htmlspecialchars($_POST['user-phone'], ENT_QUOTES),
                    htmlspecialchars($_POST['user-email'], ENT_QUOTES),
                    $_POST['data'],
                    $_POST['gender'],
                    htmlspecialchars($_POST['biograf'], ENT_QUOTES),
                    ($_POST['agree'] === 'yes') ? 1 : 0,
                    $form_id
                ]);
                
                $db->prepare("DELETE FROM lang_check WHERE check_id = ?")
                   ->execute([$form_id]);
                
                if (!empty($_POST['languages'])) {
                    $stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)");
                    foreach ($_POST['languages'] as $lang_id) {
                        $stmt->execute([$form_id, (int)$lang_id]);
                    }
                }
                
                $db->commit();
                
                $_SESSION['form_data'] = [
                    'name_fio' => htmlspecialchars($_POST['user-fio'], ENT_QUOTES),
                    'phone' => htmlspecialchars($_POST['user-phone'], ENT_QUOTES),
                    'email' => htmlspecialchars($_POST['user-email'], ENT_QUOTES),
                    'date_r' => $_POST['data'],
                    'gender' => $_POST['gender'],
                    'biograf' => htmlspecialchars($_POST['biograf'], ENT_QUOTES),
                    'contract_accepted' => ($_POST['agree'] === 'yes') ? 1 : 0,
                    'languages' => array_map('intval', $_POST['languages'] ?? [])
                ];
                
                $_SESSION['success_message'] = "Данные успешно обновлены!";
                header('Location: index.php?edit=1&success=1');
                exit();
                
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("Ошибка обновления: " . $e->getMessage());
                $_SESSION['error'] = "Ошибка сохранения данных";
                $_SESSION['old_values'] = sanitizeInput($_POST);
                header('Location: index.php?edit=1');
                exit();
            }
        } else {
            try {
                $db->beginTransaction();
                
                $stmt = $db->prepare("INSERT INTO form 
                                    (name_fio, phone, email, date_r, gender, biograf, contract_accepted) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    htmlspecialchars($_POST['user-fio'], ENT_QUOTES),
                    htmlspecialchars($_POST['user-phone'], ENT_QUOTES),
                    htmlspecialchars($_POST['user-email'], ENT_QUOTES),
                    $_POST['data'],
                    $_POST['gender'],
                    htmlspecialchars($_POST['biograf'], ENT_QUOTES),
                    ($_POST['agree'] === 'yes') ? 1 : 0
                ]);
                
                $form_id = $db->lastInsertId();
                
                if (isset($_POST['languages'])) {
                    foreach ($_POST['languages'] as $language_id) {
                        $stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)");
                        $stmt->execute([$form_id, (int)$language_id]);
                    }
                }
                
                $login = uniqid('user_');
                $password = bin2hex(random_bytes(4));
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO users (form_id, login, password_hash) VALUES (?, ?, ?)");
                $stmt->execute([$form_id, $login, $password_hash]);
                
                $db->commit();
                $_SESSION['generated_credentials'] = [
                    'login' => $login,
                'password' => $password
                ];
                
                include('credentials.php');
                exit();
                
            } catch (PDOException $e) {
                $db->rollBack();
                error_log("Ошибка при создании формы: " . $e->getMessage());
                $errors['database'] = 'Ошибка при сохранении данных';
                setcookie('form_errors', json_encode($errors), 0, '/');
                setcookie('old_values', json_encode(sanitizeInput($_POST)), 0, '/');
                header('Location: index.php');
                exit();
            }
        }
    } else {
        if (isset($_SESSION['user_id'])) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old_values'] = sanitizeInput($_POST);
            header('Location: index.php?edit=1');
        } else {
            setcookie('form_errors', json_encode($errors), 0, '/');
            setcookie('old_values', json_encode(sanitizeInput($_POST)), 0, '/');
            header('Location: index.php');
        }
        exit();
    }
}

$allowed_includes = ['form.php', 'login_form.php'];
$page = 'form.php';

if (in_array($page, $allowed_includes) && file_exists(__DIR__ . '/' . $page)) {
    include(__DIR__ . '/' . $page);
} else {
    include('form.php');
}

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
            if (!in_array((int)$lang, $validLanguages)) {
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