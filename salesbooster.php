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

        //Test data
        try {
            $exists = Db::getInstance()->getValue('SELECT 1 FROM `' . _DB_PREFIX_ . 'salesbooster_discount` WHERE `id_product` = 1'); // Use the ID you choose below

            if (!$exists) {
                $testDiscountData = [
                    'id_product' => 1,
                    'discount_percentage' => 25.50,
                    'is_selected' => 1,
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s'),
                ];

                $inserted = Db::getInstance()->insert(
                    'salesbooster_discount',
                    $testDiscountData,
                    false,
                    true,
                    Db::INSERT
                );

                if (!$inserted) {
                    PrestaShopLogger::addLog('SalesBooster: Failed to insert temporary test discount data during install.', 2);
                } else {
                    PrestaShopLogger::addLog('SalesBooster: Inserted temporary test discount data for product ID 1.', 1);
                }
            } else {
                PrestaShopLogger::addLog('SalesBooster: Temporary test discount data for product ID 1 already exists, skipping insertion.', 1);
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('SalesBooster: Error inserting temporary test discount data: ' . $e->getMessage(), 3);
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
        $startDate = Tools::getValue('start_date', '2025-01-01');
        $endDate = Tools::getValue('end_date', '2026-01-01');
        $this->action_message = ''; // Initialize messages
        $this->resultofsync = '';
        $this->modulemessage = '';

        // --- Existing Form Handlers ---
        if (Tools::isSubmit('submitSelectedProducts')) {
            // Keep the OLD logic for now, we'll change this later
            $selectedProducts = Tools::getValue('selected_products');
            $discounts = Tools::getValue('discounts');

            if (!empty($selectedProducts)) {
                Configuration::updateValue('SALESBOOSTER_SELECTED_PRODUCTS', json_encode($selectedProducts));
                Configuration::updateValue('SALESBOOSTER_DISCOUNTS', json_encode($discounts));

                try {
                    // This still uses the old $discounts array from the form
                    $this->processProductDiscounts($discounts);
                } catch (Exception $e) {
                    PrestaShopLogger::addLog("Discount processing failed: " . $e->getMessage(), 3);
                }
                $this->modulemessage = "The banner and the discounts were updated (using old method)"; // Clarify message
            } else {
                Configuration::deleteByName('SALESBOOSTER_SELECTED_PRODUCTS');
                Configuration::deleteByName('SALESBOOSTER_DISCOUNTS');
                $this->modulemessage = "The banner was disabled (old method)"; // Clarify message
            }
        }

        $savedDiscountsJson = Configuration::get('SALESBOOSTER_DISCOUNTS');
        $savedDiscounts = $savedDiscountsJson ? json_decode($savedDiscountsJson, true) : [];
        $selectedProductsJson = Configuration::get('SALESBOOSTER_SELECTED_PRODUCTS');
        $savedProductIds = $selectedProductsJson ? json_decode($selectedProductsJson, true) : [];

        if (Tools::isSubmit('submitActionSendProducts')) {
            $this->processActionSendProducts();
        }
        if (Tools::isSubmit('submitActionSendOrders')) {
            $this->processActionSendOrders();
        }
        // --- End Existing Form Handlers ---


        // --- Fetch Data for Analysis Table ---
        if (Tools::isSubmit('submitSalesAnalysis')) {
            $data = $this->fetchBackendData($startDate, $endDate);
        } else {
            $data = [
                'analysis' => ['products' => [], 'opinion'  => ''],
                'metadata' => ['total_products' => 0, 'total_orders' => 0, 'total_quantity' => 0, 'analysis_date' => ''],
            ];
        }
        // --- End Fetch Data for Analysis Table ---


        // --- Fetch Data for NEW Selected Discounts Table ---
        $selectedDiscountDetails = [];
        $selectedDiscountsCollection = SalesBoosterDiscount::getDiscountSuggestions(true); // Get only is_selected = true

        foreach ($selectedDiscountsCollection as $discount) {
            /** @var SalesBoosterDiscount $discount */
            $product = new Product($discount->id_product, false, $this->context->language->id);
            if (Validate::isLoadedObject($product)) {
                $selectedDiscountDetails[] = [
                    'id_product' => $discount->id_product,
                    'product_name' => $product->name, // Get product name
                    'discount_percentage' => $discount->discount_percentage,
                    // Add other fields from SalesBoosterDiscount if needed later
                ];
            } else {
                // Optionally log if a product referenced in the discount table doesn't exist
                PrestaShopLogger::addLog(
                    "SalesBooster: Product ID {$discount->id_product} found in salesbooster_discount table but not in product table.",
                    2 // Warning level
                );
            }
        }
        // --- End Fetch Data for NEW Selected Discounts Table ---


        $currentUrl = $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->name,
            'tab_module' => 'other',
            'module_name' => $this->name,
        ]);

        // Assign all variables to Smarty
        $this->context->smarty->assign([
            'currentUrl'  => $currentUrl,
            'products'    => $data['analysis']['products'],
            'metadata'    => $data['metadata'],
            'opinion'     => $data['analysis']['opinion'],
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'action_message' => $this->action_message,
            'resultofsync' => $this->resultofsync,
            'modulemessage' => $this->modulemessage,
            // Variables for the OLD table (Product Trends)
            'selectedProducts' => $savedProductIds,
            'savedDiscounts' => $savedDiscounts,
            // NEW Variable for the new table (Applied Discounts)
            'applied_discounts' => $selectedDiscountDetails, // Changed name for clarity
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function hookDisplayCrossSellingShoppingCart($params)
    {
        // 1. Get Product IDs from Configuration
        $selectedProductsJson = Configuration::get('SALESBOOSTER_SELECTED_PRODUCTS');
        if (empty($selectedProductsJson)) {
            return ''; // No products configured
        }

        $selectedProductIds = json_decode($selectedProductsJson, true);
        if (!is_array($selectedProductIds) || empty($selectedProductIds)) {
            return '';
        }

        // Filter out non-numeric IDs just in case
        $selectedProductIds = array_filter($selectedProductIds, 'is_numeric');
        $selectedProductIds = array_map('intval', $selectedProductIds);

        if (empty($selectedProductIds)) {
            return ''; // No valid product IDs found
        }

        // 2. Get Product Data (Formatted for the template)
        $productsForTemplate = [];
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $assembler = new ProductAssembler($this->context);

        foreach ($selectedProductIds as $productId) {
            // Ensure product exists and is active for the current context
            $product = new Product($productId, false, $this->context->language->id, $this->context->shop->id);

            if (Validate::isLoadedObject($product) && $product->isAssociatedToShop() && $product->active) {
                $productData = $assembler->assembleProduct(['id_product' => $productId]);
                if($productData) {
                    $productsForTemplate[] = $presenter->present(
                        $presentationSettings,
                        $productData,
                        $this->context->language
                    );
                }
            }
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

            $this->action_message = htmlspecialchars($json, ENT_QUOTES);
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

            $this->action_message = htmlspecialchars($json, ENT_QUOTES);
            $this->resultofsync = htmlspecialchars($info, ENT_QUOTES);

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
        // Validate input
        if ($discount < 0 || $discount > 100) {
            throw new Exception("Invalid discount value for product $productId");
        }

        $product = new Product($productId);
        if (!Validate::isLoadedObject($product)) {
            throw new Exception("Product $productId not found");
        }

        // Create/update specific price
        $this->updateSpecificPrice(
            $productId,
            $discount,
            $this->context->shop->id,
            $this->context->currency->id,
            $this->context->customer->id_default_group
        );
    }

    protected function updateSpecificPrice(
        int $productId,
        int $discount,
        int $shopId,
        int $currencyId,
        ?int $groupId = null
    ) {
        try {
            $groupId = $groupId ?? 0;

            // 1. Find existing specific price with exact matching criteria
            $existingPrice = SpecificPrice::getSpecificPrice(
                $productId,
                $shopId,
                $currencyId,
                0,
                $groupId,
                1,
                0,
                0,
                0
            );

            // 2. Handle discount removal
            if ($discount <= 0) {
                if ($existingPrice && isset($existingPrice['id_specific_price'])) {
                    $specificPrice = new SpecificPrice($existingPrice['id_specific_price']);
                    if ($specificPrice->delete()) {
                        PrestaShopLogger::addLog("Deleted specific price for product $productId", 1);
                    }
                }
                return;
            }

            $specificPrice = $existingPrice ? new SpecificPrice($existingPrice['id_specific_price']) : new SpecificPrice();

            // Set mandatory fields
            $specificPrice->id_product = $productId;
            $specificPrice->id_shop = $shopId;
            $specificPrice->id_currency = $currencyId;
            $specificPrice->id_group = $groupId;
            $specificPrice->from_quantity = 1;
            $specificPrice->price = -1; // default product price
            $specificPrice->reduction_type = 'percentage';
            $specificPrice->reduction = $discount / 100;
            // universal
            $specificPrice->id_country = 0;
            $specificPrice->id_customer = 0;
            $specificPrice->from = '0000-00-00';
            $specificPrice->to = '0000-00-00';

            if (!$specificPrice->save()) {
                throw new Exception("Failed to save specific price: " . print_r($specificPrice->getErrors(), true));
            }

        } catch (Exception $e) {
            PrestaShopLogger::addLog("Price update error: {$e->getMessage()}", 3);
            throw $e;
        }
    }

}
