{block name="content"}

    <div class="panel">
        <div class="panel-heading">
            <h2 class="mb-3">{l s='Sales Analysis' mod='salesbooster'}</h2>
            <p class="mb-0">{$opinion}</p>
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
                    {l s='1. Sync shop items with backend' mod='salesbooster'}
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
                <h3 class="mb-3">{l s='Select Date Range' mod='salesbooster'}</h3>
            </div>
            <form method="post" action="{$currentUrl|escape:'html':'UTF-8'}" class="mb-4">
                <div class="form-group">
                    <label for="start_date">{l s='Start Date' mod='salesbooster'}</label>
                    <input type="date" name="start_date" id="start_date" class="form-control"
                           value="{$start_date|escape:'html':'UTF-8'}" required>
                </div>

                <div class="form-group mt-3">
                    <label for="end_date">{l s='End Date' mod='salesbooster'}</label>
                    <input type="date" name="end_date" id="end_date" class="form-control"
                           value="{$end_date|escape:'html':'UTF-8'}" required>
                </div>

                <button type="submit" name="submitSalesAnalysis" class="btn btn-primary mt-4">
                    {l s='Analyze Sales' mod='salesbooster'}
                </button>
            </form>
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-body">
            <div class="panel-heading">
                <h3 class="mb-3">{l s='Product Trends' mod='salesbooster'}</h3>
            </div>
            <form method="post" action="{$currentUrl|escape:'html':'UTF-8'}">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr class="bg-primary text-white">
                            <th scope="col">{l s='Select' mod='salesbooster'}</th>
                            <th scope="col">{l s='Product ID' mod='salesbooster'}</th>
                            <th scope="col">{l s='Product Name' mod='salesbooster'}</th>
                            <th scope="col">{l s='Trend Status' mod='salesbooster'}</th>
                            <th scope="col">{l s='Change (%)' mod='salesbooster'}</th>
                            <th scope="col">{l s='Key Dates' mod='salesbooster'}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach from=$products item=product}
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_products[]" value="{$product.product_id}"
                                           {if isset($selectedProducts) && in_array($product.product_id, $selectedProducts)}checked="checked"{/if}>
                                </td>
                                <td>{$product.product_id}</td>
                                <td>{$product.product_name}</td>
                                <td>{$product.trend_status}</td>
                                <td>{$product.percentage_change}%</td>
                                <td>{implode(', ', $product.key_dates)}</td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
                <button type="submit" name="submitSelectedProducts" class="btn btn-warning mt-3">
                    {l s='Promote Selected Products' mod='salesbooster'}
                </button>
            </form>
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
