CREATE TABLE form(
	id INT AUTO_INCREMENT PRIMARY KEY,
  name_fio VARCHAR(150) NOT NULL,
  phone VARCHAR(15) NOT NULL,
  email VARCHAR(100) NOT NULL,
	date_r DATE NOT NULL,
	gender ENUM('male', 'female') NOT NULL,
	biograf text NOT NULL,
	contract_accepted TINYINT(1) NOT NULL,
);

CREATE TABLE lang(
	id INT AUTO_INCREMENT PRIMARY KEY,
	name_lang VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE lang_check(
	check_id INT(10) unsigned NOT NULL,
	language_id INT(10) UNSIGNED NOT NULL,
	PRIMARY KEY (check_id,language_id),
	FOREIGN KEY check_id REFERENCES form(id),
	FOREIGN KEY language_id REFERENCES lang(id_lang)
);

INSERT INTO lang (name_lang) VALUES
('Pascal'),
('C'),
('C++'),
('JavaScript'),
('PHP'),
('Python'),
('Java'),
('Haskell'),
('Clojure'),
('Prolog'),
('Scala'),
('Go');
