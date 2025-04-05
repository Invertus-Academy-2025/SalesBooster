<?php
if (!defined('_PS_VERSION_')) {
    exit;
}
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Core\Product\ProductPresenter;

require_once _PS_MODULE_DIR_ . 'salesbooster/classes/SalesBoosterDiscount.php';

class SalesBooster extends Module
{
    protected $action_message;
    protected $resultofsync;
    protected $modulemessage;
    public function __construct()
    {
        $this->name = 'salesbooster';
        $this->tab = 'other';
        $this->version = '1.0.0';
        $this->author = 'Domas ir Ignas';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => '8.99.99',
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('SalesBooster', [], 'Modules.Mymodule.Admin');
        $this->description = $this->trans('Analyse sale trends using chatGPT and do promotions.', [], 'Modules.Mymodule.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Mymodule.Admin');

        if (!Configuration::get('MYMODULE_NAME')) {
            $this->warning = $this->trans('No name provided', [], 'Modules.Mymodule.Admin');
        }
    }

    public function install():bool
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'salesbooster_discount` (
            `id_salesbooster_discount` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `id_product` INT UNSIGNED NOT NULL,
            `discount_percentage` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
            `is_selected` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            INDEX `idx_id_product` (`id_product`),
            INDEX `idx_is_selected` (`is_selected`)
         ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        if (!Db::getInstance()->execute($sql)) {
            PrestaShopLogger::addLog('SalesBooster: Failed to create salesbooster_discount table.', 3);
            return false;
        }

        return (
            parent::install()
            && Configuration::updateValue('MYMODULE_NAME', 'salesbooster')
            && $this->registerHook('displayCrossSellingShoppingCart')
            && $this->registerHook('actionFrontControllerSetMedia')
        );
    }

    public function uninstall(): bool
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'salesbooster_discount`;';

        if (!Db::getInstance()->execute($sql)) {
            PrestaShopLogger::addLog('SalesBooster: Failed to drop salesbooster_discount table.', 3);
            return false;
        }

        return (
            parent::uninstall()
            && Configuration::deleteByName('MYMODULE_NAME')
            && Configuration::deleteByName('SALESBOOSTER_SELECTED_PRODUCTS')
            && Configuration::deleteByName('SALESBOOSTER_DISCOUNTS')
        );
    }

    /**
     * Add the CSS & JavaScript files you need for the carousel
     */
    public function hookActionFrontControllerSetMedia()
    {
        // Only load assets on the cart page
        if ($this->context->controller->php_self !== 'cart') {
            return;
        }

        $this->context->controller->registerStylesheet(
            'modules-salesbooster-slick-css', // Unique ID
            'modules/'.$this->name.'/views/css/slick.css', // Local path
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerStylesheet(
            'modules-salesbooster-slick-theme-css', // Unique ID
            'modules/'.$this->name.'/views/css/slick-theme.css', // Local path
            ['media' => 'all', 'priority' => 151]
        );

        // 2. Add Slick JS (Local)
        $this->context->controller->registerJavascript(
            'modules-salesbooster-slick-js', // Unique ID
            'modules/'.$this->name.'/views/js/slick.min.js', // Local path
            [
                'position' => 'bottom',
                'priority' => 150,
            ]
        );

        $this->context->controller->registerStylesheet(
            'modules-salesbooster-custom-css',
            'modules/'.$this->name.'/views/css/salesbooster-carousel.css', // Local path
            ['media' => 'all', 'priority' => 152] // Load after Slick CSS
        );

        $this->context->controller->registerJavascript(
            'modules-salesbooster-custom-js',
            'modules/'.$this->name.'/views/js/salesbooster-carousel.js', // Local path
            ['position' => 'bottom', 'priority' => 151] // Load after Slick JS
        );
    }
    /**
     * This method handles the module's configuration page
     * @return string The page's HTML content
     */
    public function getContent()
    {
        $startDate = Tools::getValue('start_date', date('Y-m-d', strtotime('-1 year'))); // Default start date
        $endDate = Tools::getValue('end_date', date('Y-m-d')); // Default end date
        $this->action_message = '';
        $this->resultofsync = '';
        $this->modulemessage = '';

        if (Tools::isSubmit('submitAddSuggestions')) {
            $this->handleSuggestionSubmission();
        }

        $deselectionProductId = $this->getDeselectionProductId();
        if ($deselectionProductId > 0) {
            $this->handleDeselection($deselectionProductId);
        }

        if (Tools::isSubmit('submitActionSendDataToExternal')) {
            $this->processActionSendProducts();
            $this->processActionSendOrders();
        }

        $currentlySelectedProductIds = [];
        $selectedDiscountsCollection = SalesBoosterDiscount::getDiscountSuggestions(true);
        foreach ($selectedDiscountsCollection as $discount) {
            $currentlySelectedProductIds[] = (int)$discount->id_product;
        }

        $analysisData = [
            'analysis' => ['products' => [], 'opinion'  => ''],
            'metadata' => ['total_products' => 0, 'total_orders' => 0, 'total_quantity' => 0, 'analysis_date' => ''],
        ];

        if (Tools::isSubmit('submitSalesAnalysis')) {
            try {
                $analysisData = $this->fetchBackendData($startDate, $endDate);
                $this->modulemessage = $this->trans('Analysis successful!', [], 'Modules.Salesbooster.Admin');

            } catch (Exception $e) {
                $this->modulemessage = $this->trans('Error fetching analysis data: ', [], 'Modules.Salesbooster.Admin') . $e->getMessage();
            }
        }

        $suggestionProducts = [];
        if (isset($analysisData['analysis']['products']) && is_array($analysisData['analysis']['products'])) {
            foreach ($analysisData['analysis']['products'] as $product) {
                if (isset($product['product_id']) && is_numeric($product['product_id'])) {
                    $productId = (int)$product['product_id'];
                    if (!in_array($productId, $currentlySelectedProductIds)) {
                        $suggestionProducts[] = $product;
                    }
                }
            }
        }

        $appliedDiscountDetails = [];
        foreach ($selectedDiscountsCollection as $discount) {
            $product = new Product($discount->id_product, false, $this->context->language->id);
            if (Validate::isLoadedObject($product)) {
                $appliedDiscountDetails[] = [
                    'id_salesbooster_discount' => $discount->id,
                    'id_product' => $discount->id_product,
                    'product_name' => $product->name,
                    'discount_percentage' => $discount->discount_percentage,
                ];
            }
        }

        $currentUrl = $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->name,
            'tab_module' => 'other',
            'module_name' => $this->name,
        ]);

        $this->context->smarty->assign([
            'currentUrl'  => $currentUrl,
            'suggestion_products' => $suggestionProducts,
            'analysis_opinion'     => $analysisData['analysis']['opinion'] ?? '',
            'applied_discounts' => $appliedDiscountDetails,
            'metadata'    => $analysisData['metadata'],
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'action_message' => $this->action_message,
            'resultofsync' => $this->resultofsync,
            'modulemessage' => $this->modulemessage,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function hookDisplayCrossSellingShoppingCart($params): string
    {
        $selectedDiscounts = SalesBoosterDiscount::getDiscountSuggestions(true);

        if (!$selectedDiscounts->count()) {
            return '';
        }

        $productsForTemplate = [];
        try {
            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $pricePrecision = Configuration::get('PS_PRICE_DISPLAY_PRECISION') ?? 2;

            $presenter = new ProductPresenter(
                new ImageRetriever($this->context->link),
                $this->context->link,
                new PriceFormatter(),
                new ProductColorsRetriever(),
                $this->context->getTranslator()
            );

            $assembler = new ProductAssembler($this->context);

            foreach ($selectedDiscounts as $discount) {
                $productId = (int)$discount->id_product;
                $salesBoosterDiscountPercentage = (float)$discount->discount_percentage;

                $product = new Product($productId, false, $this->context->language->id, $this->context->shop->id);

                if (Validate::isLoadedObject($product) && $product->isAssociatedToShop() && $product->active) {
                    $productData = $assembler->assembleProduct(['id_product' => $productId]);
                    if($productData) {
                        $presentedProduct = $presenter->present(
                            $presentationSettings,
                            $productData,
                            $this->context->language
                        );

                        if ($salesBoosterDiscountPercentage > 0) {
                            $presentedProduct['has_discount'] = true;
                            $presentedProduct['discount_type'] = 'percentage';
                            $formattedPercentage = '-' . number_format($salesBoosterDiscountPercentage, $pricePrecision) . '%';
                            $presentedProduct['discount_percentage'] = $formattedPercentage;
                            $presentedProduct['discount_percentage_absolute'] = $salesBoosterDiscountPercentage;
                        } else {
                            $presentedProduct['has_discount'] = false;
                            unset(
                                $presentedProduct['discount_type'],
                                $presentedProduct['discount_percentage'],
                                $presentedProduct['discount_percentage_absolute'],
                                $presentedProduct['discount_amount_to_display']
                            );
                            $presentedProduct['regular_price'] = null;
                        }

                        $productsForTemplate[] = $presentedProduct;
                    }
                }
            }
        } catch (Exception $e) {
            return '';
        }

        if (!empty($productsForTemplate)) {
            $this->context->smarty->assign([
                'salesbooster_products' => $productsForTemplate,
                'salesbooster_title' => $this->l('You might also like'),
            ]);

            return $this->display(__FILE__, 'views/templates/hook/focarousel.tpl');
        }

        return '';
    }

    private function fetchBackendData(string $startDate, string $endDate): array
    {
        $url = "http://php:80/api/analyse-sales/{$startDate}/{$endDate}";
        $response = file_get_contents($url);

        if ($response === false) {
            dd(error_get_last());
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            dd('JSON decode error: ' . json_last_error_msg());
        }

        return $data;
    }

    private function handleSuggestionSubmission(): void
    {
        $submittedProductIds = array_map('intval', Tools::getValue('selected_suggestions', []));
        $submittedDiscounts = Tools::getValue('suggestion_discounts', []);

        if (empty($submittedProductIds)) {
            $this->modulemessage = $this->trans('No suggestions were selected to add.', [], 'Modules.Salesbooster.Admin');
            return;
        }

        foreach ($submittedProductIds as $productId) {
            if ($productId <= 0) {
                continue;
            }

            $discountValueInput = $submittedDiscounts[$productId] ?? '';
            $discountPercentage = 0.0;

            if (is_numeric($discountValueInput)) {
                $discountValueFloat = (float)$discountValueInput;
                if ($discountValueFloat > 0 && $discountValueFloat <= 100) {
                    $discountPercentage = $discountValueFloat;
                } elseif ($discountValueFloat > 100) {
                    $discountPercentage = 100.0;
                }
            }

            $discountEntry = SalesBoosterDiscount::findByProductId((int)$productId);

            if ($discountEntry instanceof SalesBoosterDiscount && $discountEntry->id) {
                $discountEntry->is_selected = true;
                $discountEntry->discount_percentage = $discountPercentage;
                if ($discountEntry->update()) {
                    $this->applyProductDiscount($productId, $discountPercentage);
                }
            } else {
                $newDiscount = new SalesBoosterDiscount();
                $newDiscount->id_product = (int)$productId;
                $newDiscount->is_selected = true;
                $newDiscount->discount_percentage = $discountPercentage;
                if ($newDiscount->add()) {
                    $this->applyProductDiscount($productId, $discountPercentage);
                }
            }
        }

        $this->modulemessage = $this->trans('Suggestions added!', [], 'Modules.Salesbooster.Admin');
    }

    private function handleDeselection(int $productIdToDeselect): void
    {
        $discountEntry = SalesBoosterDiscount::findByProductId($productIdToDeselect);

        if ($discountEntry instanceof SalesBoosterDiscount && $discountEntry->id) {
            $discountEntry->delete();
        } else {
            return;
        }

        $this->applyProductDiscount($productIdToDeselect, 0);
    }

    private function getDeselectionProductId(): int
    {
        foreach (Tools::getAllValues() as $key => $value) {
            if (strpos($key, 'submitDeselectProduct_') === 0) {
                $productId = (int)str_replace('submitDeselectProduct_', '', $key);
                if ($productId > 0) {
                    return $productId;
                }
            }
        }
        return 0;
    }

    private function processActionSendProducts(): void
    {
        try {
            $products = Product::getProducts(
                $this->context->language->id,
                0,
                0,
                'id_product',
                'ASC',
                false,
                true
            );

            $productData = array_map(function($product) {
                return [
                    'product_id' => (int)$product['id_product'],
                    'name' => $product['name'],
                    'price' => $product['price']
                ];
            }, $products);

            if (empty($productData)) {
                $this->action_message = $this->l('No products found');
                return;
            }

            // Convert to pretty-printed JSON
            $json = json_encode($productData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $info= self::sendApiRequest("http://php:80/api/save-products", $json);

            if ($json === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }

            $this->resultofsync = htmlspecialchars($info, ENT_QUOTES);

        } catch (Exception $e) {
            $this->action_message = $this->l('Error: ') . $e->getMessage();
        }
    }

    private function processActionSendOrders(): void
    {
        try {
            $statusIds = [
                (int)Configuration::get('PS_OS_DELIVERED'),
                (int)Configuration::get('PS_OS_PAYMENT'),
                (int)Configuration::get('PS_OS_PREPARATION'),
                (int)Configuration::get('PS_OS_WS_PAYMENT'),
                (int)Configuration::get('PS_OS_SHIPPING')
            ];

            $statusIds = array_filter($statusIds, function($id) {
                return $id > 0;
            });

            if (empty($statusIds)) {
                $this->action_message = $this->l('No valid order statuses configured');
                return;
            }

            $completedOrders = [];
            foreach ($statusIds as $statusId) {
                $orders = Order::getOrderIdsByStatus($statusId);
                $completedOrders = array_merge($completedOrders, $orders);
            }

            $completedOrders = array_unique($completedOrders);

            if (empty($completedOrders)) {
                $this->action_message = $this->l('No orders found in selected statuses');
                return;
            }

            $orderData = [];

            foreach ($completedOrders as $orderId) {
                $order = new Order((int)$orderId);

                $status = new OrderState($order->current_state, $this->context->language->id);

                foreach ($order->getProducts() as $product) {

                    $orderData[] = [
                        // Order data
                        'order_id' => (int)$order->id,
                        'order_date' => $order->date_add,
                        // Product data
                        'product_id' => (int)$product['product_id'],
                        'quantity' => (int)$product['product_quantity']
                    ];
                }
            }

            $json = json_encode($orderData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $info= self::sendApiRequest("http://php:80/api/save-orders", $json);

            if ($json === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }

            $this->resultofsync .= '. ' . htmlspecialchars($info, ENT_QUOTES);

        } catch (Exception $e) {
            $this->action_message = $this->l('Error: ') . $e->getMessage();
        }
    }
    public static function sendApiRequest($apiEndpoint, $jsonData)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiEndpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            throw new Exception('API connection failed: ' . $curlError);
        }

        if ($statusCode !== 201) {
            $errorBody = json_decode($response, true) ?: $response;

            $errorMessage = $errorBody['error'] ?? $errorBody['message'] ?? 'Unknown API error';

            throw new Exception("API Error: $errorMessage ($statusCode)");
        }

        $responseBody = json_decode($response, true);

        $successMessage = $responseBody['message'] ?? $responseBody['error'] ?? null;

        if (!$successMessage) {

            throw new Exception('Invalid API response format');
        }

        return $successMessage;
    }

    protected function processProductDiscounts(array $discounts)
    {

        try {
            foreach ($discounts as $productId => $discountValue) {
                if($discountValue != '%' || $discountValue != null) {
                    $this->applyProductDiscount((int)$productId, (int)$discountValue);
                }
            }
            $this->confirmations[] = $this->l('Discounts updated successfully');
        } catch (Exception $e) {
            PrestaShopLogger::addLog("Discount error: {$e->getMessage()}", 3);
            $this->errors[] = $this->l('Error updating discounts');
        }
    }

    protected function applyProductDiscount(int $productId, float $discount)
    {
        if ($discount < 0 || $discount > 100) {
            throw new Exception("Invalid discount value ($discount) for product $productId. Must be between 0 and 100.");
        }

        if ($discount > 0) {
            $product = new Product($productId);
            if (!Validate::isLoadedObject($product)) {
                throw new Exception("Product $productId not found when trying to apply discount.");
            }
        }

        $this->updateSpecificPrice(
            $productId,
            $discount,
            $this->context->shop->id,
            $this->context->currency->id,
            0
        );
    }

    protected function updateSpecificPrice(
        int $productId,
        float $discount,
        int $shopId,
        int $currencyId,
        ?int $groupId = null
    ) {
        try {
            $targetGroupId = 0;
            $id_specific_price = 0;

            $existingPriceRuleArray = SpecificPrice::getSpecificPrice(
                $productId,
                $shopId,
                $currencyId,
                0,
                $targetGroupId,
                1,
                0,
                0,
                0,
                1
            );

            if (is_array($existingPriceRuleArray) && isset($existingPriceRuleArray['id_specific_price'])) {
                $id_specific_price = (int)$existingPriceRuleArray['id_specific_price'];
            }

            if ($discount < 0.0001) {
                if ($id_specific_price > 0) {
                    $specificPriceToDelete = new SpecificPrice($id_specific_price);
                    if (Validate::isLoadedObject($specificPriceToDelete)) {
                        if ($specificPriceToDelete->delete()) {
                            Cache::clean('SpecificPrice::getSpecificPrice_' . $productId . '-' . $shopId . '-*');
                        }
                    }
                }
                return;
            }

            $specificPrice = new SpecificPrice($id_specific_price);

            $specificPrice->id_product = $productId;
            $specificPrice->id_product_attribute = 0;
            $specificPrice->id_shop = $shopId;
            $specificPrice->id_currency = $currencyId;
            $specificPrice->id_country = 0;
            $specificPrice->id_group = $targetGroupId;
            $specificPrice->id_customer = 0;
            $specificPrice->price = -1.00;
            $specificPrice->from_quantity = 1;
            $specificPrice->reduction = round($discount / 100.0, 6);
            $specificPrice->reduction_tax = 1;
            $specificPrice->reduction_type = 'percentage';
            $specificPrice->from = '0000-00-00 00:00:00';
            $specificPrice->to = '0000-00-00 00:00:00';

            if (!$specificPrice->save()) {
                throw new Exception("Failed to save specific price for product $productId.");
            } else {
                Cache::clean('SpecificPrice::getSpecificPrice_' . $productId . '-' . $shopId . '-*');
            }

        } catch (Exception $e) {
            throw $e;
        }
    }
}
