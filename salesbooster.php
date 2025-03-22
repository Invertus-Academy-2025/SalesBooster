<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class SalesBooster extends Module
{
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
}
