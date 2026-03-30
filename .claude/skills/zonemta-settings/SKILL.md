---
name: zonemta-settings
description: Registers plugin configuration settings in getSettings() in src/Plugin.php using add_text_setting, add_password_setting, and add_dropdown_setting. Use when user says 'add setting', 'register config key', 'new zonemta option', or modifies getSettings. Covers $settings->setTarget('global'), get_setting(), and the ZoneMTA/ClickHouse/MySQL/rSPAMd/MXToolBox setting groups. Do NOT use for lifecycle handlers (getActivate, getDeactivate, etc.) or hook registration.
---
# ZoneMTA Settings Registration

## Critical

- `getSettings()` is the ONLY place settings are registered — never add config constants elsewhere.
- Always call `$settings->setTarget('global')` as the FIRST line inside `getSettings()` before any `add_*_setting` calls.
- Password fields MUST use `add_password_setting`, not `add_text_setting`.
- The `get_setting()` argument MUST be the UPPERCASE version of the snake_case key (e.g., key `zonemta_host` → `get_setting('ZONEMTA_HOST')`).
- The MXToolBox token uses a bare constant (`MXTOOLBOX_AUTH_TOKEN`) as its value, not `get_setting()`.
- Wrap all human-readable strings in `_()` for gettext i18n.

## Instructions

1. **Open `src/Plugin.php`** and locate `getSettings(GenericEvent $event)`. Verify `$settings->setTarget('global')` is already present; if not, add it as the first statement after `$settings = $event->getSubject();`.

2. **Choose the correct method** for the new setting:
   - Plain text / host / port / username → `add_text_setting`
   - Secret / password / token (when stored via `get_setting`) → `add_password_setting`
   - Boolean toggle (enabled/disabled) → `add_dropdown_setting`

3. **Add a text or password setting** using this exact signature:
   ```php
   $settings->add_text_setting(
       self::$module,          // module: 'mail'
       _('ZoneMTA'),           // group label (translated)
       'zonemta_key_name',     // snake_case setting key
       _('ZoneMTA Key Label'), // human label (translated)
       _('ZoneMTA Key Label'), // description (translated; often same as label)
       $settings->get_setting('ZONEMTA_KEY_NAME')  // UPPERCASE of key
   );
   ```
   For passwords, replace `add_text_setting` with `add_password_setting` — signature is identical.

4. **Add a dropdown setting** (e.g., out-of-stock flag) using:
   ```php
   $settings->add_dropdown_setting(
       self::$module,
       _('Out of Stock'),
       'outofstock_mail_zonemta',
       _('Out Of Stock ZoneMTA Mail'),
       _('Enable/Disable Sales Of This Type'),
       $settings->get_setting('OUTOFSTOCK_MAIL_ZONEMTA'),
       ['0', '1'],   // option values
       ['No', 'Yes'] // option labels
   );
   ```

5. **Order settings** to match their logical group:
   - Dropdown flags (Out of Stock) first
   - ZoneMTA core: `zonemta_clickhouse_host`, `zonemta_clickhouse_port`, `zonemta_host`, `zonemta_host2`, `zonemta_username`, `zonemta_password`
   - ZoneMTA MySQL: host, port, db, username, password
   - ZoneMTA rSPAMd MySQL: host, port, db, username, password
   - External tokens (MXToolBox) last

6. **Use the bare constant** for settings that are defined globally rather than via `get_setting()`:
   ```php
   $settings->add_password_setting(self::$module, _('MXToolBox'), 'mxtoolbox_auth_token', _('MXToolBox API Auth Key'), '', MXTOOLBOX_AUTH_TOKEN);
   ```

7. **Verify** by running `vendor/bin/phpunit` — no new test failures should appear.

## Examples

**User says:** "Add a ZoneMTA API port setting"

**Actions taken:**
1. Open `src/Plugin.php:263`.
2. Inside `getSettings()`, after the `zonemta_host2` line, add:
   ```php
   $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_api_port', _('ZoneMTA API Port'), _('ZoneMTA API Port'), $settings->get_setting('ZONEMTA_API_PORT'));
   ```
3. Run `vendor/bin/phpunit` — all tests pass.

**Result:** Setting `ZONEMTA_API_PORT` is now configurable from the admin UI under the ZoneMTA group.

## Common Issues

- **Setting shows as empty even though the constant is defined:** You passed the constant name as a string literal (`'ZONEMTA_HOST'`) instead of calling `$settings->get_setting('ZONEMTA_HOST')`. Fix: use `$settings->get_setting('ZONEMTA_HOST')`.
- **Password stored in plaintext / visible in UI:** Used `add_text_setting` for a credential. Fix: change to `add_password_setting` — signature is identical, just the method name differs.
- **`Call to undefined method` on `$settings`:** `$settings = $event->getSubject()` was not called, or the event subject is not a `\MyAdmin\Settings` instance. Verify the hook `mail.settings` is wired to `getSettings` in `getHooks()`.
- **gettext warning / missing translation:** Forgot to wrap a string in `_()`. Every human-readable label and description argument must be wrapped: `_('My Label')`.
- **`setTarget` not called first:** Settings may be registered under the wrong scope. `$settings->setTarget('global')` must appear before any `add_*_setting` call.