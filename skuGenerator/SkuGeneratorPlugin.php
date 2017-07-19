<?php

/**
 * Description of SkuGeneratorPlugin
 *
 * @author DartVadius
 */
class SkuGeneratorPlugin extends Installer_Plugin {
    public function setButton($id) {
        return '<a href="/skuGenerator/sku-generator/product/id/'. $id['id'] .'" class="btn btn-primary">Generate sku</a>';
    }
}
