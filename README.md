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
docker compose up -d --build app mysql nginx
```

Встановити PHP-залежності:

```bash
docker compose exec app composer install
```

Згенерувати ключ застосунку:

```bash
docker compose exec app php artisan key:generate
```

Застосунок буде доступний за адресою:

```text
http://wtg-spain-test.localhost:8080
```

## Міграції та seeders

Виконати міграції:

```bash
docker compose exec app php artisan migrate
```

Запустити seeders:
```bash
docker compose exec app php artisan db:seed
```

## Queue worker

```bash
docker compose up -d queue
```

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
