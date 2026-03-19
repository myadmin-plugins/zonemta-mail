<?php
/**
 * Unit tests for the ZoneMTA Mail Plugin class.
 *
 * Tests class structure, static properties, hook registration,
 * event handler signatures, and static analysis of database/MongoDB code paths.
 * No external services (MongoDB, MySQL) are required.
 *
 * @package Detain\MyAdminZoneMTAMail\Tests
 */

namespace Detain\MyAdminZoneMTAMail\Tests;

use PHPUnit\Framework\TestCase;
use Detain\MyAdminZoneMTAMail\Plugin;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\GenericEvent;

class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass
     */
    private ReflectionClass $reflection;

    /**
     * Set up reflection instance for the Plugin class.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    // -------------------------------------------------------------------------
    // Class existence and structure
    // -------------------------------------------------------------------------

    /**
     * Test that the Plugin class exists and is loadable.
     *
     * @return void
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Test that the Plugin class belongs to the correct namespace.
     *
     * @return void
     */
    public function testNamespace(): void
    {
        $this->assertSame('Detain\\MyAdminZoneMTAMail', $this->reflection->getNamespaceName());
    }

    /**
     * Test that the Plugin class has no parent class.
     *
     * @return void
     */
    public function testHasNoParentClass(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Test that the Plugin class does not implement any interfaces.
     *
     * @return void
     */
    public function testImplementsNoInterfaces(): void
    {
        $this->assertEmpty($this->reflection->getInterfaceNames());
    }

    /**
     * Test that the Plugin class is not abstract.
     *
     * @return void
     */
    public function testIsNotAbstract(): void
    {
        $this->assertFalse($this->reflection->isAbstract());
    }

    /**
     * Test that the Plugin class is not final.
     *
     * @return void
     */
    public function testIsNotFinal(): void
    {
        $this->assertFalse($this->reflection->isFinal());
    }

    /**
     * Test that the Plugin class is instantiable.
     *
     * @return void
     */
    public function testIsInstantiable(): void
    {
        $this->assertTrue($this->reflection->isInstantiable());
    }

    /**
     * Test that the constructor can be called without arguments.
     *
     * @return void
     */
    public function testConstructorRequiresNoArguments(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertSame(0, $constructor->getNumberOfRequiredParameters());
    }

    /**
     * Test that the Plugin class can be instantiated.
     *
     * @return void
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    // -------------------------------------------------------------------------
    // Static properties
    // -------------------------------------------------------------------------

    /**
     * Test that all expected static properties exist.
     *
     * @return void
     */
    public function testStaticPropertiesExist(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "Plugin should have static property \${$prop}"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isStatic(),
                "\${$prop} should be static"
            );
        }
    }

    /**
     * Test that all static properties are public.
     *
     * @return void
     */
    public function testStaticPropertiesArePublic(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $prop) {
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isPublic(),
                "\${$prop} should be public"
            );
        }
    }

    /**
     * Test that $name has the correct default value.
     *
     * @return void
     */
    public function testNameDefaultValue(): void
    {
        $this->assertSame('ZoneMTA Mail', Plugin::$name);
    }

    /**
     * Test that $description has the correct default value.
     *
     * @return void
     */
    public function testDescriptionDefaultValue(): void
    {
        $this->assertSame('Mail Services', Plugin::$description);
    }

    /**
     * Test that $help is an empty string by default.
     *
     * @return void
     */
    public function testHelpDefaultValue(): void
    {
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Test that $module is set to 'mail'.
     *
     * @return void
     */
    public function testModuleDefaultValue(): void
    {
        $this->assertSame('mail', Plugin::$module);
    }

    /**
     * Test that $type is set to 'service'.
     *
     * @return void
     */
    public function testTypeDefaultValue(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    // -------------------------------------------------------------------------
    // Method existence and signatures
    // -------------------------------------------------------------------------

    /**
     * Test that all expected public methods exist.
     *
     * @return void
     */
    public function testExpectedMethodsExist(): void
    {
        $expected = [
            '__construct',
            'getHooks',
            'apiRegister',
            'getActivate',
            'getReactivate',
            'getDeactivate',
            'getTerminate',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];
        foreach ($expected as $method) {
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Plugin should have method {$method}()"
            );
        }
    }

    /**
     * Test that all methods except the constructor are static.
     *
     * @return void
     */
    public function testAllMethodsExceptConstructorAreStatic(): void
    {
        $methods = $this->reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->getName() === '__construct') {
                $this->assertFalse($method->isStatic(), '__construct should not be static');
                continue;
            }
            if ($method->getDeclaringClass()->getName() === Plugin::class) {
                $this->assertTrue(
                    $method->isStatic(),
                    "{$method->getName()}() should be static"
                );
            }
        }
    }

    /**
     * Test that getHooks requires no parameters.
     *
     * @return void
     */
    public function testGetHooksRequiresNoParameters(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertSame(0, $method->getNumberOfRequiredParameters());
    }

    /**
     * Test that event handler methods accept exactly one GenericEvent parameter.
     *
     * @dataProvider eventHandlerMethodProvider
     * @param string $methodName The method name to check.
     * @return void
     */
    public function testEventHandlerAcceptsGenericEvent(string $methodName): void
    {
        $method = $this->reflection->getMethod($methodName);
        $params = $method->getParameters();
        $this->assertCount(1, $params, "{$methodName}() should accept exactly one parameter");
        $this->assertSame('event', $params[0]->getName(), "Parameter should be named \$event");

        $type = $params[0]->getType();
        $this->assertNotNull($type, "{$methodName}() parameter should be type-hinted");
        $this->assertSame(
            GenericEvent::class,
            $type->getName(),
            "{$methodName}() parameter should be typed as GenericEvent"
        );
    }

    /**
     * Data provider for event handler method names.
     *
     * @return array<string, array{string}>
     */
    public function eventHandlerMethodProvider(): array
    {
        return [
            'apiRegister' => ['apiRegister'],
            'getActivate' => ['getActivate'],
            'getReactivate' => ['getReactivate'],
            'getDeactivate' => ['getDeactivate'],
            'getTerminate' => ['getTerminate'],
            'getChangeIp' => ['getChangeIp'],
            'getMenu' => ['getMenu'],
            'getRequirements' => ['getRequirements'],
            'getSettings' => ['getSettings'],
        ];
    }

    // -------------------------------------------------------------------------
    // getHooks() return value
    // -------------------------------------------------------------------------

    /**
     * Test that getHooks returns an array.
     *
     * @return void
     */
    public function testGetHooksReturnsArray(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertIsArray($hooks);
    }

    /**
     * Test that getHooks returns the expected hook keys.
     *
     * @return void
     */
    public function testGetHooksContainsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();
        $expectedKeys = [
            'mail.settings',
            'mail.activate',
            'mail.reactivate',
            'mail.deactivate',
            'mail.terminate',
            'api.register',
            'function.requirements',
            'ui.menu',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $hooks, "Hooks should contain key '{$key}'");
        }
    }

    /**
     * Test that getHooks does not contain unexpected keys.
     *
     * @return void
     */
    public function testGetHooksHasExactlyExpectedCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(8, $hooks, 'getHooks() should return exactly 8 hooks');
    }

    /**
     * Test that all hook values are valid callable arrays.
     *
     * @return void
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();
        foreach ($hooks as $eventName => $handler) {
            $this->assertIsArray($handler, "Handler for '{$eventName}' should be an array");
            $this->assertCount(2, $handler, "Handler for '{$eventName}' should have 2 elements");
            $this->assertSame(
                Plugin::class,
                $handler[0],
                "Handler class for '{$eventName}' should be Plugin"
            );
            $this->assertTrue(
                $this->reflection->hasMethod($handler[1]),
                "Handler method '{$handler[1]}' for '{$eventName}' should exist on Plugin"
            );
        }
    }

    /**
     * Test that hook keys use the module prefix correctly.
     *
     * @return void
     */
    public function testGetHooksUsesModulePrefix(): void
    {
        $hooks = Plugin::getHooks();
        $moduleHooks = ['settings', 'activate', 'reactivate', 'deactivate', 'terminate'];
        foreach ($moduleHooks as $hook) {
            $key = Plugin::$module . '.' . $hook;
            $this->assertArrayHasKey($key, $hooks, "Hooks should contain '{$key}'");
        }
    }

    /**
     * Test that specific hooks map to the correct handler methods.
     *
     * @return void
     */
    public function testGetHooksMapping(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame('getSettings', $hooks['mail.settings'][1]);
        $this->assertSame('getActivate', $hooks['mail.activate'][1]);
        $this->assertSame('getReactivate', $hooks['mail.reactivate'][1]);
        $this->assertSame('getDeactivate', $hooks['mail.deactivate'][1]);
        $this->assertSame('getTerminate', $hooks['mail.terminate'][1]);
        $this->assertSame('apiRegister', $hooks['api.register'][1]);
        $this->assertSame('getRequirements', $hooks['function.requirements'][1]);
        $this->assertSame('getMenu', $hooks['ui.menu'][1]);
    }

    // -------------------------------------------------------------------------
    // apiRegister — pure function call test
    // -------------------------------------------------------------------------

    /**
     * Test that apiRegister calls api_register without errors.
     *
     * @return void
     */
    public function testApiRegisterExecutesWithoutError(): void
    {
        $event = new GenericEvent(null);
        // Should not throw; calls the stubbed api_register()
        Plugin::apiRegister($event);
        $this->assertTrue(true, 'apiRegister executed without error');
    }

    // -------------------------------------------------------------------------
    // getMenu — lightweight handler test
    // -------------------------------------------------------------------------

    /**
     * Test that getMenu executes without error.
     *
     * @return void
     */
    public function testGetMenuExecutesWithoutError(): void
    {
        $menu = new \stdClass();
        $event = new GenericEvent($menu);
        Plugin::getMenu($event);
        $this->assertTrue(true, 'getMenu executed without error');
    }

    // -------------------------------------------------------------------------
    // getRequirements — lightweight handler test
    // -------------------------------------------------------------------------

    /**
     * Test that getRequirements executes without error.
     *
     * @return void
     */
    public function testGetRequirementsExecutesWithoutError(): void
    {
        $loader = new \stdClass();
        $event = new GenericEvent($loader);
        Plugin::getRequirements($event);
        $this->assertTrue(true, 'getRequirements executed without error');
    }

    // -------------------------------------------------------------------------
    // getSettings — verifies settings handler calls
    // -------------------------------------------------------------------------

    /**
     * Test that getSettings calls expected settings methods on the subject.
     *
     * @return void
     */
    public function testGetSettingsCallsSetTarget(): void
    {
        $settings = new class {
            /** @var string */
            public string $target = '';
            /** @var array<int, array<string, mixed>> */
            public array $dropdowns = [];
            /** @var array<int, array<string, mixed>> */
            public array $texts = [];
            /** @var array<int, array<string, mixed>> */
            public array $passwords = [];

            public function setTarget(string $target): void
            {
                $this->target = $target;
            }

            public function add_dropdown_setting(string $module, string $label, string $key, string $name, string $desc, $value, array $options, array $labels): void
            {
                $this->dropdowns[] = ['key' => $key, 'module' => $module];
            }

            public function add_text_setting(string $module, string $label, string $key, string $name, string $desc, $value): void
            {
                $this->texts[] = ['key' => $key, 'module' => $module];
            }

            public function add_password_setting(string $module, string $label, string $key, string $name, string $desc, $value): void
            {
                $this->passwords[] = ['key' => $key, 'module' => $module];
            }

            public function get_setting(string $name)
            {
                return '';
            }
        };

        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        $this->assertSame('global', $settings->target, 'setTarget should be called with "global"');
    }

    /**
     * Test that getSettings registers the expected number of dropdown settings.
     *
     * @return void
     */
    public function testGetSettingsRegistersDropdownSettings(): void
    {
        $settings = $this->createSettingsStub();
        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        $this->assertCount(1, $settings->dropdowns, 'Should register exactly 1 dropdown setting');
        $this->assertSame('outofstock_mail_zonemta', $settings->dropdowns[0]['key']);
    }

    /**
     * Test that getSettings registers text settings for ZoneMTA configuration.
     *
     * @return void
     */
    public function testGetSettingsRegistersTextSettings(): void
    {
        $settings = $this->createSettingsStub();
        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        $textKeys = array_column($settings->texts, 'key');
        $expectedKeys = [
            'zonemta_clickhouse_host',
            'zonemta_clickhouse_port',
            'zonemta_host',
            'zonemta_host2',
            'zonemta_username',
            'zonemta_mysql_host',
            'zonemta_mysql_port',
            'zonemta_mysql_db',
            'zonemta_mysql_username',
            'zonemta_rspamd_mysql_host',
            'zonemta_rspamd_mysql_port',
            'zonemta_rspamd_mysql_db',
            'zonemta_rspamd_mysql_username',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertContains($key, $textKeys, "Text settings should include '{$key}'");
        }
    }

    /**
     * Test that getSettings registers password settings.
     *
     * @return void
     */
    public function testGetSettingsRegistersPasswordSettings(): void
    {
        $settings = $this->createSettingsStub();
        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        $passwordKeys = array_column($settings->passwords, 'key');
        $expectedKeys = [
            'zonemta_password',
            'zonemta_mysql_password',
            'zonemta_rspamd_mysql_password',
            'mxtoolbox_auth_token',
        ];
        foreach ($expectedKeys as $key) {
            $this->assertContains($key, $passwordKeys, "Password settings should include '{$key}'");
        }
    }

    /**
     * Test that getSettings registers settings under the mail module.
     *
     * @return void
     */
    public function testGetSettingsUsesMailModule(): void
    {
        $settings = $this->createSettingsStub();
        $event = new GenericEvent($settings);
        Plugin::getSettings($event);

        $allSettings = array_merge($settings->dropdowns, $settings->texts, $settings->passwords);
        foreach ($allSettings as $setting) {
            $this->assertSame('mail', $setting['module'], "All settings should use module 'mail'");
        }
    }

    // -------------------------------------------------------------------------
    // Event handler type-guard — non-matching types
    // -------------------------------------------------------------------------

    /**
     * Test that getActivate sets success and stops propagation for non-matching type.
     *
     * @return void
     */
    public function testGetActivateWithNonMatchingTypeStillSetSuccess(): void
    {
        $event = new GenericEvent(null, ['type' => 999]);
        Plugin::getActivate($event);
        // The method sets success=true and stops propagation at the end regardless
        $this->assertTrue($event['success']);
        $this->assertTrue($event->isPropagationStopped());
    }

    /**
     * Test that getReactivate does not stop propagation for non-matching type.
     *
     * @return void
     */
    public function testGetReactivateWithNonMatchingTypeDoesNotStopPropagation(): void
    {
        $event = new GenericEvent(null, ['type' => 999]);
        Plugin::getReactivate($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    /**
     * Test that getDeactivate does not stop propagation for non-matching type.
     *
     * @return void
     */
    public function testGetDeactivateWithNonMatchingTypeDoesNotStopPropagation(): void
    {
        $event = new GenericEvent(null, ['type' => 999]);
        Plugin::getDeactivate($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    /**
     * Test that getTerminate does not stop propagation for non-matching type.
     *
     * @return void
     */
    public function testGetTerminateWithNonMatchingTypeDoesNotStopPropagation(): void
    {
        $event = new GenericEvent(null, ['type' => 999]);
        $result = Plugin::getTerminate($event);
        $this->assertFalse($event->isPropagationStopped());
        $this->assertNull($result);
    }

    /**
     * Test that getChangeIp does not stop propagation for non-matching type.
     *
     * @return void
     */
    public function testGetChangeIpWithNonMatchingTypeDoesNotStopPropagation(): void
    {
        $event = new GenericEvent(null, ['type' => 999]);
        Plugin::getChangeIp($event);
        $this->assertFalse($event->isPropagationStopped());
    }

    // -------------------------------------------------------------------------
    // Static analysis: source code inspection for DB/MongoDB patterns
    // -------------------------------------------------------------------------

    /**
     * Test that getActivate source references MongoDB\Client.
     *
     * @return void
     */
    public function testGetActivateSourceReferencesMongoDBClient(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringContainsString('MongoDB\Client', $source, 'getActivate should use MongoDB\Client');
        $this->assertStringContainsString('zone-mta', $source, 'getActivate should reference zone-mta database');
        $this->assertStringContainsString('users', $source, 'getActivate should reference users collection');
    }

    /**
     * Test that getActivate source uses findOne and insertOne.
     *
     * @return void
     */
    public function testGetActivateSourceUsesMongoOperations(): void
    {
        $source = $this->getMethodSource('getActivate');

        $this->assertStringContainsString('findOne', $source, 'getActivate should use findOne');
        $this->assertStringContainsString('insertOne', $source, 'getActivate should use insertOne');
        $this->assertStringContainsString('updateOne', $source, 'getActivate should use updateOne');
    }

    /**
     * Test that getActivate source runs a SQL UPDATE query.
     *
     * @return void
     */
    public function testGetActivateSourceRunsSqlUpdate(): void
    {
        $source = $this->getMethodSource('getActivate');

        $this->assertStringContainsString('$db->query', $source, 'getActivate should call $db->query()');
        $this->assertStringContainsString('update', $source, 'getActivate should perform SQL UPDATE');
        $this->assertStringContainsString('real_escape', $source, 'getActivate should escape SQL values');
    }

    /**
     * Test that getDeactivate source uses MongoDB deleteOne.
     *
     * @return void
     */
    public function testGetDeactivateSourceUsesDeleteOne(): void
    {
        $source = $this->getMethodSource('getDeactivate');

        $this->assertStringContainsString('MongoDB\Client', $source);
        $this->assertStringContainsString('deleteOne', $source);
    }

    /**
     * Test that getTerminate source uses MongoDB deleteOne.
     *
     * @return void
     */
    public function testGetTerminateSourceUsesDeleteOne(): void
    {
        $source = $this->getMethodSource('getTerminate');

        $this->assertStringContainsString('MongoDB\Client', $source);
        $this->assertStringContainsString('deleteOne', $source);
    }

    /**
     * Test that getReactivate source has same MongoDB pattern as getActivate.
     *
     * @return void
     */
    public function testGetReactivateSourceUsesMongoOperations(): void
    {
        $source = $this->getMethodSource('getReactivate');

        $this->assertStringContainsString('findOne', $source);
        $this->assertStringContainsString('insertOne', $source);
        $this->assertStringContainsString('updateOne', $source);
        $this->assertStringContainsString('$db->query', $source);
    }

    /**
     * Test that getActivate source constructs username with 'mb' prefix.
     *
     * @return void
     */
    public function testGetActivateSourceConstructsUsernameWithMbPrefix(): void
    {
        $source = $this->getMethodSource('getActivate');
        $this->assertStringContainsString("'mb'", $source, 'getActivate should construct username with mb prefix');
    }

    /**
     * Test that getActivate source calls mail_welcome_email.
     *
     * @return void
     */
    public function testGetActivateSourceCallsMailWelcomeEmail(): void
    {
        $source = $this->getMethodSource('getActivate');
        $this->assertStringContainsString('mail_welcome_email', $source);
    }

    /**
     * Test that getReactivate source calls mail_welcome_email.
     *
     * @return void
     */
    public function testGetReactivateSourceCallsMailWelcomeEmail(): void
    {
        $source = $this->getMethodSource('getReactivate');
        $this->assertStringContainsString('mail_welcome_email', $source);
    }

    /**
     * Test that getActivate source handles password generation fallback.
     *
     * @return void
     */
    public function testGetActivateSourceHandlesPasswordFallback(): void
    {
        $source = $this->getMethodSource('getActivate');
        $this->assertStringContainsString('mail_get_password', $source);
        $this->assertStringContainsString('generate_password', $source);
    }

    /**
     * Test that getChangeIp source references history add and set_ip.
     *
     * @return void
     */
    public function testGetChangeIpSourceReferencesHistoryAndSetIp(): void
    {
        $source = $this->getMethodSource('getChangeIp');
        $this->assertStringContainsString('history->add', $source);
        $this->assertStringContainsString('set_ip', $source);
    }

    /**
     * Test that getChangeIp source sets event status on success.
     *
     * @return void
     */
    public function testGetChangeIpSourceSetsStatusOkOnSuccess(): void
    {
        $source = $this->getMethodSource('getChangeIp');
        $this->assertStringContainsString("'ok'", $source);
        $this->assertStringContainsString('status_text', $source);
    }

    /**
     * Test that getChangeIp source sets event error status on fault.
     *
     * @return void
     */
    public function testGetChangeIpSourceSetsStatusErrorOnFault(): void
    {
        $source = $this->getMethodSource('getChangeIp');
        $this->assertStringContainsString("'error'", $source);
        $this->assertStringContainsString('faultcode', $source);
    }

    // -------------------------------------------------------------------------
    // Static property count
    // -------------------------------------------------------------------------

    /**
     * Test that the Plugin class has exactly 5 static properties.
     *
     * @return void
     */
    public function testStaticPropertyCount(): void
    {
        $staticProps = array_filter(
            $this->reflection->getProperties(),
            fn ($p) => $p->isStatic() && $p->getDeclaringClass()->getName() === Plugin::class
        );
        $this->assertCount(5, $staticProps);
    }

    /**
     * Test that the Plugin class has the expected total method count.
     *
     * @return void
     */
    public function testMethodCount(): void
    {
        $ownMethods = array_filter(
            $this->reflection->getMethods(),
            fn ($m) => $m->getDeclaringClass()->getName() === Plugin::class
        );
        $this->assertCount(11, $ownMethods, 'Plugin should have exactly 11 own methods');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Get the source code of a Plugin method as a string.
     *
     * @param string $methodName The method name.
     * @return string
     */
    private function getMethodSource(string $methodName): string
    {
        $method = $this->reflection->getMethod($methodName);
        $filename = $method->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        return implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));
    }

    /**
     * Create an anonymous-class stub mimicking the settings object.
     *
     * @return object
     */
    private function createSettingsStub(): object
    {
        return new class {
            /** @var string */
            public string $target = '';
            /** @var array<int, array<string, mixed>> */
            public array $dropdowns = [];
            /** @var array<int, array<string, mixed>> */
            public array $texts = [];
            /** @var array<int, array<string, mixed>> */
            public array $passwords = [];

            public function setTarget(string $target): void
            {
                $this->target = $target;
            }

            public function add_dropdown_setting(string $module, string $label, string $key, string $name, string $desc, $value, array $options, array $labels): void
            {
                $this->dropdowns[] = ['key' => $key, 'module' => $module];
            }

            public function add_text_setting(string $module, string $label, string $key, string $name, string $desc, $value): void
            {
                $this->texts[] = ['key' => $key, 'module' => $module];
            }

            public function add_password_setting(string $module, string $label, string $key, string $name, string $desc, $value): void
            {
                $this->passwords[] = ['key' => $key, 'module' => $module];
            }

            public function get_setting(string $name)
            {
                return '';
            }
        };
    }
}
