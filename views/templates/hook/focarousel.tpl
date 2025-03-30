{if isset($salesbooster_products) && $salesbooster_products}
    <section class="crossselling-products-salesbooster block">
        {if isset($salesbooster_title) && $salesbooster_title}
            <h2 class="h2 products-section-title text-uppercase">
                {$salesbooster_title}
            </h2>
        {/if}

        <div class="products products-carousel-salesbooster js-salesbooster-carousel row"> {* 'row' class might be helpful depending on theme/CSS framework *}
            {foreach from=$salesbooster_products item="product"}
             <div class="item product-item"> {* Or maybe just <div class="item"> *}
                    {include file='catalog/_partials/miniatures/product.tpl' product=$product}
                </div>
            {/foreach}
        </div>
    </section>
{/if}