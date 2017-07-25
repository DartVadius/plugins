<?php

/**
 * Description of HeybroPlugin
 *
 * @author DartVadius
 */
class HeybroPlugin extends Installer_Plugin {
    
    public function setLink($arr) {
        return '<li><a href="/cabinet/heybro">Heybro</a></li>';
    }

    /**
     * create card template on change order status
     * 
     * @param array $request
     * @param array $data
     * @return boolean
     */
    public function setCard($request, $data) {
//        print_r($data);
//        die;
        if (!is_dir('cards')) {
            mkdir('cards', 0777);
        }
        chmod(APPLICATION_PATH . '/plugins/frontend/heybro/template/template.jpg', 0777);
        chmod('cards', 0777);
        $pluginModel = new Application_Model_DbTable_Plugins();
        $configModel = new Application_Model_DbTable_PluginsSettings();
        $heybroModel = new Heybro_Model_Heybro();
        $pluginId = $pluginModel->getBySystemName('heybro')['id'];
        $typeId = $configModel->getSettingsByPluginsID($pluginId)['type'];
        if ($data['state']['type'] == 'completed') {
            $this->setTable($data, $typeId);
            $products = $heybroModel->getProductsByOrderId($data['order_id']);
            $this->setFile($products);
        }
        return TRUE;
    }

    /**
     * create template image for editing
     * 
     * @param array $products
     * @return boolean
     */
    private function setFile($products) {
        if (empty($products)) {
            return FALSE;
        }
        foreach ($products as $product) {
            $code = $product['code'];
            $fileName = $code . '.jpg';
            copy(APPLICATION_PATH . '/plugins/frontend/heybro/template/template.jpg', "cards/$fileName");
        }
        return TRUE;
    }

    /**
     * check products on right type
     * 
     * @param array $data
     * @param int $typeId
     * @return boolean
     */
    private function setTable($data, $typeId) {
        $orderItemsModel = new Order_Model_OrderItems();
        $orderModel = new Order_Model_Order();
        $productModel = new Application_Model_DbTable_Products();
        $order = $orderModel->getById($data['order_id']);
        $products = $orderItemsModel->getAllByOrderId($data['order_id']);
        if (!empty($products)) {
            foreach ($products as $product) {
                $productInfo = $productModel->getById($product['product_id']);
                if ($productInfo['type_id'] == $typeId) {
                    $this->setCode($order, $productInfo);
                }
            }
        }
        return TRUE;
    }

    /**
     * create unique code for card and
     * save card data in db
     * 
     * @param int $order
     * @param array $productInfo
     * @return boolean
     */
    private function setCode($order, $productInfo) {
        $heybroModel = new Heybro_Model_Heybro();
        $stamp = $order['id'] . $productInfo['id'];
        $convert = base_convert($stamp, 10, 36);
        $rand = 6 - strlen($convert);
        $uid = strtoupper($convert . $this->codeGenerator($rand));
        $check = $heybroModel->checkExist($uid);
        if (!$check) {
            $heybroModel->insert([
                'code' => $uid,
                'user_id' => $order['contact_id'],
                'product_id' => $productInfo['id'],
                'date' => date('Y-m-d H:i:s'),
                'order_id' => $order['id'],
            ]);
            return TRUE;
        } else {
            $this->setCode($order, $productInfo);
        }
    }

    /**
     * generate random code
     * 
     * @param int $rand
     * @return string
     */
    private function codeGenerator($rand = 2) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUWVXYZ123456789';
        $numChars = strlen($chars);
        $string = '';
        for ($i = 0; $i < $rand; $i++) {
            $string .= substr($chars, rand(1, $numChars) - 1, 1);
        }
        return $string;
    }

}
