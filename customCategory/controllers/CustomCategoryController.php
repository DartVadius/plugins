<?php

/**
 * Description of CustomCategoryController
 *
 * @author DartVadius
 */
class CustomCategory_CustomCategoryController extends Zend_Controller_Action {

    public function indexAction() {
        $plugin = new Application_Model_DbTable_Plugins();
        $pluginId = $plugin->getBySystemName('customCategory')['id'];
        $config = new Application_Model_DbTable_PluginsSettings();
        $urls = $config->getSettingsByPluginsID($pluginId);

        $res = CustomCategory_Model_CustomCategory::findCategoryByConfig($urls);
        $res = array_merge($urls, $res);
        $this->view->urls = $res;
    }

    public function viewAction() {
        $uri = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
        $uri = substr($uri, 1);
        $plugin = new Application_Model_DbTable_Plugins();
        $pluginId = $plugin->getBySystemName('customCategory')['id'];
        $configCat = CustomCategory_Model_CustomCategory::findConfigByUri($pluginId, $uri);
        $categories = CustomCategory_Model_CustomCategory::findCategoriesForView($configCat);
        $this->view->categories = $categories;
    }

    public function searchAction() {
        $post = $this->_request->getPost();

        $name = trim($post['search']);
        $checkbox = NULL;
        if (!empty($post['checkbox'])) {
            $checkbox = array_unique($post['checkbox']);
            $checkbox = implode(', ', $checkbox);
        }
        $res = CustomCategory_Model_CustomCategory::searchCategory($name, $checkbox);
        $arr = [
            'id' => $post['id'],
            'cat' => $res,
        ];
//        print_r($arr);
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/plugins/frontend/customCategory/views/scripts/custom-category');
        $html->assign('arr', $arr);

        $body_html = $html->render('search.phtml');
        $this->_helper->json([
            "html" => $body_html,
            'id' => $post['id'],
        ]);
    }

    public function saveAction() {
        $post = $this->_request->getPost();
        unset($post['search']);
        $config = new Application_Model_DbTable_PluginsSettings();
        $plugin = new Application_Model_DbTable_Plugins();
        $pluginId = $plugin->getBySystemName('customCategory')['id'];
        $urls = $config->getSettingsByPluginsID($pluginId);
        if (!empty($post)) {
            foreach ($post as $key => $value) {
                unset($urls[$key]);
                $val = implode(',', $value);
                $config->update(['params' => $val], "plugins_id='$pluginId' AND name='$key'");
            }
        }
        if (!empty($urls)) {
            foreach ($urls as $key => $url) {
                $config->update(['params' => ''], "plugins_id='$pluginId' AND name='$key'");
            }
        }

        $this->redirect('/customCategory/custom-category/index');
    }

}
