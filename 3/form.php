<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" href="./style.css" />
	<title>3 FORM</title>
</head>

<body>

	<form class="decor" action="./index.php" method="POST">
		<div class="form-left-decoration"></div>
		<div class="form-right-decoration"></div>
		<div class="circle"></div>
		<div class="form-inner">
			<h1 id="zag_form">Заполнение формы</h1>

			<label for="user-fio">ФИО:</label><br />
			<input id="user-fio" name="user-fio" type="text" placeholder="Ваше полное имя" />
			<br />
			<label for="user-phone"> Номер телефона:</label><br />
			<input id="user-phone" name="user-phone" type="tel" placeholder="7 (999)999-99-99" required />
			<br />
			<label for="user-email"> Электронная почта</label><br />
			<input id="user-email" name="user-email" type="email" placeholder="example@example.example" />
			<br />
			<label for="data">Дата рождения:</label><br />
			<input id="data" name="data" type="date" />
			<br />
			<div class='pol'>
				<label>Ваш пол:</label>
				<div>
					<label for="male">Мужской</label>
					<input type="radio" id="male" name="gender" value="male" />
				</div>
				<div>
					<label for="female">Женский</label>
					<input type="radio" id="female" name="gender" value="female" />
				</div>
			</div>

			<br />

			<label for="languages">Любимые языки программирования:</label>
			<select id="languages" name="languages[]" multiple>
				<option value="1">Pascal</option>
				<option value="2">C</option>
				<option value="3">C++</option>
				<option value="4">JavaScript</option>
				<option value="5">PHP</option>
				<option value="6">Python</option>
				<option value="7">Java</option>
				<option value="8">Haskell</option>
				<option value="9">Clojure</option>
				<option value="10">Prolog</option>
				<option value="11">Scala</option>
			</select>

			<p>
				<label for="biograf">Биография:</label>
				<textarea id="biograf" name="biograf" rows="2" placeholder="Расскажите о себе"></textarea>
			</p>
			<div class="sog">
				<label for="agree"> с контрактом ознакомлен (а)</label>
				<input id="agree" name="agree" value="yes" type="checkbox" />
			</div>
			<br />
			<button type="submit" class="submit">Сохранить</button>
		</div>
	</form>
</body>

</html>