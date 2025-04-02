{block name="content"}

    {if !empty($opinion) || !empty($modulemessage)}
    <div class="panel">
        <div class="panel-body">
        <div class="panel-heading">
            <h2 class="mb-3">{l s='Sales Analysis' mod='salesbooster'} at {$metadata.analysis_date}</h2>
        </div>
            <div class="mt-3">
            <h4 class="mb-0 text-success">
                {$opinion}
                {$modulemessage}
            </h4>
        </div>
        </div>
    </div>
    {/if}

    <div class="panel mt-4">

        <div class="panel-body">

            <div class="panel-heading">
                <h3 class="mb-3">{l s='Data Synchronisation' mod='salesbooster'}</h3>
                <div class="mt-3">
                    <p class="mb-0">{$resultofsync}</p>
                    <p class="mb-0">{$action_message nofilter}</p>
                </div>
            </div>
            <form method="post" action="{$currentUrl|escape:'html':'UTF-8'}">
                <button type="submit" name="submitActionSendToExternal"
                        class="btn btn-success btn-lg font-weight-bold px-4 py-2 mr-2">
                    {l s='Synchronise products and sales with salesbooster module' mod='salesbooster'}
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
                {if !empty($metadata.analysis_date)}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr class="bg-primary text-white">
                            <th scope="col">{l s='Select' mod='salesbooster'}</th>
                            <th scope="col">{l s='Apply Discount %' mod='salesbooster'}</th>
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
                                <td>
                                <input type="number"
                                       name="discounts[{$product.product_id}]"
                                       value="{$savedDiscounts.{$product.product_id}|default:'%'}"
                                       min="0"
                                       max="100"
                                       step="1"
                                       placeholder="%">
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
                {else}
                    <p>Since you haven't analysed sales, you can only disable the Carousel In Cart Page</p>
                    <button type="submit" name="submitSelectedProducts" class="btn btn-warning mt-3">
                        {l s='Disable Carousel' mod='salesbooster'}
                    </button>
                {/if}
            </form>
        </div>
    </div>

    <div class="panel mt-4">
        <div class="panel-body">
            <div class="panel-heading">
                <h3 class="mb-3">{l s='Metadata' mod='salesbooster'}</h3>
            </div>
            {if !empty($metadata.analysis_date)}
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
            {else}
                <p>Analyse sales first to see metadata</p>
            {/if}
        </div>
    </div>

{/block}
