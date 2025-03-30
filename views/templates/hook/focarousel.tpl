{if $selectedProducts|@count > 0}
<link rel="stylesheet" href="{$module_dir}views/css/focarousel.css" type="text/css" media="all">
<div class="card my-4" style="background-color: #f8f9fa;">
    <div class="card-header">
        <h3 class="h3">{l s='Products on SALE:' mod='SalesBooster'}</h3>
    </div>
    <div class="card-body pb-5">
        <div id="salesBoosterCarousel"
             class="carousel slide"
             data-interval="5000"
             data-wrap="false"
             data-pause="hover"
             data-touch="true">

            {assign var="totalProducts" value=$selectedProducts|@count}
            {assign var="totalSlides" value=ceil($totalProducts/4)}

            <ol class="carousel-indicators">
                {for $slideIndex=0 to $totalSlides-1}
                    <li data-target="#salesBoosterCarousel"
                        data-slide-to="{$slideIndex}"
                        {if $slideIndex == 0}class="active"{/if}>
                    </li>
                {/for}
            </ol>

            <div class="carousel-inner px-5" role="listbox" aria-label="{l s='Carousel container' d='Shop.Theme.Global'}">
                {for $slideIndex=0 to $totalSlides-1}
                <div class="carousel-item {if $slideIndex == 0}active{/if}" role="option" aria-hidden="{if $slideIndex == 0}false{else}true{/if}">
                    {assign var="startIndex" value=$slideIndex*4}
                    {assign var="endIndex" value=min(($slideIndex+1)*4-1, $totalProducts-1)}
                    {assign var="itemCount" value=$endIndex-$startIndex+1}

                    {if $itemCount < 4}
                    <div class="row justify-content-center">
                        {else}
                        <div class="row">
                            {/if}

                            {for $i=$startIndex to $endIndex}
                                <div class="col-md-3 mb-4">
                                    <div class="card product-card">
                                        <div class="card-img-container">
                                            <img class="card-img-top" src="{$selectedProducts[$i].image}" alt="{$selectedProducts[$i].name|escape:'html':'UTF-8'}" loading="lazy">
                                            {if isset($savedDiscounts[$selectedProducts[$i].id_product])}
                                                <div class="discount-badge">
                                                    -{$savedDiscounts[$selectedProducts[$i].id_product]}%
                                                </div>
                                            {/if}
                                        </div>
                                        <div class="card-body d-flex flex-column">
                                            <h5 class="card-title">{$selectedProducts[$i].name}</h5>
                                            <div class="mt-auto">
                                                <div class="price-container">
                                                    <p class="card-text price">{$selectedProducts[$i].price}</p>
                                                    {if isset($savedDiscounts[$selectedProducts[$i].id_product])}
                                                        <p class="card-text original-price">
                                                            {assign var="discountPercent" value=$savedDiscounts[$selectedProducts[$i].id_product]}
                                                            {assign var="currentPrice" value=str_replace(['€', ' '], ['', ''], $selectedProducts[$i].price)}
                                                            {assign var="originalPrice" value=$currentPrice / (1 - ($discountPercent/100))}
                                                            €{$originalPrice|string_format:"%.2f"}
                                                        </p>
                                                    {/if}
                                                </div>
                                                <a href="{$selectedProducts[$i].link}" class="btn btn-primary mt-2 view-product-btn">
                                                    {l s='VIEW PRODUCT' mod='SalesBooster'}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            {/for}
                        </div>
                    </div>
                    {/for}
                </div>

                <a class="carousel-control-prev"
                   href="#salesBoosterCarousel"
                   role="button"
                   data-slide="prev"
                   aria-label="{l s='Previous' mod='SalesBooster'}">
                    <span class="carousel-control-prev-icon" aria-hidden="true">
                        <i class="material-icons">&#xE5CB;</i>
                    </span>
                </a>
                <a class="carousel-control-next"
                   href="#salesBoosterCarousel"
                   role="button"
                   data-slide="next"
                   aria-label="{l s='Next' mod='SalesBooster'}">
                    <span class="carousel-control-next-icon" aria-hidden="true">
                        <i class="material-icons">&#xE5CC;</i>
                    </span>
                </a>
            </div>
        </div>
    </div>
<div class="card-footer">
{/if}
