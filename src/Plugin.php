<?php

namespace Detain\MyAdminZoneMTAMail;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminZoneMTAWeb
 */
class Plugin
{
    public static $name = 'ZoneMTA Mail';
    public static $description = 'Mail Services';
    public static $help = '';
    public static $module = 'mail';
    public static $type = 'service';

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getHooks()
    {
        return [
            self::$module.'.settings' => [__CLASS__, 'getSettings'],
            self::$module.'.activate' => [__CLASS__, 'getActivate'],
            self::$module.'.reactivate' => [__CLASS__, 'getReactivate'],
            self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
            self::$module.'.terminate' => [__CLASS__, 'getTerminate'],
            'api.register' => [__CLASS__, 'apiRegister'],
            'function.requirements' => [__CLASS__, 'getRequirements'],
            'ui.menu' => [__CLASS__, 'getMenu']
        ];
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function apiRegister(GenericEvent $event)
    {
        /**
         * @var \ServiceHandler $subject
         */
        //$subject = $event->getSubject();
        api_register('api_auto_zonemta_login', ['id' => 'int'], ['return' => 'result_status'], 'Logs into ZoneMTA for the given mail id and returns a unique logged-in url.  The status will be "ok" if successful, or "error" if there was any problems status_text will contain a description of the problem if any.');
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Exception
     */
    public static function getActivate(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('MAIL_ZONEMTA')])) {
            $serviceClass = $event->getSubject();
            myadmin_log('myadmin', 'info', 'ZoneMTA Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $serviceTypes = run_event('get_service_types', false, self::$module);
            $settings = get_module_settings(self::$module);
            $extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
            //$serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $password = mail_get_password($serviceClass->getId(), $serviceClass->getCustid());
            $username = 'mb'.$serviceClass->getId();
            $client = new \MongoDB\Client('mongodb://'.ZONEMTA_USERNAME.':'.rawurlencode(ZONEMTA_PASSWORD).'@'.ZONEMTA_HOST.':27017/');
            $users = $client->selectDatabase('zone-mta')->selectCollection('users');
            $data = [
                'username' => $username,
                'password' => $password,
            ];
            $result = $users->findOne(['username' => $username]);
            if (is_null($result)) {
                $result = $users->insertOne($data);
                request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'zonemta', 'insert', $data, $result, $serviceClass->getId());
                myadmin_log('myadmin', 'info', 'ZoneMTA insert '.json_encode($data).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                if ($result->getInsertedCount() == 0) {
                    $event['success'] = false;
                    myadmin_log('zonemta', 'error', 'Error Creating User '.$username.' Site '.$hostname.' Text:'.$result['text'].' Details:'.$result['details'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $event->stopPropagation();
                    return;
                }
            } else {
                myadmin_log('myadmin', 'info', 'ZoneMTA found existing entry for '.json_encode($data).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                if ($result['password'] != $password) {
                    myadmin_log('myadmin', 'info', 'ZoneMTA updating user '.$username.' password to '.$password, __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $updateResult = $users->updateOne(
                        ['username' => $username],
                        ['$set' => ['password' => $password]]
                    );
                }
            }
            /* if ($serviceTypes[$serviceClass->getType()]['services_field2'] != '') {
                $fields = explode(',', $serviceTypes[$serviceClass->getType()]['services_field2']);
                foreach ($fields as $field) {
                    list($key, $value) = explode('=', $field);
                    if ($key == 'script') {
                        $extra[$key] = $value;
                    } else {
                        $options[$key] = $value;
                    }
                }
            } */
            $db = get_module_db(self::$module);
            $username = $db->real_escape($username);
            $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_username='{$username}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
            mail_welcome_email($serviceClass->getId());
            $event['success'] = true;
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Exception
     */
    public static function getReactivate(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('MAIL_ZONEMTA')])) {
            $serviceClass = $event->getSubject();
            $settings = get_module_settings(self::$module);
            $username = $serviceClass->getUsername() == '' ? 'mb'.$serviceClass->getId() : $serviceClass->getUsername();
            $password = mail_get_password($serviceClass->getId(), $serviceClass->getCustid());
            $client = new \MongoDB\Client('mongodb://'.ZONEMTA_USERNAME.':'.rawurlencode(ZONEMTA_PASSWORD).'@'.ZONEMTA_HOST.':27017/');
            $users = $client->selectDatabase('zone-mta')->selectCollection('users');
            $data = [
                'username' => $username,
                'password' => $password,
            ];
            $result = $users->findOne(['username' => $username]);
            if (is_null($result)) {
                $result = $users->insertOne($data);
                request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'zonemta', 'insert', $data, $result, $serviceClass->getId());
                myadmin_log('myadmin', 'info', 'ZoneMTA insert '.json_encode($data).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                if ($result->getInsertedCount() == 0) {
                    $event['success'] = false;
                    myadmin_log('zonemta', 'error', 'Error Creating User '.$username.' Site '.$hostname.' Text:'.$result['text'].' Details:'.$result['details'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $event->stopPropagation();
                    return;
                }
            } else {
                myadmin_log('myadmin', 'info', 'ZoneMTA found existing entry for '.json_encode($data).' : '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                if ($result['password'] != $password) {
                    myadmin_log('myadmin', 'info', 'ZoneMTA updating user '.$username.' password to '.$password, __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $updateResult = $users->updateOne(
                        ['username' => $username],
                        ['$set' => ['password' => $password]]
                    );
                }
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Exception
     */
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

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @return boolean|null
     * @throws \Exception
     */
    public static function getTerminate(GenericEvent $event)
    {
        $serviceClass = $event->getSubject();
        if (in_array($event['type'], [get_service_define('MAIL_ZONEMTA')])) {
            myadmin_log('myadmin', 'info', 'ZoneMTA Termination', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $settings = get_module_settings(self::$module);
            $client = new \MongoDB\Client('mongodb://'.ZONEMTA_USERNAME.':'.rawurlencode(ZONEMTA_PASSWORD).'@'.ZONEMTA_HOST.':27017/');
            $users = $client->selectDatabase('zone-mta')->selectCollection('users');
            $data = ['username' => $serviceClass->getUsername()];
            $result = $users->deleteOne($data);
            request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'zonemta', 'delete', $data, $result, $serviceClass->getId());
            myadmin_log('myadmin', 'info', 'ZoneMTA delete '.json_encode($data).' Response: '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $event->stopPropagation();
            return true;
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getChangeIp(GenericEvent $event)
    {
        if (in_array($event['type'], [get_service_define('MAIL_ZONEMTA')])) {
            $serviceClass = $event->getSubject();
            $settings = get_module_settings(self::$module);
            $client = new \MongoDB\Client('mongodb://'.ZONEMTA_USERNAME.':'.rawurlencode(ZONEMTA_PASSWORD).'@'.ZONEMTA_HOST.':27017/');
            $users = $client->selectDatabase('zone-mta')->selectCollection('users');
            myadmin_log('myadmin', 'info', 'IP Change - (OLD:'.$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            //$result = $zonemta->editIp($serviceClass->getIp(), $event['newip']);
            if (isset($result['faultcode'])) {
                myadmin_log('myadmin', 'error', 'ZoneMTA editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['status'] = 'error';
                $event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
            } else {
                $GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getId(), $serviceClass->getCustid());
                $serviceClass->set_ip($event['newip'])->save();
                $event['status'] = 'ok';
                $event['status_text'] = 'The IP Address has been changed.';
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getMenu(GenericEvent $event)
    {
        $menu = $event->getSubject();
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getRequirements(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Plugins\Loader $this->loader
         */
        $loader = $event->getSubject();
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
        $settings->setTarget('global');
        $settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_mail_zonemta', _('Out Of Stock ZoneMTA Mail'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_MAIL_ZONEMTA'), ['0', '1'], ['No', 'Yes']);
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_clickhouse_host', _('ZoneMTA ClickHouse Host'), _('ZoneMTA ClickHouse Host'), $settings->get_setting('ZONEMTA_CLICKHOUSE_HOST'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_clickhouse_port', _('ZoneMTA ClickHouse Port'), _('ZoneMTA ClickHouse Port'), $settings->get_setting('ZONEMTA_CLICKHOUSE_PORT'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_host', _('ZoneMTA Host'), _('ZoneMTA Host'), $settings->get_setting('ZONEMTA_HOST'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_host2', _('ZoneMTA Host2'), _('ZoneMTA Host2'), $settings->get_setting('ZONEMTA_HOST2'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_username', _('ZoneMTA Username'), _('ZoneMTA Username'), $settings->get_setting('ZONEMTA_USERNAME'));
        $settings->add_password_setting(self::$module, _('ZoneMTA'), 'zonemta_password', _('ZoneMTA Password'), _('ZoneMTA Password'), $settings->get_setting('ZONEMTA_PASSWORD'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_mysql_host', _('ZoneMTA MySQL Host'), _('ZoneMTA MySQL Host'), $settings->get_setting('ZONEMTA_MYSQL_HOST'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_mysql_port', _('ZoneMTA MySQL Port'), _('ZoneMTA MySQL Port'), $settings->get_setting('ZONEMTA_MYSQL_PORT'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_mysql_db', _('ZoneMTA MySQL DB'), _('ZoneMTA MySQL DB'), $settings->get_setting('ZONEMTA_MYSQL_DB'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_mysql_username', _('ZoneMTA MySQL Username'), _('ZoneMTA MySQL Username'), $settings->get_setting('ZONEMTA_MYSQL_USERNAME'));
        $settings->add_password_setting(self::$module, _('ZoneMTA'), 'zonemta_mysql_password', _('ZoneMTA MySQL Password'), _('ZoneMTA MySQL Password'), $settings->get_setting('ZONEMTA_MYSQL_PASSWORD'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_rspamd_mysql_host', _('ZoneMTA rSPAMd MySQLHost'), _('ZoneMTA rSPAMd MySQLHost'), $settings->get_setting('ZONEMTA_RSPAMD_MYSQL_HOST'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_rspamd_mysql_port', _('ZoneMTA rSPAMd MySQLPort'), _('ZoneMTA rSPAMd MySQLPort'), $settings->get_setting('ZONEMTA_RSPAMD_MYSQL_PORT'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_rspamd_mysql_db', _('ZoneMTA rSPAMd MySQLDB'), _('ZoneMTA rSPAMd MySQLDB'), $settings->get_setting('ZONEMTA_RSPAMD_MYSQL_DB'));
        $settings->add_text_setting(self::$module, _('ZoneMTA'), 'zonemta_rspamd_mysql_username', _('ZoneMTA rSPAMd MySQLUsername'), _('ZoneMTA rSPAMd MySQLUsername'), $settings->get_setting('ZONEMTA_RSPAMD_MYSQL_USERNAME'));
        $settings->add_password_setting(self::$module, _('ZoneMTA'), 'zonemta_rspamd_mysql_password', _('ZoneMTA rSPAMd MySQLPassword'), _('ZoneMTA rSPAMd MySQLPassword'), $settings->get_setting('ZONEMTA_RSPAMD_MYSQL_PASSWORD'));
    }
}
