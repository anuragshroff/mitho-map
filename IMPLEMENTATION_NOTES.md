# Mitho Map Implementation Notes

This document tracks the implemented work for authentication, onboarding, admin operations, order assignment, and order chat.

## 1. Implemented Scope

### 1.1 Authentication and onboarding

- Phone-based verification (OTP via WhatsApp sender abstraction)
- Registration with required verified phone and required default address
- Social login with `google`, `facebook`, and `apple`
- Apple token signature verification against Apple public keys
- `auth/me` API to return profile + default address + linked social accounts
- Sanctum token issuance with role-based abilities

### 1.2 Admin and order operations

- Admin seeder and admin config (`ADMIN_*` env values)
- Admin order management page improvements:
- Status update
- Driver assignment with max active assignment guard
- Assignment metadata persisted (`assigned_by`, `assigned_at`)
- Typed order chat UI in admin (`User ↔ Admin`, `Admin ↔ Driver`)
- Realtime chat updates (Echo/Reverb channel subscription if Echo is present, polling fallback)

### 1.3 Order chat backend

- Conversation model with typed threads:
- `user_driver`
- `user_admin`
- `admin_driver`
- Conversation-scoped authorization for read/write
- Reverb event broadcast per conversation thread

## 2. Key Files

- API routes: `routes/api.php`
- Broadcast channels: `routes/channels.php`
- Admin order web routes: `routes/web.php`
- Auth controllers:
- `app/Http/Controllers/Api/V1/PhoneVerificationController.php`
- `app/Http/Controllers/Api/V1/AuthRegistrationController.php`
- `app/Http/Controllers/Api/V1/SocialAuthController.php`
- `app/Http/Controllers/Api/V1/AuthTokenController.php`
- `app/Http/Controllers/Api/V1/CurrentUserController.php`
- Chat controller:
- `app/Http/Controllers/Api/V1/OrderChatController.php`
- Admin order controller:
- `app/Http/Controllers/Admin/AdminOrderController.php`
- Services:
- `app/Services/Auth/PhoneVerificationCodeService.php`
- `app/Services/Auth/SocialIdentityResolver.php`
- `app/Services/Messaging/WhatsAppVerificationSender.php`
- Event:
- `app/Events/OrderChatMessageSent.php`
- Admin page:
- `resources/js/pages/admin/orders.tsx`

## 3. Environment Configuration

Use `.env.example` as baseline. Important keys:

### 3.1 Admin seeding

- `ADMIN_NAME`
- `ADMIN_EMAIL`
- `ADMIN_PHONE`
- `ADMIN_PASSWORD`

### 3.2 Driver assignment

- `FOOD_DRIVER_MAX_ACTIVE_ASSIGNMENTS`

### 3.3 Reverb / broadcasting

- `BROADCAST_CONNECTION=reverb`
- `REVERB_APP_ID`
- `REVERB_APP_KEY`
- `REVERB_APP_SECRET`
- `REVERB_HOST`
- `REVERB_PORT`
- `REVERB_SCHEME`
- `REVERB_SERVER_HOST`
- `REVERB_SERVER_PORT`
- `VITE_REVERB_APP_KEY`
- `VITE_REVERB_HOST`
- `VITE_REVERB_PORT`
- `VITE_REVERB_SCHEME`

### 3.4 WhatsApp verification sender

- `WHATSAPP_DRIVER` (`log` for local/no provider)
- `WHATSAPP_WEBHOOK_URL`
- `WHATSAPP_TOKEN`
- `WHATSAPP_FROM`
- `WHATSAPP_TIMEOUT`
- `WHATSAPP_VERIFICATION_TTL_MINUTES`
- `WHATSAPP_VERIFICATION_MAX_ATTEMPTS`

### 3.5 Apple social login

- `APPLE_CLIENT_ID`
- `APPLE_CLIENT_IDS` (comma-separated, preferred for multiple app IDs)

## 4. Data Model Additions

### 4.1 User and auth

- `users.phone` (unique, nullable)
- `users.phone_verified_at`
- `phone_verification_codes`
- `social_accounts`
- `user_addresses`

### 4.2 Order chat and assignment

- `order_conversations`
- `order_chat_messages`
- `orders.assigned_by`
- `orders.assigned_at`

## 5. API Contracts

Base prefix: `/api/v1`

### 5.1 Phone verification

#### `POST /auth/phone/send-code`

Body:

```json
{
  "phone": "+9779812345678"
}
```

Returns `202` when accepted.

#### `POST /auth/phone/verify-code`

Body:

```json
{
  "phone": "+9779812345678",
  "code": "123456"
}
```

Returns `200` when code is valid.

### 5.2 Registration

#### `POST /auth/register`

Required fields:

- `name`
- `email`
- `phone`
- `password`
- `password_confirmation`
- `verification_code`
- `device_name`
- `address` object with `line_1`, `city`, `state`, `postal_code`, `country`

Returns `201` with bearer token and created user payload.

### 5.3 Social login

#### `POST /auth/social/login`

Required fields:

- `provider`: `google|facebook|apple`
- `provider_token`
- `device_name`

Conditional for first-time creation without known user phone:

- `phone`
- `verification_code`

Optional:

- `name`
- `address`

Returns bearer token and normalized user payload.

### 5.4 Auth me

#### `GET /auth/me` (Sanctum)

Returns:

- user core profile
- default address
- linked social accounts

### 5.5 Order chat

#### `GET /orders/{order}/chat/messages` (Sanctum + ability)

Query:

- `conversation_type` required, one of:
- `user_driver`
- `user_admin`
- `admin_driver`

#### `POST /orders/{order}/chat/messages` (Sanctum + ability)

Body:

```json
{
  "conversation_type": "user_admin",
  "message": "Need support help"
}
```

Message max length: `2000`.

## 6. Conversation Authorization Rules

Enforced in `OrderChatController` and broadcast channel authorization:

- `user_driver`: customer or assigned driver
- `user_admin`: customer or admin
- `admin_driver`: assigned driver or admin
- If conversation requires driver and order has no driver, access is blocked

## 7. Reverb Channels and Events

### 7.1 Channels

- `orders.{orderId}.conversation.{conversationType}`

### 7.2 Event

- Class: `OrderChatMessageSent`
- Broadcast name: `order.chat.message.sent`

### 7.3 Admin frontend behavior

In `resources/js/pages/admin/orders.tsx`:

- Loads thread messages by conversation type
- Sends messages with selected conversation type
- Realtime update path:
- subscribes via `window.Echo.private(channelName)` when Echo is available
- listens to `.order.chat.message.sent`
- appends message if not already present
- Fallback: polling every 7 seconds when Echo is unavailable

## 8. Admin Driver Assignment Rules

In `AdminOrderController@assignDriver`:

- Counts active assignments for selected driver
- Compares against `food.driver_max_active_assignments`
- Blocks assignment if limit reached
- Stores assignment metadata:
- `assigned_by` = acting admin
- `assigned_at` = current timestamp

In admin orders UI:

- Busy drivers are shown with active count
- Busy drivers are disabled in assign dropdown (except current assigned driver)

## 9. Admin Seeder

### 9.1 Seeder

- `database/seeders/AdminUserSeeder.php`

### 9.2 Config

- `config/admin.php` reads `ADMIN_*` env values

### 9.3 Database seeder integration

- `DatabaseSeeder` calls `AdminUserSeeder`

### 9.4 Run

```bash
php artisan db:seed --class=AdminUserSeeder --no-interaction
```

or full seed:

```bash
php artisan db:seed --no-interaction
```

## 10. Tests Added

- `tests/Feature/Api/AuthRegistrationAndSocialAuthTest.php`
- `tests/Feature/Api/OrderChatAndAssignmentTest.php`
- `tests/Feature/Admin/AdminUserSeederTest.php`

## 11. Known Environment Caveat

Current local test run may fail if SQLite PDO extension is missing:

- Error: `could not find driver (Connection: sqlite, Database: :memory:)`

Enable/install `pdo_sqlite` in PHP runtime, then run:

```bash
php artisan test --compact tests/Feature/Api/AuthRegistrationAndSocialAuthTest.php
php artisan test --compact tests/Feature/Api/OrderChatAndAssignmentTest.php
php artisan test --compact tests/Feature/Admin/AdminUserSeederTest.php
```

## 12. Rollout Checklist

1. Set env variables in production for WhatsApp, Apple IDs, and Reverb.
2. Run migrations.
3. Run admin seeder.
4. Start queue/broadcast workers as needed.
5. Verify API auth flow from mobile clients.
6. Verify admin chat realtime path with Reverb/Echo client configured.
