<?php

/**
 * Description of HeybroAjaxController
 *
 * @author DartVadius
 */
class Heybro_AjaxController extends Zend_Controller_Action {

    /**
     * edit post card
     */
    public function editAction() {
        Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');
        $heybroModel = new Heybro_Model_Heybro();
        $post = $this->_request->getPost();
        $form = [];
        $code = $post['code'];
        parse_str($post['form'], $form);
//        print_r($date);
//        die;
        $img = imagecreatefromjpeg(PUBLIC_PATH . "/cards/$code.jpg");
        $black = imagecolorallocate($img, 0x00, 0x00, 0x00);
        imagefttext($img, 38, 0, 320, 1550, $black, APPLICATION_PATH . '/plugins/frontend/heybro/controllers/Norasi.ttf', $code);
        imagefttext($img, 76, 0, 1500, 320, $black, APPLICATION_PATH . '/plugins/frontend/heybro/controllers/Norasi.ttf', $form['name']);
        imagefttext($img, 76, 0, 1500, 740, $black, APPLICATION_PATH . '/plugins/frontend/heybro/controllers/Norasi.ttf', date_create($form['date'])->Format('d-m-Y'));
        imagefttext($img, 76, 0, 1500, 1140, $black, APPLICATION_PATH . '/plugins/frontend/heybro/controllers/Norasi.ttf', $form['character']);
        imagejpeg($img, PUBLIC_PATH . "/cards/$code.jpg");
        imagedestroy($img);

        $heybroModel->setEditTrueByCode($code);

        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/plugins/frontend/heybro/views/scripts/partials/');
        $html->assign('cardsReady', $heybroModel->getCardsReadyByUserId($post['user_id']));
        $html->assign('cardsEdit', $heybroModel->getCardsEditByUserId($post['user_id']));
        $page = $html->render('set-img-partial.phtml');
        $this->_helper->json(['html' => $page]);
//        die;
    }

    /**
     * get summ of selected products
     */
    public function checkProductAction() {
        Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');
        $productModel = new Application_Model_DbTable_Products();

        $post = $this->_request->getPost();
        $price = 0;
        $price += $productModel->getById($post['toy'])['price'];
        $price += $productModel->getById($post['topic'])['price'];
        $price += $productModel->getById($post['pants'])['price'];
//        print_r($price);
//        die;
        $path = $post['folder'];
        $count = 0;
        if (is_dir("cards/$path")) {
            $files = scandir("cards/$path");
            $count = count($files) - 2;
        }
        $this->_helper->json([
            'count' => $count,
            'price' => $price
        ]);
    }
    
    /**
     * find out sku id
     */
    public function getSkuAction() {
        $skuModel = new Application_Model_DbTable_ProductSku();
        $id = $this->_request->getParam('id');
        $sku = $skuModel->getByProductId($id);
        $this->_helper->json([
            'id' => $sku[0]['id'],
        ]);
    }

}
