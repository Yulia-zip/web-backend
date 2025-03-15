<?php
header('Content-Type: text/html; charset=UTF-8');
include('form.php');
$errors = FALSE;
$errorsMessages=[];
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['user-fio'])) {
        $user_name = trim($_POST['user-fio']);
        if (empty($user_name) && !preg_match("/^[\p{L} ]+$/u", $user_name) && strlen($user_name) >150) {
            $errorMessages[] = 'Неверная запись ФИО <br>';
            $errors = TRUE;
        }
    } else {
        $errorMessages[] = "Укажите ФИО.<br>";
        $errors = TRUE;
    }

    if (isset($_POST['user-phone'])) {
        $user_phone = $_POST['user-phone'];
        if (!preg_match("/^[1-9]\d{10}$/", $user_phone)) {
            $errorMessages[] = "Некорректная запись номера телефона <br>";
            $errors = TRUE;
        }
    } else {
        $errorMessages[] = "Укажите номер телефона.<br>";
        $errors = TRUE;
    }

    if (isset($_POST['user-email'])) {
        $email = $_POST['user-email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessages[] = "Некорректная запись email <br>";
            $errors = TRUE;
        }
    } else {
        $errorMessages[] = "Укажите email.<br>";
        $errors = TRUE;
    }

    if (isset($_POST['data'])) {
        $dataBir = $_POST['data'];
        $data = DateTime::createFromFormat('Y-m-d', $dataBir);
        if ($data==false || $data->format('Y-m-d') !== $dataBir) {
            $errorMessages[] = "Некорректная запись даты<br>";
            $errors = TRUE;
        }
    } else {
        $errorMessages[] = "Дата не выбрана.<br>";
        $errors = TRUE;
    }

    if (isset($_POST['gender'])) {
        $gender = $_POST['gender'];
        if ($gender !== 'male' && $gender !== 'female') {
            $errorMessages[] = "Некорректный выбор пола.<br>";
            $errors = TRUE;
        }
    } else {
        $errorMessages[] = "Пол не выбран.<br>";
        $errors = TRUE;
    }

    if (!isset($_POST['languages']) || empty($_POST['languages'])) {
        $errorMessages[] = "Вы не выбрали ни одного языка программирования.<br>";
        $errors = TRUE;
    }

    if (isset($_POST['biograf'])) {
        $biog = trim($_POST['biograf']);
        if (empty($biog)) {
            $errorMessages[] = "Поле биография не заполнено.<br>";
            $errors = TRUE;
        }
    } else {
        $errorMessages[] = "Поле биография не передано.<br>";
        $errors = TRUE;
    }

    if (isset($_POST['agree']) && $_POST['agree'] === 'yes') {
        $sogl = ($_POST['agree']) ? 1 : 0; 
    }else {
        $errorMessages[] = "Подтвердите ознакомление с контрактом.<br>";
        $errors = TRUE;
    }

    if ($errors) {
        foreach ($errorMessages as $message) {
            echo '<font color="red">' . $message . '</font>';
        }
        exit();
    }


    $pass = '4643907'; 
    $user = 'u68770';
    $db = new PDO('mysql:host=localhost;dbname=u68770', $user, $pass,
    [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); 

    try {
        $stmt = $db->prepare("INSERT INTO form (name_fio, phone, email,date_r, gender, biograf,contract_accepted ) VALUES (?, ?, ?, ?, ?, ?,?)");
        $stmt->execute([$user_name, $user_phone, $email, $dataBir, $gender, $biog,$sogl]);

        $form_id = $db->lastInsertId();

    
        if (isset($_POST['languages'])) {
            foreach ($_POST['languages'] as $language_id) {
                $stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)");
                $stmt->execute([$form_id, $language_id]);
            }
        }
        $successMessage = 'Спасибо, результаты сохранены.';
    }catch (PDOException $e) {
        echo 'Ошибка: ' . $e->getMessage();
        exit();
    }
}