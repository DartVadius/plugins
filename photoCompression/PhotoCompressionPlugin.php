<?php

/**
 * Description of PhotoCompressionPlugin
 *
 * @author DartVadius
 */
class PhotoCompressionPlugin extends Installer_Plugin {

    public function setButtonCompressProduct($id) {
        $photo = new PhotoCompression_Model_PhotoCompression();
        $translate = Zend_Registry::get('Zend_Translate');
        if ($id['id'] != -1) {
            if ($photo->checkApiKey()) {
                return '<a id="compress" href="/photoCompression/photo-compression/compress-product/id/' . $id['id'] . '" class="btn btn-primary">' . $translate->_('Compress images') . '</a>'
                        . '<span id="compress_spinner" style="display: none"><i class="fa fa-spinner fa-spin fa-2x fa-fw"></i></span>';
            } else {
                return "<span>" . $translate->_('Photo compression: wrong API key') . "</span>";
            }
        }
    }

    public function setButtonCompressCategory($id) {
        $photo = new PhotoCompression_Model_PhotoCompression();
        $translate = Zend_Registry::get('Zend_Translate');
        if ($id['id'] != -1) {
            if ($photo->checkApiKey()) {
                return '<a id="compress" href="/photoCompression/photo-compression/compress-category/id/' . $id['id'] . '" class="btn btn-primary">' . $translate->_('Compress images') . '</a>';
            } else {
                return "<span>" . $translate->_('Photo compression: wrong API key') . "</span>";
            }
        }
    }

}
