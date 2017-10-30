<?php
/**
 * Created by PhpStorm.
 * User: vad
 * Date: 19.09.17
 * Time: 15:32
 */

require_once __DIR__ . ("/../lib/LiqPaySdk.php");

class Privatbank_Model_LiqPay {

    private $liq;
    private $description;
    private $private_key;

    public function __construct() {
        $config = $this->getConfig();
        $this->private_key = $config['private_key'];
        if (empty($config['public_key']) || empty($config['private_key']) || empty($config['description'])) {
            $this->liq = null;
            $this->description = null;
        } else {
            $this->liq = new LiqPaySdk($config['public_key'], $config['private_key']);
            $this->description = $config['description'];
        }
    }

    public function getLiq() {
        return $this->liq;
    }

    public function getConfig() {
        $plugin = new Application_Model_DbTable_Plugins();
        return $plugin->getBySettingsBySystemName('privatbank');
    }

    public function getDescription() {
        return $this->description;
    }

    public function saveConfig($post) {
        $plugin = new Application_Model_DbTable_Plugins();
        $plugConf = new Application_Model_DbTable_PluginsSettings();

        $pluginId = $plugin->getBySystemName('privatbank')['id'];

        if (isset($post['description'])) {
            $plugConf->saveSettings($pluginId, 'description', $post['description']);
        }
        if (isset($post['public_key'])) {
            $plugConf->saveSettings($pluginId, 'public_key', $post['public_key']);
        }
        if (isset($post['private_key'])) {
            $plugConf->saveSettings($pluginId, 'private_key', $post['private_key']);
        }
        return TRUE;
    }

    public function check($data, $signature) {
        $local_signature = base64_encode( sha1($this->private_key . $data . $this->private_key, 1 ));
        return $local_signature == $signature ? true : false;
    }

}