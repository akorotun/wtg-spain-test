# Accommodation API

REST API на Laravel 12 для асинхронного імпорту пропозицій житла, пошуку актуальних пропозицій та бронювання.

## Вимоги

- Docker
- Docker Compose

## Встановлення та запуск

Клонувати репозиторій:

```bash
git clone https://github.com/akorotun/wtg-spain-test.git
cd wtg-spain-test
```

Створити локальні файли конфігурації:

```bash
cp .env.example .env
cp .env.testing.example .env.testing
```

Зібрати та запустити Docker-контейнери:

```bash
docker compose up -d --build app mysql nginx mysql-test
```

Встановити PHP-залежності:

```bash
docker compose exec app composer install
```

Згенерувати ключі застосунку:

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan key:generate --env=testing
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

Запустити seeders (створює двох постачальників - supplier-a та supplier-b):
```bash
docker compose exec app php artisan db:seed
```

Додатковий seeder для генерації тестового набору даних для перевірки пошуку:
```bash
docker compose exec app php artisan db:seed --class=PropertySearchPerformanceSeeder
```

## Queue worker

```bash
docker compose up -d queue
```

## API endpoints

- `POST /api/imports`
- `GET /api/imports/{import}`
- `GET /api/properties`
- `POST /api/offers/{offer}/reservations`


## Тести
Тести покривають основні сценарії імпорту, пошуку житла та бронювання.

Тести використовують окрему MySQL базу даних у контейнері `mysql-test`.

Запустити тести:

```bash
docker compose exec app php artisan test --env=testing
```

## Ідемпотентність імпорту

- комбінація `(supplier_id, external_import_id)` унікальна;
- повторний запит не створює новий `Import`;
- Job повторно не dispatch-иться;
- `Offer` ідентифікується за `(supplier_id, external_id)` і оновлюється через `upsert`.

## Захист від двох одночасних бронювань останньої одиниці

- відкривається DB transaction;
- Offer читається через lockForUpdate();
- перевіряється available_units;
- створюється Reservation і зменшується available_units;
- паралельна транзакція чекає блокування й після цього бачить вже оновлену кількість, тому не може забронювати останню одиницю вдруге.


## Пошук найдешевшої пропозиції

Endpoint `GET /api/properties`.

Вибір найдешевшої актуальної пропозиції для кожного Property,
сортування результатів та пагінація виконуються на рівні бази даних.
Усі пропозиції не завантажуються в пам'ять для подальшого групування через PHP Collections.

При пошуку враховуються:
- check_in та check_out;
- мінімальна кількість гостей (max_guests >= guests);
- наявність вільних одиниць (available_units > 0);
- термін дії пропозиції (expires_at > now());
- місто, якщо передано параметр city.


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
