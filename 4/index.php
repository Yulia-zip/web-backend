<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

$pass = '4643907'; 
$user = 'u68770';
$db = new PDO('mysql:host=localhost;dbname=u68770', $user, $pass,
    [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

if (isset($_COOKIE['form_errors'])) {
    $errors = json_decode($_COOKIE['form_errors'], true);
    setcookie('form_errors', '', time() - 3600, '/');
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        $successMessage = 'Спасибо, результаты сохранены.';
    }
    include('form.php');
    exit();
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

    if (empty($errors)) {
        try {
            $user_name = $_POST['user-fio'];
            $user_phone = $_POST['user-phone'];
            $email = $_POST['user-email'];
            $dataBir = $_POST['data'];
            $gender = $_POST['gender'];
            $biog = $_POST['biograf'];
            $sogl = $_POST['agree'] === 'yes' ? 1 : 0;
            
            $stmt = $db->prepare("INSERT INTO form (name_fio, phone, email, date_r, gender, biograf, contract_accepted) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_name, $user_phone, $email, $dataBir, $gender, $biog, $sogl]);
            
            $form_id = $db->lastInsertId();
            
            if (isset($_POST['languages'])) {
                foreach ($_POST['languages'] as $language_id) {
                    $stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)");
                    $stmt->execute([$form_id, $language_id]);
                }
            }
            
            foreach ($_POST as $key => $value) {
                if ($key !== 'agree') { 
                    setcookie('persistent_'.$key, is_array($value) ? json_encode($value) : $value, 
                            time() + 60*60*24*365, '/');
                }
            }
            
            foreach ($_POST as $key => $value) {
                setcookie($key, '', time() - 3600, '/');
            }
            setcookie('old_values', '', time() - 3600, '/');
            
            header('Location: index.php?save=1');
            exit();
            
        } catch (PDOException $e) {
            $errors['database'] = 'Ошибка при сохранении данных: ' . $e->getMessage();
            setcookie('form_errors', json_encode($errors), 0, '/');
            header('Location: index.php');
            exit();
        }
    }
}
?>