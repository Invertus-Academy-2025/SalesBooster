{block name="content"}

    <div class="panel">
        <div class="panel-heading">
            <h2 class="mb-3">{l s='Sales Booster Dashboard' mod='salesbooster'}</h2>
        </div>
        <div class="panel-body">
            {if $modulemessage}
                <div class="alert {if strpos($modulemessage|lower, 'error') !== false || strpos($modulemessage|lower, 'fail') !== false || strpos($modulemessage|lower, 'could not') !== false}alert-danger{else}alert-success{/if}" role="alert">
                    {$modulemessage}
                </div>
            {/if}
        </div>
    </div>

    <div class="panel mt-4">

        <div class="panel-body">

            <div class="panel-heading">
                <h3 class="mb-3">{l s='Data Synchronisation' mod='salesbooster'}</h3>
                <div class="mt-3">
                    <p class="mb-0">{$resultofsync}</p>
                    <textarea class="form-control" rows="12" readonly>{$action_message nofilter}</textarea>
                </div>
            </div>
            <form method="post" action="{$currentUrl|escape:'html':'UTF-8'}">
                <button type="submit" name="submitActionSendProducts" class="btn btn-success mr-2">
                    {l s='1. Sync products with backend' mod='salesbooster'}
                </button>
                <button type="submit" name="submitActionSendOrders" class="btn btn-info">
                    {l s='2. Sync orders with backend' mod='salesbooster'}
                </button>
            </form>
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-body">
            <div class="panel-heading">
                <h3 class="mb-3">{l s='Select Date Range for Analysis' mod='salesbooster'}</h3>
            </div>
            <form method="post" action="{$currentUrl|escape:'html':'UTF-8'}" class="form-inline align-items-end">
                <div class="form-group mr-2 mb-2">
                    <label for="start_date" class="mr-2">{l s='Start Date' mod='salesbooster'}</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                           value="{$start_date|escape:'html':'UTF-8'}" required>
                </div>

                <div class="form-group mr-2 mb-2">
                    <label for="end_date" class="mr-2">{l s='End Date' mod='salesbooster'}</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                           value="{$end_date|escape:'html':'UTF-8'}" required>
                </div>

                <button type="submit" name="submitSalesAnalysis" class="btn btn-primary mb-2 ml-3">
                    <i class="material-icons">analytics</i> {l s='Analyze Sales' mod='salesbooster'}
                </button>
            </form>

            <hr class="mt-4 mb-4">

            {if isset($analysis_opinion) && $analysis_opinion}
            <div class="alert alert-info" role="alert">
                <strong>{l s='Analysis Opinion:' mod='salesbooster'}</strong> {$analysis_opinion|escape:'html':'UTF-8'}
            </div>
            {/if}
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-body">
            <h3 class="mb-3">{l s='Promotion Suggestions (Not Currently Active)' mod='salesbooster'}</h3>
            <form method="post" action="{$currentUrl|escape:'html':'UTF-8'}">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr class="bg-light">
                            <th scope="col" style="width: 5%;">{l s='Add' mod='salesbooster'}</th>
                            <th scope="col" style="width: 15%;">{l s='Set Discount %' mod='salesbooster'}</th>
                            <th scope="col">{l s='Product ID' mod='salesbooster'}</th>
                            <th scope="col">{l s='Product Name' mod='salesbooster'}</th>
                            <th scope="col">{l s='Trend Status' mod='salesbooster'}</th>
                            <th scope="col">{l s='Change (%)' mod='salesbooster'}</th>
                            <th scope="col">{l s='Key Dates' mod='salesbooster'}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {if !empty($suggestion_products)}
                            {foreach from=$suggestion_products item=product}
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="selected_suggestions[]" value="{$product.product_id}">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm"
                                               name="suggestion_discounts[{$product.product_id}]"
                                               min="0"
                                               max="100"
                                               step="0.01"
                                               placeholder="{l s='e.g., 5' mod='salesbooster'}">
                                    </td>
                                    <td>{$product.product_id}</td>
                                    <td>{$product.product_name|escape:'html':'UTF-8'}</td>
                                    <td>{$product.trend_status|escape:'html':'UTF-8'}</td>
                                    <td>{if isset($product.percentage_change)}{$product.percentage_change|string_format:"%.2f"}%{/if}</td>
                                    <td>{if isset($product.key_dates) && is_array($product.key_dates)}{implode(', ', $product.key_dates)|escape:'html':'UTF-8'}{/if}</td>
                                </tr>
                            {/foreach}
                        {else}
                            <tr>
                                <td colspan="7" class="text-center">{l s='No new promotion suggestions available (or all suggestions are already active).' mod='salesbooster'}</td>
                            </tr>
                        {/if}
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="submitAddSuggestions" class="btn btn-success mt-3 d-inline-flex align-items-center">
                    <i class="material-icons mr-1" style="vertical-align: middle;">add_circle_outline</i>
                    <span>{l s='Add Selected Suggestions to Active Promotions' mod='salesbooster'}</span>
                </button>
            </form>
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-body">
            <div class="panel-heading">
                <h3 class="mb-3">{l s='Currently Active Promotions' mod='salesbooster'}</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                    <tr class="bg-info text-white">
                        <th scope="col">{l s='Product ID' mod='salesbooster'}</th>
                        <th scope="col">{l s='Product Name' mod='salesbooster'}</th>
                        <th scope="col">{l s='Applied Discount (%)' mod='salesbooster'}</th>
                        <th scope="col" style="width: 10%; text-align: center;">{l s='Actions' mod='salesbooster'}</th>
                    </tr>
                    </thead>
                    <tbody>
                    {if !empty($applied_discounts)}
                        {foreach from=$applied_discounts item=discount}
                            <tr>
                                <td>{$discount.id_product}</td>
                                <td>{$discount.product_name|escape:'html':'UTF-8'}</td>
                                <td>{$discount.discount_percentage|string_format:"%.2f"}%</td>
                                <td class="text-center">
                                    <form method="post" action="{$currentUrl|escape:'html':'UTF-8'}" style="display: inline;">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                name="submitDeselectProduct_{$discount.id_product}"
                                                title="{l s='Remove promotion for this product' mod='salesbooster'}"
                                                onclick="return confirm('{l s='Are you sure you want to remove the discount for' mod='salesbooster'} {$discount.product_name|escape:'javascript':'UTF-8'}?');">
                                            <i class="material-icons">remove_circle_outline</i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td colspan="4" class="text-center">{l s='No discounts are currently active.' mod='salesbooster'}</td>
                        </tr>
                    {/if}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-body">
            <div class="panel-heading">
                <h3 class="mb-3">{l s='Metadata' mod='salesbooster'}</h3>
            </div>
            <ul class="list-group">
                <li class="list-group-item">
                    <strong>{l s='Total Products' mod='salesbooster'}:</strong> {$metadata.total_products}
                </li>
                <li class="list-group-item">
                    <strong>{l s='Total Orders' mod='salesbooster'}:</strong> {$metadata.total_orders}
                </li>
                <li class="list-group-item">
                    <strong>{l s='Total Quantity Sold' mod='salesbooster'}:</strong> {$metadata.total_quantity}
                </li>
                <li class="list-group-item">
                    <strong>{l s='Analysis Date' mod='salesbooster'}:</strong> {$metadata.analysis_date}
                </li>
            </ul>
        </div>
    </div>

{/block}
