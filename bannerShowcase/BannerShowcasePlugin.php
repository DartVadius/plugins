<?php

/**
 * Description of BannerShowcasePlugin
 *
 * @author DartVadius
 */
class BannerShowcasePlugin extends Installer_Plugin {

    /**
     * Использование: $this->plugin('plugin_name', 'frontend')->showBanner('banner_name', 'product_id', 'category_id', 'width');
     * Варианты использования: 
     *    ($a->showBanner($banner_name)) - баннер выводится везде, где есть код 
     *    вызова, без всяких проверок
     *    $a->showBanner($banner_name, NULL, NULL, 100) - выводим баннер с заданной
     *    шириной, дополнительные проверки не выполняются
     *    $a->showBanner($banner_name, $product_id, NULL, 100) - проверяем наличие
     *    переданного Id продукта в сохраненных настройках баннера, если совпадений
     *    не найдено - ничего не выводим
     *    $a->showBanner($banner_name, NULL, $category_id, 100) - то же, что и с
     *    продуктом, но для категорий
     * 
     * @param string $name          banner name
     * @param integer $product      product Id
     * @param integer $category     category Id
     * @param integer $width        width of banner
     * @return boolean | string
     */
    public function showBanner($name, $product = NULL, $category = NULL, $width = NULL) {
        $bannerModel = new BannerShowcase_Model_Banner();
        $productId = json_decode($bannerModel->getByName($name)['product'], TRUE);
        $categoryId = json_decode($bannerModel->getByName($name)['category'], TRUE);
        if ($product) {
            if (!in_array($product, $productId)) {
                return FALSE;
            }
        }
        if ($category) {
            if (!in_array($category, $categoryId)) {
                return FALSE;
            }
        }
        return $bannerModel->showBanner($name, $width);
    }

}
