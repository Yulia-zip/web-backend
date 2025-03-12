<?php
header('Content-Type: text/html; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] == 'GET') {

  if (!empty($_GET['save'])) {
    print('Спасибо, результаты сохранены.');
  }
  include('form.php');
  exit();
}
$errors = FALSE;

if (isset($_POST['user-fio'])) {
    $user_name = trim($_POST['user-fio']);
    if (!empty($user_name) && preg_match("/^[\p{L} ]+$/u", $user_name) && strlen($user_name) <= 150) {
        echo "FIO is valid";
    } else {
        echo "FIO is not valid";
        $errors = TRUE;
    }
} else {
    echo '<font color="red">"Укажите ФИО."</font>';
    $errors = TRUE;
}

if (isset($_POST['user-phone'])) {
    $user_phone = $_POST['user-phone'];
    if (preg_match("/^[1-9]\d{10}$/", $user_phone)) {
        echo "PHONE is valid";
    } else {
        echo "PHONE is not valid";
        $errors = TRUE;
    }
} else {
    echo '<font color="red">"Укажите номер телефона."</font>';
    $errors = TRUE;
}

if (isset($_POST['user-email'])) {
    $email = $_POST['user-email'];
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Email is valid";
    } else {
        echo '<font color="red">"Email is not valid"</font>';
        $errors = TRUE;
    }
} else {
    echo '<font color="red">"Укажите email."</font>';
    $errors = TRUE;
}

if (isset($_POST['data'])) {
    $dataBir = $_POST['data'];
    $data = DateTime::createFromFormat('Y-m-d', $dataBir);
    if ($data && $data->format('Y-m-d') === $dataBir) {
        echo "Date is valid";
    } else {
        echo '<font color="red">"Date is not valid"</font>';
        $errors = TRUE;
    }
} else {
    echo '<font color="red">"Дата не выбрана."</font>';
    $errors = TRUE;
}

if (isset($_POST['gender'])) {
    $gender = $_POST['gender'];
    if ($gender === 'male' || $gender === 'female') {
        echo "Корректный выбор пола.";
    } else {
        echo '<font color="red">"Некорректный выбор пола."</font>';
        $errors = TRUE;
    }
} else {
    echo '<font color="red">"Пол не выбран."</font>';
    $errors = TRUE;
}

if (!isset($_POST['languages']) || empty($_POST['languages'])) {
    echo "Вы не выбрали ни одного языка программирования.<br>";
    $errors = TRUE;
}

if (isset($_POST['biograf'])) {
    $biog = trim($_POST['biograf']);
    if (!empty($biog)) {
        echo "Поле биографии заполнено";
    } else {
        echo '<span style="color: red;">Поле биография не заполнено.</span>';
        $errors = TRUE;
    }
} else {
    echo '<span style="color: red;">Поле биография не передано.</span>';
    $errors = TRUE;
}

if (isset($_POST['agree']) && $_POST['agree'] === 'yes') {
    echo "С контрактом ознакомлен(а)";
} else {
    echo 'Подтвердите ознакомление с контрактом.';
    $errors = TRUE;
}
if ($errors) {
  exit();
}

$user = 'u68770'; 
$pass = '4643907'; 
$db = new PDO('mysql:host=localhost;dbname=test', $user, $pass,
  [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]); 

  try {
    $stmt = $db->prepare("INSERT INTO form (name_fio, phone, email,date_r, gender, biograf) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_name, $user_phone, $email, $dataBir, $gender, $biog]);

    $form_id = $db->lastInsertId();

 
    if (isset($_POST['languages'])) {
        foreach ($_POST['languages'] as $language_id) {
            $stmt = $db->prepare("INSERT INTO lang_check (check_id, language_id) VALUES (?, ?)");
            $stmt->execute([$form_id, $language_id]);
        }
    }

    echo 'Данные успешно сохранены.';
} catch (PDOException $e) {
    echo 'Ошибка: ' . $e->getMessage();
    exit();
}