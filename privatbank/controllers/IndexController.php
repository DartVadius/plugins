<?php
/**
 * Created by PhpStorm.
 * User: vad
 * Date: 19.09.17
 * Time: 12:17
 */

class Privatbank_IndexController extends Zend_Controller_Action {
    public function indexAction() {
        $liq = new Privatbank_Model_LiqPay();
        $config = $liq->getConfig();
        $this->view->config = $config;
    }

    public function saveAction() {
        $liq = new Privatbank_Model_LiqPay();
        $route = new Zend_Controller_Action_Helper_Redirector;

        $post = $this->_request->getPost();

        $liq->saveConfig($post);
        $route->gotoUrl('/privatbank/index')->redirectAndExit();
    }

    public function statusAction() {
        $liqModel = new Privatbank_Model_LiqPay();
        $paymentModel = new Application_Model_DbTable_Payments();
        $post = $this->_request->getPost();
        $data = $post['data'];
        $signature = $post['signature'];
        if (!$liqModel->check($data, $signature)) {
            die;
        }

        $result = base64_decode($data);
        $result = json_decode($result, true);

        if ($result['status'] == 'success') {
            $params = [
                'name' => 'Приватбанк',
                'order_id' => $result['order_id'],
                'payment' => $result['amount'],

            ];
            $paymentModel->save($params);
        }
        die;
    }

    public function paymentAction() {
        $liqModel = new Privatbank_Model_LiqPay();
        $route = new Zend_Controller_Action_Helper_Redirector;
        $paymentModel = new Application_Model_DbTable_Payments();

        $public = Zend_Registry::get('rootDomain');
        $sheme = Zend_Controller_Front::getInstance()->getRequest()->getScheme();
        $post = $this->_request->getPost();
        $data = $post['data'];
        $signature = $post['signature'];
        if (!$liqModel->check($data, $signature)) {
            $route->gotoUrl($sheme . '://' . $public)->redirectAndExit();
        }

        $result = base64_decode($data);
        $result = json_decode($result, true);

        if ($result['status'] == 'success') {
            $params = [
                'name' => 'Приватбанк',
                'order_id' => $result['order_id'],
                'payment' => $result['amount'],

            ];
            $paymentModel->save($params);
        }
        $route->gotoUrl($sheme . '://' . $public)->redirectAndExit();
    }
}