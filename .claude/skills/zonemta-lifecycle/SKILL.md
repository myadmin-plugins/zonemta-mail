---
name: zonemta-lifecycle
description: Implements a service lifecycle event handler (activate/reactivate/deactivate/terminate) in `src/Plugin.php` following the MongoDB + MySQL dual-write pattern. Use when user says 'add lifecycle handler', 'implement activate', 'add deactivate logic', or modifies `getActivate`/`getReactivate`/`getDeactivate`/`getTerminate`. Covers MongoDB findOne/insertOne/updateOne/deleteOne, get_module_db() SQL update, myadmin_log, request_log, mail_welcome_email, and stopPropagation. Do NOT use for getSettings or apiRegister.
---
# ZoneMTA Lifecycle Handler

## Critical

- Every handler MUST guard with `if (in_array($event['type'], [get_service_define('MAIL_ZONEMTA')]))` before any logic.
- Always call `$event->stopPropagation()` — both on early-return error paths AND at the end of normal flow.
- Never interpolate raw user input into SQL — always `$db->real_escape()` first.
- MongoDB connection string MUST use `rawurlencode(ZONEMTA_PASSWORD)` — passwords may contain special characters.
- Username format is always `'mb' . $serviceClass->getId()` (e.g. `mb123`). Never derive it differently in activate.
- For reactivate, prefer existing username: `$serviceClass->getUsername() == '' ? 'mb'.$serviceClass->getId() : $serviceClass->getUsername()`.

## Instructions

1. **Open `src/Plugin.php`** and locate the method to modify (`getActivate`, `getReactivate`, `getDeactivate`, or `getTerminate`). All methods are `public static` and accept `GenericEvent $event`.

2. **Add the service-type guard** as the first statement inside the method body:
   ```php
   if (in_array($event['type'], [get_service_define('MAIL_ZONEMTA')])) {
       $serviceClass = $event->getSubject();
       $settings = get_module_settings(self::$module);
   ```
   Verify `get_service_define('MAIL_ZONEMTA')` is the correct constant for this plugin type.

3. **Obtain/generate credentials** (activate and reactivate only):
   ```php
   $username = 'mb'.$serviceClass->getId(); // activate
   // reactivate: $username = $serviceClass->getUsername() == '' ? 'mb'.$serviceClass->getId() : $serviceClass->getUsername();
   $password = mail_get_password($serviceClass->getId(), $serviceClass->getCustid());
   if ($password === false || trim($password) == '') {
       function_requirements('generate_password');
       $password = generate_password(20, 'lud');
   }
   $GLOBALS['tf']->history->add($settings['PREFIX'], 'password', $serviceClass->getId(), $password);
   ```

4. **Connect to MongoDB** and select the collection (all handlers):
   ```php
   $client = new \MongoDB\Client('mongodb://'.ZONEMTA_USERNAME.':'.rawurlencode(ZONEMTA_PASSWORD).'@'.ZONEMTA_HOST.':27017/');
   $users = $client->selectDatabase('zone-mta')->selectCollection('users');
   ```

5. **Perform the MongoDB operation** based on handler type:

   **activate/reactivate** — findOne then insertOne or updateOne:
   ```php
   $data = ['username' => $username, 'password' => $password];
   $result = $users->findOne(['username' => $username]);
   if (is_null($result)) {
       $result = $users->insertOne($data);
       request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'zonemta', 'insert', $data, $result, $serviceClass->getId());
       myadmin_log('myadmin', 'info', 'ZoneMTA insert '.json_encode($data).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
       if ($result->getInsertedCount() == 0) {
           $event['success'] = false;
           myadmin_log('zonemta', 'error', 'Error Creating User '.$username, __LINE__, __FILE__, self::$module, $serviceClass->getId());
           $event->stopPropagation();
           return;
       }
   } else {
       myadmin_log('myadmin', 'info', 'ZoneMTA found existing entry for '.json_encode($data).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
       if ($result['password'] != $password) {
           myadmin_log('myadmin', 'info', 'ZoneMTA updating user '.$username.' password to '.$password, __LINE__, __FILE__, self::$module, $serviceClass->getId());
           $users->updateOne(['username' => $username], ['$set' => ['password' => $password]]);
       }
   }
   ```

   **deactivate/terminate** — deleteOne only:
   ```php
   $data = ['username' => $serviceClass->getUsername()];
   $result = $users->deleteOne($data);
   request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'zonemta', 'delete', $data, $result, $serviceClass->getId());
   myadmin_log('myadmin', 'info', 'ZoneMTA delete '.json_encode($data).' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
   ```

6. **Write back username to MySQL** (activate and reactivate only, after successful MongoDB op):
   ```php
   $db = get_module_db(self::$module);
   $username = $db->real_escape($username);
   $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_username='{$username}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
   ```

7. **Send welcome email** (activate and reactivate only):
   ```php
   mail_welcome_email($serviceClass->getId());
   ```

8. **Close the guard block and stop propagation**:
   ```php
   } // end if MAIL_ZONEMTA
   $event['success'] = true;  // activate only — outside the if block
   $event->stopPropagation();
   ```
   Note: `getActivate` sets `$event['success'] = true` outside the guard. `getReactivate`/`getDeactivate`/`getTerminate` call `stopPropagation()` inside the guard only.

9. **Verify** by running `vendor/bin/phpunit` — all existing tests must pass.

## Examples

**User says:** "Add deactivate logic to remove the MongoDB user"

**Actions taken:**
1. Locate `getDeactivate` in `src/Plugin.php:178`
2. Add guard, get `$serviceClass`, build MongoDB client, call `deleteOne(['username' => $serviceClass->getUsername()])`
3. Call `request_log(...)` with `'delete'` action, call `myadmin_log(...)`, call `$event->stopPropagation()`

**Result** (`src/Plugin.php:178`):
```php
public static function getDeactivate(GenericEvent $event)
{
    if (in_array($event['type'], [get_service_define('MAIL_ZONEMTA')])) {
        $serviceClass = $event->getSubject();
        myadmin_log('myadmin', 'info', 'ZoneMTA Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $client = new \MongoDB\Client('mongodb://'.ZONEMTA_USERNAME.':'.rawurlencode(ZONEMTA_PASSWORD).'@'.ZONEMTA_HOST.':27017/');
        $users = $client->selectDatabase('zone-mta')->selectCollection('users');
        $data = ['username' => $serviceClass->getUsername()];
        $result = $users->deleteOne($data);
        request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'zonemta', 'delete', $data, $result, $serviceClass->getId());
        myadmin_log('myadmin', 'info', 'ZoneMTA delete '.json_encode($data).' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
        $event->stopPropagation();
    }
}
```

## Common Issues

- **`MongoDB\Driver\Exception\AuthenticationException: Authentication failed`**: `ZONEMTA_PASSWORD` contains special characters and was not URL-encoded. Fix: ensure connection string uses `rawurlencode(ZONEMTA_PASSWORD)`, not bare `ZONEMTA_PASSWORD`.
- **Event propagation continues after error return**: forgot `$event->stopPropagation()` before `return` on the `insertOne` failure path. Every early return must call `stopPropagation()` first.
- **`$result->getInsertedCount()` call on null**: `insertOne` was not called because the `is_null($result)` branch was skipped. Verify `findOne` is called before `insertOne`, and that the null check is `is_null($result)` not `$result === false`.
- **SQL injection / `real_escape` missing**: always call `$db->real_escape($username)` before interpolating into the `UPDATE` query. The reassignment `$username = $db->real_escape($username)` is intentional.
- **`getUsername()` returns empty string on deactivate**: if the service was never activated, `$serviceClass->getUsername()` may be empty and `deleteOne` will match nothing. This is acceptable — log it and continue.
- **Tests fail after editing**: run `vendor/bin/phpunit` from the plugin root (not the parent MyAdmin root). Bootstrap is `tests/bootstrap.php` as configured in `phpunit.xml.dist`.