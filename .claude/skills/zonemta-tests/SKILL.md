---
name: zonemta-tests
description: Adds PHPUnit tests in `tests/PluginTest.php` following reflection-based and source-inspection patterns. Use when user says 'add test', 'write unit test', 'test this method', or adds coverage for `src/Plugin.php`. Covers ReflectionClass method inspection, GenericEvent stubs, anonymous-class settings stubs, and getMethodSource() assertions. Do NOT use for modifying `tests/bootstrap.php` stubs or adding new global function stubs.
---
# zonemta-tests

## Critical

- **Never modify `tests/bootstrap.php`** — all global function stubs (`myadmin_log`, `get_module_db`, `request_log`, etc.) and the `$GLOBALS['tf']` stub are already defined there.
- **No real MongoDB or MySQL** — tests must run without external services. Use source-inspection (`getMethodSource()`) to assert DB/MongoDB patterns instead of executing them.
- **All Plugin methods except `__construct` are static** — call them as `Plugin::methodName($event)`.
- Run the test suite from the repo root using:
  ```bash
  vendor/bin/phpunit
  ```
  Must pass before adding more tests.

## Instructions

1. **Place all tests in `tests/PluginTest.php`**, namespace `Detain\MyAdminZoneMTAMail\Tests`, class `PluginTest extends TestCase`.
   Required imports:
   ```php
   use PHPUnit\Framework\TestCase;
   use Detain\MyAdminZoneMTAMail\Plugin;
   use ReflectionClass;
   use ReflectionMethod;
   use Symfony\Component\EventDispatcher\GenericEvent;
   ```
   Verify `$this->reflection` is set in `setUp()`:
   ```php
   protected function setUp(): void {
       $this->reflection = new ReflectionClass(Plugin::class);
   }
   ```

2. **Structure test into sections** using comment banners:
   ```php
   // -------------------------------------------------------------------------
   // Section name
   // -------------------------------------------------------------------------
   ```
   Sections: Class existence and structure · Static properties · Method existence and signatures · getHooks() return value · Event handler tests · Static analysis: source code inspection

3. **For reflection/signature tests**, use `$this->reflection->getMethod($name)` and check `isStatic()`, `getNumberOfRequiredParameters()`, parameter type via `$params[0]->getType()->getName()`.
   ```php
   public function testEventHandlerAcceptsGenericEvent(string $methodName): void {
       $method = $this->reflection->getMethod($methodName);
       $params = $method->getParameters();
       $this->assertCount(1, $params);
       $this->assertSame('event', $params[0]->getName());
       $this->assertSame(GenericEvent::class, $params[0]->getType()->getName());
   }
   ```
   Use `@dataProvider eventHandlerMethodProvider` for multi-method coverage.

4. **For event handler execution tests** (lightweight handlers like `apiRegister`, `getMenu`, `getRequirements`), pass a `new GenericEvent(null)` or `new GenericEvent(new \stdClass())`:
   ```php
   $event = new GenericEvent(null);
   Plugin::apiRegister($event);
   $this->assertTrue(true, 'apiRegister executed without error');
   ```

5. **For settings handler tests**, use the `createSettingsStub()` private helper — an anonymous class with public arrays `$dropdowns`, `$texts`, `$passwords` and methods `setTarget()`, `add_dropdown_setting()`, `add_text_setting()`, `add_password_setting()`, `get_setting()`. Do NOT inline the stub — extract it to the helper.
   ```php
   $settings = $this->createSettingsStub();
   $event = new GenericEvent($settings);
   Plugin::getSettings($event);
   $this->assertSame('global', $settings->target);
   ```

6. **For type-guard / non-matching-type tests**, pass `['type' => 999]` as the second `GenericEvent` argument and assert `isPropagationStopped()` or event arguments like `$event['success']`:
   ```php
   $event = new GenericEvent(null, ['type' => 999]);
   Plugin::getDeactivate($event);
   $this->assertFalse($event->isPropagationStopped());
   ```

7. **For source-inspection tests** (MongoDB/MySQL patterns), use the private `getMethodSource()` helper:
   ```php
   private function getMethodSource(string $methodName): string {
       $method = $this->reflection->getMethod($methodName);
       $filename = $method->getFileName();
       $startLine = $method->getStartLine();
       $endLine = $method->getEndLine();
       return implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));
   }
   ```
   Then assert:
   ```php
   $source = $this->getMethodSource('getActivate');
   $this->assertStringContainsString('MongoDB\Client', $source);
   $this->assertStringContainsString('findOne', $source);
   $this->assertStringContainsString('insertOne', $source);
   $this->assertStringContainsString('$db->query', $source);
   $this->assertStringContainsString('real_escape', $source);
   ```

8. **Verify** by running the test suite — all tests must be green before committing:
   ```bash
   vendor/bin/phpunit
   ```

## Examples

**User says:** "Add a test that verifies getTerminate uses MongoDB deleteOne"

**Actions taken:**
1. Add source-inspection test under the static analysis section in `tests/PluginTest.php`:
```php
public function testGetTerminateSourceUsesDeleteOne(): void
{
    $source = $this->getMethodSource('getTerminate');
    $this->assertStringContainsString('MongoDB\Client', $source);
    $this->assertStringContainsString('deleteOne', $source);
}
```
2. Run the test suite and confirm green:
   ```bash
   vendor/bin/phpunit
   ```

**Result:** Test passes without requiring a live MongoDB connection.

## Common Issues

- **"Call to undefined function myadmin_log"**: `tests/bootstrap.php` is not being loaded. Check `phpunit.xml.dist` has `<bootstrap>tests/bootstrap.php</bootstrap>`.
- **"Cannot redeclare function get_module_db"**: A test or included file defines the stub twice. All stubs in `tests/bootstrap.php` are already guarded with `if (!function_exists(...))` — do not re-declare them in test files.
- **`$event->isPropagationStopped()` returns unexpected value**: The handler guards on `get_service_define('MAIL_ZONEMTA')` returning the matching type constant. The bootstrap stub returns `100` for `MAIL_ZONEMTA` — if `$event['type']` equals `100`, the guard passes and propagation stops. Pass `['type' => 999]` to test the non-matching path.
- **`assertCount` fails on method/property counts**: A new method or property was added to `src/Plugin.php`. Update `testMethodCount()` and `testStaticPropertyCount()` to match the new totals.
- **`getMethodSource()` returns empty string**: The method does not exist on `Plugin` or the file path from `getFileName()` is wrong. Verify `$this->reflection->hasMethod($methodName)` first.
