# MyAdmin ZoneMTA Mail Plugin

PHP plugin providing ZoneMTA-based SMTP mail provisioning via MongoDB user management, integrated into the MyAdmin event system.

## Commands

```bash
composer install           # install deps
vendor/bin/phpunit         # run tests (bootstrap: tests/bootstrap.php)
```

## Architecture

**Entry:** `src/Plugin.php` — single class, all methods static
**Namespace:** `Detain\MyAdminZoneMTAMail\` → `src/`
**Tests:** `tests/PluginTest.php` · config `phpunit.xml.dist` · bootstrap `tests/bootstrap.php`

**Hook registration** (`getHooks()` in `src/Plugin.php`):
- `mail.settings` → `getSettings`
- `mail.activate` → `getActivate`
- `mail.reactivate` → `getReactivate`
- `mail.deactivate` → `getDeactivate`
- `mail.terminate` → `getTerminate`
- `api.register` → `apiRegister`
- `function.requirements` → `getRequirements`
- `ui.menu` → `getMenu`

**All event handlers** accept `GenericEvent $event` (Symfony EventDispatcher 5.x), guard on `get_service_define('MAIL_ZONEMTA')`, and call `$event->stopPropagation()`.

**MongoDB pattern** (used in activate/reactivate/deactivate/terminate):
```php
$client = new \MongoDB\Client('mongodb://'.ZONEMTA_USERNAME.':'.rawurlencode(ZONEMTA_PASSWORD).'@'.ZONEMTA_HOST.':27017/');
$users = $client->selectDatabase('zone-mta')->selectCollection('users');
$result = $users->findOne(['username' => $username]);   // check exists
$result = $users->insertOne($data);                     // create
$result = $users->updateOne(['username' => $username], ['$set' => ['password' => $password]]); // update
$result = $users->deleteOne(['username' => $username]); // remove
```

**MySQL update pattern** (after MongoDB op in activate/reactivate):
```php
$db = get_module_db(self::$module);
$username = $db->real_escape($username);
$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_username='{$username}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
```

**Username format:** `'mb' . $serviceClass->getId()` (e.g. `mb123`)

**Logging:** `myadmin_log('myadmin', 'info', 'ZoneMTA ...', __LINE__, __FILE__, self::$module, $serviceClass->getId())`

**Request logging:** `request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'zonemta', 'insert'|'delete', $data, $result, $serviceClass->getId())`

## Settings Keys (registered in `getSettings()`)

- `ZONEMTA_HOST`, `ZONEMTA_HOST2`, `ZONEMTA_USERNAME`, `ZONEMTA_PASSWORD`
- `ZONEMTA_CLICKHOUSE_HOST`, `ZONEMTA_CLICKHOUSE_PORT`
- `ZONEMTA_MYSQL_HOST`, `ZONEMTA_MYSQL_PORT`, `ZONEMTA_MYSQL_DB`, `ZONEMTA_MYSQL_USERNAME`, `ZONEMTA_MYSQL_PASSWORD`
- `ZONEMTA_RSPAMD_MYSQL_HOST`, `ZONEMTA_RSPAMD_MYSQL_PORT`, `ZONEMTA_RSPAMD_MYSQL_DB`, `ZONEMTA_RSPAMD_MYSQL_USERNAME`, `ZONEMTA_RSPAMD_MYSQL_PASSWORD`
- `MXTOOLBOX_AUTH_TOKEN`

## Conventions

- All `Plugin` methods are `public static` except `__construct`
- Static properties: `$name`, `$description`, `$help`, `$module = 'mail'`, `$type = 'service'`
- Never use PDO — use `get_module_db(self::$module)` for MySQL
- Always `$db->real_escape()` on user input before SQL interpolation
- `get_module_settings(self::$module)` returns `['PREFIX' => 'mail', 'TABLE' => 'mail', ...]`
- Password flow: `mail_get_password($id, $custid)` → fallback `generate_password('lud')`
- After activate/reactivate: call `mail_welcome_email($serviceClass->getId())`
- `getActivate` always sets `$event['success'] = true` and stops propagation at end
- Test stubs live in `tests/bootstrap.php` — defines `ZONEMTA_*` constants, `get_module_db`, `myadmin_log`, etc.

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
