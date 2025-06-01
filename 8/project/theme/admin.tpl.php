<!DOCTYPE html>
<html>

<head>
	<meta charset="UTF-8">
	<title>Админ-панель</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="/backend-s0188267.github.io/project/styles/admin.css">
</head>
<style>
/* Основные стили */
:root {
	--primary: #6c5ce7;
	--primary-dark: #5649c0;
	--secondary: #00cec9;
	--danger: #ff7675;
	--danger-dark: #e84393;
	--text: #f5f6fa;
	--text-secondary: #dfe6e9;
	--bg: #2d3436;
	--bg-light: #636e72;
	--bg-dark: #1e272e;
	--border: #57606f;
	--shadow: rgba(0, 0, 0, 0.3);
}

body {
	font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
	background-color: var(--bg);
	color: var(--text);
	line-height: 1.6;
	margin: 0;
	padding: 0;
}

.admin-container {
	max-width: 1200px;
	margin: 0 auto;
	padding: 2rem;
}

h1,
h2 {
	color: var(--secondary);
	position: relative;
	padding-bottom: 0.5rem;
}

h1 {
	font-size: 2.5rem;
	margin-bottom: 2rem;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

h1::after {
	content: '';
	position: absolute;
	bottom: 0;
	left: 0;
	width: 100px;
	height: 3px;
	background: var(--primary);
}

h2 {
	font-size: 1.8rem;
	margin: 2rem 0 1rem;
}

/* Кнопки */
.button {
	display: inline-block;
	padding: 0.7rem 1.5rem;
	border-radius: 5px;
	text-decoration: none;
	font-weight: 600;
	transition: all 0.3s ease;
	text-align: center;
	cursor: pointer;
}

.admin-logout {
	background-color: var(--danger);
	color: white;
	border: none;
}

.admin-logout:hover {
	background-color: var(--danger-dark);
	transform: translateY(-2px);
}

.change_button a {
	background-color: var(--primary);
	color: white;
	padding: 0.5rem 1rem;
	border-radius: 5px;
	display: block;
	margin-bottom: 0.5rem;
}

.change_button a:hover {
	background-color: var(--primary-dark);
	transform: translateY(-2px);
}

.delete_button {
	background-color: var(--danger);
	color: white;
	border: none;
	padding: 0.5rem 1rem;
	border-radius: 5px;
	width: 100%;
	font-weight: 600;
	transition: all 0.3s ease;
}

.delete_button:hover {
	background-color: var(--danger-dark);
	transform: translateY(-2px);
}

/* Таблицы */
table {
	width: 100%;
	border-collapse: collapse;
	margin: 1.5rem 0;
	box-shadow: 0 4px 15px var(--shadow);
	border-radius: 10px;
	overflow: hidden;
}

thead {
	background-color: var(--primary);
	color: white;
}

th,
td {
	padding: 1rem;
	text-align: left;
	border-bottom: 1px solid var(--border);
}

tbody tr {
	background-color: var(--bg-dark);
	transition: background-color 0.3s ease;
}

tbody tr:nth-child(even) {
	background-color: var(--bg-light);
}

tbody tr:hover {
	background-color: var(--primary-dark);
	color: white;
}

.buttons {
	display: flex;
	flex-direction: column;
	gap: 0.5rem;
	min-width: 120px;
}

/* Статистика */
.stats {
	background-color: var(--bg-dark);
	padding: 1.5rem;
	border-radius: 10px;
	margin-bottom: 2rem;
	box-shadow: 0 4px 15px var(--shadow);
}

.stats h2 {
	margin-top: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
	.admin-container {
		padding: 1rem;
	}

	table {
		display: block;
		overflow-x: auto;
	}

	h1 {
		font-size: 2rem;
		flex-direction: column;
		align-items: flex-start;
		gap: 1rem;
	}

	.admin-logout {
		align-self: flex-end;
	}
}

/* Анимации */
@keyframes fadeIn {
	from {
		opacity: 0;
		transform: translateY(20px);
	}

	to {
		opacity: 1;
		transform: translateY(0);
	}
}

tbody tr {
	animation: fadeIn 0.5s ease forwards;
	opacity: 0;
}

tbody tr:nth-child(1) {
	animation-delay: 0.1s;
}

tbody tr:nth-child(2) {
	animation-delay: 0.2s;
}

tbody tr:nth-child(3) {
	animation-delay: 0.3s;
}

tbody tr:nth-child(4) {
	animation-delay: 0.4s;
}

tbody tr:nth-child(5) {
	animation-delay: 0.5s;
}

tbody tr:nth-child(n+6) {
	animation-delay: 0.6s;
}
</style>

<body>
	<div class="admin-container">
		<h1>Админ-панель</h1>
		<a href="/backend-s0188267.github.io/project/modules/logout.php" class="button admin-logout">Выйти</a>

		<div class="stats">
			<h2>Статистика по языкам программирования</h2>
			<table>
				<thead>
					<tr>
						<td>Язык</td>
						<td>Количество выборов</td>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($stats as $stat): ?>
					<tr>
						<td><?= htmlspecialchars($stat['name']) ?></td>
						<td><?= isset($stat['count']) ? (int)$stat['count'] : 0 ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<h2>Все заявки пользователей (всего: <?= count($processedApplications ?? []) ?>)</h2>
		<table>
			<thead>
				<tr>
					<td>ID</td>
					<td>Пользователь</td>
					<td>ФИО</td>
					<td>Email</td>
					<td>Телефон</td>
					<td>Дата рождения</td>
					<td>Пол</td>
					<td>Языки</td>
					<td>Биография</td>
					<td>Согласие</td>
					<td>Действия</td>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($processedApplications as $app): ?>
				<tr>
					<td><?= $app['id'] ?></td>
					<td><?= htmlspecialchars($app['user_login'] ?? 'N/A') ?></td>
					<td><?= htmlspecialchars($app['full_name'] ?? '-') ?></td>
					<td><?= htmlspecialchars($app['email'] ?? '-') ?></td>
					<td><?= htmlspecialchars($app['phone'] ?? '-') ?></td>
					<td><?= htmlspecialchars($app['birth_date'] ?? '-') ?></td>
					<td><?= htmlspecialchars($app['gender_short'] ?? '-') ?></td>
					<td><?= htmlspecialchars($app['languages'] ?? '-') ?></td>
					<td>
						<?= htmlspecialchars(substr($app['biography'] ?? '', 0, 50)) ?><?= strlen($app['biography'] ?? '') > 50 ? '...' : '' ?>
					</td>
					<td><?= isset($app['agreement']) && $app['agreement'] ? 'Да' : 'Нет' ?></td>
					<td class="buttons">
						<div class="change_button">
							<a href="/backend-s0188267.github.io/project/modules/edit.php?id=<?= $app['id'] ?>">Редактировать</a>
						</div>
						<button class="delete_button"
							onclick="if(confirm('Удалить эту заявку?')) location.href='/backend-s0188267.github.io/project/modules/delete.php?id=<?= $app['id'] ?>'">Удалить</button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</body>

</html>