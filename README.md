# Starter Kit

Веб-приложение на Laravel 13 с PHP 8.5 и Vite. Локальная инфраструктура запускается через Docker Compose; для фронтенда используются Sass и Bootstrap.

## Стек

- PHP `^8.5`, Laravel `^13.0`
- MySQL 8.4 и Redis 7.4
- Node.js 22, Vite 8, Sass, Bootstrap 5
- Laravel Horizon и очереди Redis
- PHPUnit 12

Дополнительные библиотеки Laravel включают Scout, AdminLTE, Spatie Permission, ClickHouse Laravel client, S3-адаптер Flysystem и Log Viewer. Подключение ClickHouse или S3 требует соответствующей внешней инфраструктуры и настроек окружения.

## Требования

Для Docker-запуска нужны Docker и Docker Compose v2. Для запуска без Docker установите PHP 8.4 с расширениями из `composer.json`, Composer 2, Node.js 22+, MySQL 8.4 и Redis 7.

## Быстрый старт в Docker

```sh
cp .env.example .env
```

При необходимости измените `.env`: Makefile читает его, а Docker Compose использует значения для конфигурации сервисов. Далее запустите проект:

```sh
make up-local
make php-local
```

В PHP-контейнере:

```sh
composer install
php artisan key:generate
php artisan migrate
```

В отдельном терминале установите JS-зависимости и запустите Vite:

```sh
make install-local
make dev-local
```

Приложение доступно через Nginx на `http://localhost`. Compose публикует порт `3000` для node-контейнера; Vite по умолчанию использует `5173`, поэтому для HMR в Docker согласуйте порт и host в настройках Vite и Compose. Чтобы собрать ассеты для production, выполните `make build-local`. Остановить локальные контейнеры можно командой `make down-local`.

В `.env.example` задан `APP_URL=http://project-x.loc`. Если браузер или HMR используют это имя, добавьте `127.0.0.1 project-x.loc` в hosts либо настройте `APP_URL` под своё окружение.

## Запуск без Docker

```sh
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
```

Убедитесь, что MySQL и Redis запущены и их адреса/учётные данные указаны в `.env`. Затем в разных терминалах запустите сервер Laravel и Vite:

```sh
php artisan serve
npm run dev
```

Laravel будет доступен на `http://127.0.0.1:8000`, Vite dev-сервер использует порт `5173` по умолчанию (если он не занят). Production-сборка: `npm run build`.

## Команды

### Makefile

Локальные команды: `up-local`, `down-local`, `php-local`, `install-local`, `dev-local`, `build-local`.

Для stage-окружения предусмотрены `up-stage`, `down-stage`, `php-stage`, `deploy-stage`, `reload-env-stage`; для production — `up-prod`, `down-prod`, `php-prod`, `deploy-prod`, `reload-env-prod`, `logs-prod`, `status-prod`. Эти команды проверяют `APP_ENV` из `.env`. Stage и production используют отдельные Compose-файлы `docker-compose-stage.yml` и `docker-compose-prod.yml` и требуют подготовленной инфраструктуры и доступов.

### Composer и npm

```sh
php artisan migrate
php artisan queue:work
php artisan horizon
php artisan test
npm run dev
npm run build
```

## Конфигурация

Полный список переменных находится в `.env.example`. Основные настройки:

- `APP_*` — приложение, URL, локаль и часовой пояс
- `DB_*` — подключение MySQL
- `REDIS_*`, `CACHE_STORE`, `QUEUE_CONNECTION` — Redis, кэш и очереди
- `SESSION_*` — сессии
- `MAIL_*` — почта (по умолчанию используется `log`)
- `FILESYSTEM_DISK` — файловое хранилище (в примере настроен `s3`)
- `VITE_APP_NAME` — имя приложения для фронтенда
- `YOUTUBE_API_KEY` — ключ YouTube API

Не храните секреты в репозитории. Для S3 заполните параметры диска в окружении/конфигурации Laravel и укажите действующий bucket и endpoint.

## Структура

- `app/` — код приложения
- `routes/web.php`, `routes/console.php` — маршруты
- `resources/js/`, `resources/sass/`, `resources/views/` — фронтенд и Blade-шаблоны
- `database/` — миграции, фабрики и сиды
- `docker/`, `docker-compose*.yml` — контейнеры и их конфигурация
- `.gitlab-ci.yml` — CI/CD для тестирования, сборки и развертывания

## Тесты

Запускайте тесты командой `php artisan test` или `./vendor/bin/phpunit`. Параметры PHPUnit заданы в `phpunit.xml`; для тестового запуска проверьте доступность и настройки указанной там базы данных.

## Лицензия

В `composer.json` указана лицензия MIT.
