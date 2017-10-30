<?php

/**
 * Description of PrivatbankPlugin
 *
 * @author DartVadius
 */
class PrivatbankPlugin extends Installer_Plugin {

    public function setButtonPay($params) {
        $liqModel = new Privatbank_Model_LiqPay();
        $orderModel = new Shop_Model_Order();
        $mainCurrency = Zend_Registry::get('maincurrency');
        $url = Zend_Registry::get('crmDomain');
        $sheme = Zend_Controller_Front::getInstance()->getRequest()->getScheme();

        $currencySess = new Zend_Session_Namespace('sitecurrency');
        $siteCurrency = isset($currencySess->currency) ? $currencySess->currency : $mainCurrency['main_currency']['value'];

        $total = $orderModel->getById($params['id'])['total'];

        if ($liq = $liqModel->getLiq()) {
            $html = $liq->cnb_form([
                'action' => 'pay',
                'amount' => $total,
                'currency' => $siteCurrency,
                'description' => $liqModel->getDescription(),
                'order_id' => $params['id'],
                'version' => '3',
                'sandbox' => 1,
                'result_url' => $sheme . '://' . $url . '/privatbank/index/payment',
                'server_url' => $sheme . '://' . $url . '/privatbank/index/status',
            ]);
            return $html;
        }

    }

    public function setButtonPayCabinet($params) {
        $liqModel = new Privatbank_Model_LiqPay();
        $orderModel = new Shop_Model_Order();
        $paymentModel = new Application_Model_DbTable_Payments();

        $mainCurrency = Zend_Registry::get('maincurrency');
        $url = Zend_Registry::get('crmDomain');
        $sheme = Zend_Controller_Front::getInstance()->getRequest()->getScheme();

        $currencySess = new Zend_Session_Namespace('sitecurrency');
        $siteCurrency = isset($currencySess->currency) ? $currencySess->currency : $mainCurrency['main_currency']['value'];

        $total = $orderModel->getById($params['id'])['total'];
        $payments = $paymentModel->getSumPayByOrderID($params['id']);

        $payment = $total - $payments['sum'];

        if (empty($payment) || $payment <= 0) {
            return null;
        }

        if ($liq = $liqModel->getLiq()) {
            $html = $liq->cnb_form([
                'action' => 'pay',
                'amount' => $payment,
                'currency' => $siteCurrency,
                'description' => $liqModel->getDescription(),
                'order_id' => $params['id'],
                'version' => '3',
                'sandbox' => 1,
                'result_url' => $sheme . '://' . $url . '/privatbank/index/payment',
                'server_url' => $sheme . '://' . $url . '/privatbank/index/status',
            ]);
            return $html;
        }

    }

}
