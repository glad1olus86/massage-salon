# Разрешения и роли модуля Кассы

## Обзор системы

Модуль кассы использует **Spatie Laravel Permission** для управления доступом. Система построена на двух уровнях:

1. **Разрешения (Permissions)** - конкретные действия, которые можно выполнять
2. **Роли (Roles)** - наборы разрешений, назначаемые пользователям

---

## Разрешения кассы

| Разрешение | Описание | Кто имеет |
|------------|----------|-----------|
| `cashbox_access` | Доступ к модулю кассы (просмотр страниц) | Boss, Manager, Curator |
| `cashbox_deposit` | Внесение денег в кассу | Только Boss |
| `cashbox_distribute` | Выдача денег получателям | Boss, Manager, Curator |
| `cashbox_refund` | Возврат денег вышестоящему | Boss, Manager, Curator |
| `cashbox_self_salary` | Выдача зарплаты себе | Boss, Manager |
| `cashbox_edit_frozen` | Редактирование замороженных периодов | Только Boss |
| `cashbox_view_audit` | Просмотр аудита кассы | Только Boss |

---

## Роли кассы

### Boss (Директор)
Полный доступ ко всем функциям кассы.

**Разрешения:**
- `cashbox_access` ✅
- `cashbox_deposit` ✅
- `cashbox_distribute` ✅
- `cashbox_refund` ✅
- `cashbox_self_salary` ✅ (без лимита)
- `cashbox_edit_frozen` ✅
- `cashbox_view_audit` ✅

**Может выдавать деньги:**
- Manager'ам
- Curator'ам
- Worker'ам
- Себе (без ограничений)

---

### Manager (Менеджер)
Средний уровень доступа.

**Разрешения:**
- `cashbox_access` ✅
- `cashbox_deposit` ❌
- `cashbox_distribute` ✅
- `cashbox_refund` ✅
- `cashbox_self_salary` ✅ (1 раз за период)
- `cashbox_edit_frozen` ❌
- `cashbox_view_audit` ❌

**Может выдавать деньги:**
- Curator'ам
- Worker'ам
- Себе (только 1 раз за период!)

**НЕ может выдавать:**
- Другим Manager'ам
- Boss'у

---

### Curator (Куратор)
Минимальный уровень доступа.

**Разрешения:**
- `cashbox_access` ✅
- `cashbox_deposit` ❌
- `cashbox_distribute` ✅
- `cashbox_refund` ✅
- `cashbox_self_salary` ❌
- `cashbox_edit_frozen` ❌
- `cashbox_view_audit` ❌

**Может выдавать деньги:**
- Только Worker'ам

**НЕ может выдавать:**
- Другим Curator'ам
- Manager'ам
- Boss'у
- Себе

---

## Иерархия распределения денег

```
        Boss (Директор)
       /      |       \
      ↓       ↓        ↓
  Manager  Manager  Curator
     |        |        |
     ↓        ↓        ↓
  Curator  Curator  Worker
     |        |
     ↓        ↓
  Worker   Worker
```

**Правила:**
- Деньги идут только ВНИЗ по иерархии
- Возврат возможен только ВВЕРХ (тому, кто дал)
- Manager не может дать другому Manager'у
- Curator не может дать другому Curator'у

---

## Команды Tinker для управления ролями

### Проверить роли пользователя
```php
$user = User::find(ID);
$user->getRoleNames();
```

### Назначить роль Boss
```php
$user = User::find(ID);
$user->assignRole('boss');
```

### Назначить роль Manager
```php
$user = User::find(ID);
$user->assignRole('manager');
```

### Назначить роль Curator
```php
$user = User::find(ID);
$user->assignRole('curator');
```

### Убрать роль
```php
$user = User::find(ID);
$user->removeRole('manager');
```

### Проверить разрешения пользователя
```php
$user = User::find(ID);
$user->getAllPermissions()->pluck('name');
```

### Проверить конкретное разрешение
```php
$user = User::find(ID);
$user->can('cashbox_deposit'); // true/false
```

---

## Создание ролей для новой компании

Роли создаются автоматически при миграции, но если нужно создать вручную:

```php
use Spatie\Permission\Models\Role;

$companyId = 1; // ID компании

// Boss
$bossRole = Role::firstOrCreate(
    ['name' => 'boss', 'created_by' => $companyId]
);
$bossRole->syncPermissions([
    'cashbox_access',
    'cashbox_deposit',
    'cashbox_distribute',
    'cashbox_refund',
    'cashbox_self_salary',
    'cashbox_edit_frozen',
    'cashbox_view_audit',
]);

// Manager
$managerRole = Role::firstOrCreate(
    ['name' => 'manager', 'created_by' => $companyId]
);
$managerRole->syncPermissions([
    'cashbox_access',
    'cashbox_distribute',
    'cashbox_refund',
    'cashbox_self_salary',
]);

// Curator
$curatorRole = Role::firstOrCreate(
    ['name' => 'curator', 'created_by' => $companyId]
);
$curatorRole->syncPermissions([
    'cashbox_access',
    'cashbox_distribute',
    'cashbox_refund',
]);
```

---

## Важные замечания

1. **Company type = Boss**: Пользователи с `type = 'company'` автоматически считаются Boss'ами, даже без явной роли.

2. **Роли привязаны к компании**: Роли создаются с `created_by` = ID компании для мультитенантности.

3. **ЗП себе для Manager**: Manager может взять ЗП себе только 1 раз за период. Это проверяется в `CashboxService::hasSelfSalaryThisPeriod()`.

4. **Возврат денег**: Можно вернуть деньги только тому, кто их дал (проверяется `parent_transaction_id`).

5. **Worker - не User**: Worker'ы - это отдельная модель, не пользователи системы. Они только получают деньги, не могут их распределять.
