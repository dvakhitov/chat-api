# WebSocket + API проект

Этот репозиторий содержит инфраструктуру и код для двух связанных сервисов:

1. **Symfony (PHP 8)** — REST / GraphQL API и бизнес-логика.
2. **Node.js WebSocket-сервер** — отправка клиентам real-time уведомлений.

Сервисы упакованы в Docker и запускаются одной командой:

```bash
docker compose up -d
```

После запуска:
* API доступен по адресу `http://localhost` (порт проксируется через Nginx).
* WebSocket-соединения принимаются на `ws://localhost:8080`.

Файлы `compose.yaml` и `compose-prod.yaml` позволяют запускать стек в режиме разработки и в production-окружении соответственно.

## Документация

Полное описание REST-API, WebSocket-протокола и переменных окружения находится в каталоге `docs/`:

* `docs/api.md` — REST-эндпоинты Symfony
* `docs/websocket.md` — протокол WebSocket и жизненный цикл соединения
* `docs/openapi.yaml` — спецификация OpenAPI 3.1

## Секреты (`secrets/`)

Папка `secrets/` исключена из Git для настоящих секретов, но содержит **шаблоны** `*.dist`.
После клонирования выполните:

```bash
cp secrets/app_secret.txt.dist secrets/app_secret.txt
cp secrets/db_password.txt.dist secrets/db_password.txt
```

и замените значения внутри на реальные.

Docker Compose считывает содержимое этих файлов (`*.txt`) и передаёт значения контейнерам как переменные среды.

В production вместо этой папки рекомендуется использовать Docker/Swarm secrets или переменные среды, хранимые в CI/CD (Vault, GitHub Secrets, GitLab CI Variables и т. д.).

