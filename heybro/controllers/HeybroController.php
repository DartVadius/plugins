<?php

/**
 * Description of HeybroController
 *
 * @author DartVadius
 */
class Heybro_HeybroController extends Zend_Controller_Action {

    public function init() {
        $pluginModel = new Application_Model_DbTable_Plugins();
        $configModel = new Application_Model_DbTable_PluginsSettings();
        $pluginId = $pluginModel->getBySystemName('heybro')['id'];
        $config = $configModel->getSettingsByPluginsID($pluginId);
        if (!isset($config['type'])) {
            $configModel->insert(['plugins_id' => $pluginId, 'name' => 'type', 'params' => '']);
        }
        if (!isset($config['character'])) {
            $configModel->insert(['plugins_id' => $pluginId, 'name' => 'character', 'params' => '']);
        }
        if (!is_dir('cards')) {
            mkdir('cards', 0775);
        }
    }

    public function indexAction() {
        $this->view->headScript()->appendFile('/js/dropzone.js');
        $typeModel = new Application_Model_DbTable_Type();
        $heybroModel = new Heybro_Model_HeybroProduct();
        $pluginModel = new Application_Model_DbTable_Plugins();
        $configModel = new Application_Model_DbTable_PluginsSettings();
        $heybro = new Heybro_Model_Heybro();
        $pluginId = $pluginModel->getBySystemName('heybro')['id'];
        $config = $configModel->getSettingsByPluginsID($pluginId);
        $config['character'] = json_decode($config['character'], TRUE);
        $this->view->animal = $heybroModel->findByType('animal');
        $this->view->topic = $heybroModel->findByType('topic');
        $this->view->pants = $heybroModel->findByType('pants');
        $this->view->types = $typeModel->getTypes();
        $this->view->config = $config;
        $this->view->data = $heybro->getAllFullInfo();
    }

    /**
     * upload files for carousel
     * 
     * @return boolean
     */
    public function uploadAction() {
        Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');
        $post = $this->getRequest()->getParams();
        $folder = 'cards/' . $post['toy'] . $post['topic'] . $post['pants'];
        print_r($_FILES);
        die;
        if (!is_dir($folder)) {
            mkdir($folder, 0777);
        }
        $file = $folder . '/' . basename($_FILES['file']['name']);
        if (is_file($file)) {
            unlink($file);
        }

        if (move_uploaded_file($_FILES['file']['tmp_name'], $file)) {
//            header('Content-Type: image/jpeg');
//            list($width, $height) = getimagesize($file);
//            $image_p = imagecreatetruecolor(300, 400);
//            $image = imagecreatefromjpeg($file);
//            imagecopyresampled($image_p, $image, 0, 0, 0, 0, 300, 400, $width, $height);
//            imagejpeg($image_p, $file);
//            imagedestroy($image_p);
            return TRUE;
        }
        return FALSE;
    }

    /**
     * index action for showcase
     */
    public function toyAction() {
        $this->view->headScript()->appendFile('js/spritespin.min.js');
        $this->view->headTitle('Heybro');
        $heybroModel = new Heybro_Model_HeybroProduct();
        $this->view->animal = $heybroModel->findByType('animal');
        $this->view->topic = $heybroModel->findByType('topic');
        $this->view->pants = $heybroModel->findByType('pants');
        $this->view->breadcrumbs = [
            ['name' => $this->view->translate('Heybro'), 'active' => true],
        ];
    }

    /**
     * save config
     */
    public function saveAction() {
        $pluginModel = new Application_Model_DbTable_Plugins();
        $pluginId = $pluginModel->getBySystemName('heybro')['id'];
        $configModel = new Application_Model_DbTable_PluginsSettings();
        $post = $this->getRequest()->getParams();
        $character = [];
        if (!empty($post['character'])) {
            foreach ($post['character'] as $value) {
                if (!empty(trim($value))) {
                    $character[] = trim($value);
                }
            }
        }
        $configModel->update(['params' => $post['type']], "plugins_id = '$pluginId' AND name = 'type'");
        $configModel->update(['params' => json_encode($character)], "plugins_id = '$pluginId' AND name = 'character'");
        $this->redirect('/heybro');
    }

    /**
     * save product data
     */
    public function saveProductAction() {
        $heybroProductModel = new Heybro_Model_HeybroProduct();
        $post = $this->getRequest()->getParams();

        unset($post['module']);
        unset($post['controller']);
        unset($post['action']);
        unset($post['search']);
        $heybroProductModel->save($post);
        $this->redirect('/heybro');
    }

    public function searchAction() {
        $post = $this->_request->getPost();
        $name = trim($post['search']);
        $checkbox = NULL;

        if (!empty($post['checkbox'])) {
            $checkbox = array_unique($post['checkbox']);
            $checkbox = implode(', ', $checkbox);
        }

        $res = Heybro_Model_HeybroProduct::searchProduct($name, $checkbox);
        $arr = [
            'id' => $post['id'],
            'animal' => $res,
        ];

        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/plugins/frontend/heybro/views/scripts/heybro');
        $html->assign('arr', $arr);

        $body_html = $html->render('search.phtml');
        $this->_helper->json([
            "html" => $body_html,
            'id' => $post['id'],
        ]);
    }

    /**
     * index action for cabinet
     */
    public function setImgAction() {
        $userId = Zend_Auth::getInstance()->getIdentity();
        if (empty($userId)) {
            $this->redirect('/');
        }
        $pluginModel = new Application_Model_DbTable_Plugins();
        $configModel = new Application_Model_DbTable_PluginsSettings();
        $heybroModel = new Heybro_Model_Heybro();
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/plugins/frontend/heybro/views/scripts/partials/');
        $html->assign('cardsReady', $heybroModel->getCardsByUserId($userId['id']));
        $html->assign('cardsEdit', $heybroModel->getCardsByUserId($userId['id'], 0));

        $config = $configModel->getSettingsByPluginsID($pluginModel->getBySystemName('heybro')['id']);

        $this->view->breadcrumbs = [
            ['name' => $this->view->translate('Cabinet'), 'active' => false, 'url' => '/cabinet'],
            ['name' => $this->view->translate('Heybro'), 'active' => true],
        ];
        $this->view->headTitle('Heybro');
        $this->view->headScript()->appendFile('/js/jquery-ui-1_11_4/jquery.ui.datepicker-ru.js');
        $this->view->card = $html->render('set-img-partial.phtml');
        $this->view->character = json_decode($config['character'], TRUE);
    }

}
