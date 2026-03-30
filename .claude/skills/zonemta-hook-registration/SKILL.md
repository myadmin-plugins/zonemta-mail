---
name: zonemta-hook-registration
description: Adds or modifies hook registrations in getHooks() in src/Plugin.php and adds the corresponding static method stub. Use when user says 'add hook', 'register event', 'new event handler', or adds entries to the getHooks() return array. Covers the module.event key format and [__CLASS__, 'methodName'] handler syntax. Do NOT use for implementing handler logic (MongoDB ops, MySQL updates, etc.).
---
# ZoneMTA Hook Registration

## Critical

- Every hook key uses `self::$module.'.'.$eventSuffix` for module-scoped events, or a bare `'namespace.event'` string for cross-module events (e.g. `'api.register'`, `'function.requirements'`, `'ui.menu'`).
- Every handler value must be `[__CLASS__, 'methodName']` — never a closure or a string.
- The corresponding method **must** exist on `Plugin` before tests will pass — the test suite asserts every handler method exists via reflection (`testGetHooksValuesAreCallableArrays`).
- All handler methods must be `public static` and accept exactly one `GenericEvent $event` parameter named `$event`.
- After adding a hook+method, the count assertions in `tests/PluginTest.php` will fail — update `testGetHooksHasExactlyExpectedCount` (the `assertCount` value) and `testMethodCount` (currently `assertCount(11, ...)`) to match the new totals.

## Instructions

1. **Add the hook entry to `getHooks()`** in `src/Plugin.php`.
   - Module-scoped event: `self::$module.'.eventSuffix' => [__CLASS__, 'getEventSuffix']`
   - Cross-module event: `'namespace.event' => [__CLASS__, 'handlerMethod']`
   - Insert the new line inside the existing `return [...]` array, maintaining trailing commas on all but the last entry.
   - Verify: the key does not already exist in the array.

2. **Add the static method stub** immediately after the last handler method in `src/Plugin.php` (before the closing `}` of the class).
   Use this exact signature shape:
   ```php
   /**
    * @param \Symfony\Component\EventDispatcher\GenericEvent $event
    */
   public static function getEventSuffix(GenericEvent $event)
   {
   }
   ```
   - Parameter must be typed `GenericEvent $event` (imported via `use Symfony\Component\EventDispatcher\GenericEvent;` — already present at `src/Plugin.php:5`).
   - Verify: the method does not already exist on the class.

3. **Update the test count assertions** in `tests/PluginTest.php`:
   - `testGetHooksHasExactlyExpectedCount`: increment the `assertCount(N, $hooks)` value by 1.
   - `testMethodCount`: increment the `assertCount(N, $ownMethods)` value by 1.
   - If the new hook is module-scoped, add its suffix to the `$moduleHooks` array in `testGetHooksUsesModulePrefix`.
   - Add the new event key to `$expectedKeys` in `testGetHooksContainsExpectedKeys`.
   - Add the new method name to the `$expected` array in `testExpectedMethodsExist`.
   - Add the new method name to `eventHandlerMethodProvider()` return array.
   - Add a mapping assertion to `testGetHooksMapping`.

4. **Run the test suite** to confirm all assertions pass:
   ```bash
   vendor/bin/phpunit
   ```

## Examples

**User says:** "Add a `mail.suspend` hook that calls `getSuspend`"

**Actions taken:**

`src/Plugin.php` — `getHooks()` after step 1:
```php
return [
    self::$module.'.settings'     => [__CLASS__, 'getSettings'],
    self::$module.'.activate'     => [__CLASS__, 'getActivate'],
    self::$module.'.reactivate'   => [__CLASS__, 'getReactivate'],
    self::$module.'.deactivate'   => [__CLASS__, 'getDeactivate'],
    self::$module.'.terminate'    => [__CLASS__, 'getTerminate'],
    self::$module.'.suspend'      => [__CLASS__, 'getSuspend'],   // added
    'api.register'                => [__CLASS__, 'apiRegister'],
    'function.requirements'       => [__CLASS__, 'getRequirements'],
    'ui.menu'                     => [__CLASS__, 'getMenu']
];
```

`src/Plugin.php` — new method stub added after step 2:
```php
/**
 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
 */
public static function getSuspend(GenericEvent $event)
{
}
```

`tests/PluginTest.php` — updated counts:
```php
$this->assertCount(9, $hooks);   // was 8
$this->assertCount(12, $ownMethods);  // was 11
```
Also added `'mail.suspend'` to `$expectedKeys`, `'suspend'` to `$moduleHooks`, `'getSuspend'` to `$expected` methods list and `eventHandlerMethodProvider`, and `$this->assertSame('getSuspend', $hooks['mail.suspend'][1])` to `testGetHooksMapping`.

**Result:** `vendor/bin/phpunit` passes with 0 failures.

## Common Issues

- **`testGetHooksHasExactlyExpectedCount` fails with "expected 8 but got 9"** — you added the hook to `getHooks()` but forgot to update the `assertCount` in the test. Increment the count by 1 in `tests/PluginTest.php`.
- **`testGetHooksValuesAreCallableArrays` fails with "Handler method 'getFoo' for 'mail.foo' should exist on Plugin"** — the method stub is missing. Add the `public static function getFoo(GenericEvent $event)` method to `src/Plugin.php`.
- **`testAllMethodsExceptConstructorAreStatic` fails** — the new method was declared without `static`. All handlers must be `public static`.
- **`testEventHandlerAcceptsGenericEvent` fails with "should be typed as GenericEvent"** — parameter is missing the `GenericEvent` type hint or was typed as a different class. Use `GenericEvent $event` exactly.
- **`testMethodCount` fails with "Plugin should have exactly 11 own methods"** — added a method but did not update the `assertCount` value in `testMethodCount`.
