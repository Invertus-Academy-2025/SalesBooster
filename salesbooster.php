<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class SalesBooster extends Module
{
    protected $action_message;
    protected $resultofsync;
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

        return (
            parent::install()
            && Configuration::updateValue('MYMODULE_NAME', 'salesbooster')
        );
    }

    public function uninstall():bool
    {
        return (
            parent::uninstall()
            && Configuration::deleteByName('MYMODULE_NAME')
        );
    }

    public function getTabs()
    {
        return [
            [
                'class_name' => 'AdminDemoSymfonyForm',
                'visible' => true,
                'name' => 'Admin symfony form single',
                'parent_class_name' => 'CONFIGURE',
            ],
            [
                'class_name' => 'AdminDemoSymfonyFormMultipleForms',
                'visible' => true,
                'name' => 'Admin symfony form multiple forms',
                'parent_class_name' => 'CONFIGURE',
            ],
        ];
    }

    /**
     * This method handles the module's configuration page
     * @return string The page's HTML content
     */
    public function getContent()
    {
        $startDate = Tools::getValue('start_date', '2025-01-01');
        $endDate = Tools::getValue('end_date', '2026-01-01');

        // Handle Action 1
        if (Tools::isSubmit('submitActionSendProducts')) {
            $this->processActionSendProducts();
        }

        // Handle Action 2
        if (Tools::isSubmit('submitActionSendOrders')) {
            $this->processActionSendOrders();
        }

        if (Tools::isSubmit('submitSalesAnalysis')) {
            $data = $this->fetchBackendData($startDate, $endDate);
        } else {
            $data = [
                'analysis' => [
                    'products' => [],
                    'opinion'  => ''
                ],
                'metadata' => [
                    'total_products' => 0,
                    'total_orders'   => 0,
                    'total_quantity' => 0,
                    'analysis_date'  => '',
                ],
            ];
        }

        $currentUrl = $this->context->link->getAdminLink('AdminModules', true, [], [
            'configure' => $this->name,
            'tab_module' => 'other',
            'module_name' => $this->name,
        ]);

        $this->context->smarty->assign([
            'currentUrl'  => $currentUrl,
            'products'    => $data['analysis']['products'],
            'metadata'    => $data['metadata'],
            'opinion'     => $data['analysis']['opinion'],
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'action_message' => $this->action_message,
            'resultofsync' => $this->resultofsync,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
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

            $info= self::sendApiRequest("http://php:80/api/saveproducts", $json);

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
//                    $orderData[] = [
//                        // Order data
//                        'order_id' => (int)$order->id,
//                        'date' => $order->date_add,
//                        'customer_id' => (int)$order->id_customer,
//                        'total_paid' => (float)$order->total_paid,
//                        'payment_method' => $order->payment,
//                        'order_status' => $status->name,
//
//                        // Product data
//                        'product_id' => (int)$product['product_id'],
//                        'product_name' => $product['product_name'],
//                        'quantity' => (int)$product['product_quantity'],
//                        'unit_price' => (float)$product['unit_price_tax_incl']
//                    ];

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

//            $order = [
//                'order_id' => 2,
//                'order_date' => '2025-03-10 17:05:39',
//                'product_id' => 2,
//                'quantity' => 20
//            ];

            //$jsonorder = json_encode([$order], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $info= self::sendApiRequest("http://php:80/api/saveorders", $json);

            if ($json === false) {
                throw new Exception('JSON encoding failed: ' . json_last_error_msg());
            }

            $this->action_message = htmlspecialchars($json, ENT_QUOTES);
            $this->resultofsync = htmlspecialchars($info, ENT_QUOTES);

        } catch (Exception $e) {
            $this->action_message = $this->l('Error: ') . $e->getMessage();
        }
    }

}
