# Accommodation API

REST API на Laravel 12 для асинхронного імпорту пропозицій житла, пошуку актуальних пропозицій та бронювання.

## Вимоги

- Docker
- Docker Compose

## Встановлення та запуск

Клонувати репозиторій:

```bash
git clone <repository-url>
cd wtg-spain-test
```

Створити локальний файл конфігурації:

```bash
cp .env.example .env
```

Зібрати та запустити Docker-контейнери:

```bash
docker compose up -d --build
```

Встановити PHP-залежності:

```bash
docker compose exec app composer install
```

Згенерувати ключ застосунку:

```bash
docker compose exec app php artisan key:generate
```

Виконати міграції:

```bash
docker compose exec app php artisan migrate
```

Застосунок буде доступний за адресою:

```text
http://wtg-spain-test.localhost:8080
```

## Міграції та seeders

Буде доповнено під час реалізації.

## Queue worker

Буде доповнено під час реалізації.

## Тести

Буде доповнено під час реалізації.

## Ідемпотентність імпорту

Буде доповнено після реалізації імпорту.

## Захист від двох одночасних бронювань останньої одиниці
DB transaction + SELECT FOR UPDATE / lockForUpdate()

## Додаткова документація

Опис предметної області та прийнятих припущень:

`docs/domain-model.md`

## Технічний стек

- PHP 8.2
- Laravel 12
- MySQL 8.4
- Nginx 1.27
- Laravel Queue з `database` driver
- Docker / Docker Compose
- Composer 2

Frontend у межах тестового завдання не використовується.
Redis не використовується — черга працює через MySQL.
