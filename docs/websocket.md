# WebSocket Protocol

URL: `ws://<host>:6001`

## Handshake
Immediately after подключения клиент отправляет:
```json
{ "token": "<JWT>" }
```
Ответ сервера:
```json
{ "connected": true, "countChats": 17 }
```
Если токен неверен или не пришёл в течение 10 минут соединение закрывается с кодами 4001–4004.

## Входящие сообщения от клиента
После аутентификации клиент отправляет сообщения чата:
```json
{
  "chatPartnerId": 42,
  "text": "Hello"  
}
```
Сервер проксирует запрос в Symfony (`POST /api/messages`).

## Исходящие сообщения к клиенту
1. Чат-сообщение
```json
{
  "type": "CHAT_MESSAGE",
  "chatId": 7,
  "messageId": 123,
  "text": "Hello"
}
```
2. Технические уведомления (service-messages) посылаются **обоим** участникам при каждом отправленном сообщении.
Пример:
```json
{
  "type": "CHAT_META",
  "chatId": 7,
  "unreadCount": 0
}
```

## Вспомогательные HTTP-эндпоинты сервера
| Method | Path           | Описание                              |
|--------|----------------|---------------------------------------|
| GET    | /health        | Проверка статуса (200 OK)             |
| GET    | /connections   | Количество подключённых пользователей |
| POST   | /send          | Отправить raw-payload конкретному user |

Body для `/send`:
```json
{
  "recipient": 42,
  "type": "CHAT_MESSAGE",
  "data": {"text": "Ping"}
}
```

## Коды закрытия
| Code | Reason                    |
|------|---------------------------|
|4001 | Authentication timeout     |
|4002 | No token provided          |
|4003 | Invalid token              |
|4004 | Authentication failed      |
|4005 | Invalid JSON               |

