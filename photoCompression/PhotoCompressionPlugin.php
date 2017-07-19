<?php

/**
 * Description of PhotoCompressionPlugin
 *
 * @author DartVadius
 */
class PhotoCompressionPlugin extends Installer_Plugin {
    public function setButtonCompressProduct($id) {
        return '<a id="compress" href="/photoCompression/photo-compression/compress-product/id/'. $id['id'] .'" class="btn btn-primary">Сжать изображения</a>'
                . '<span id="compress_spinner" style="display: none"><i class="fa fa-spinner fa-spin fa-2x fa-fw"></i></span>';        
    }
    public function setButtonCompressCategory($id) {
        return '<a id="compress" href="/photoCompression/photo-compression/compress-category/id/'. $id['id'] .'" class="btn btn-primary">Сжать изображения</a>';
    }
}
