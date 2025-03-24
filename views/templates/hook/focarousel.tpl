{if $selectedProducts|@count > 0}
    <div class="card my-4" style="background-color: #f8f9fa;">
        <div class="card-header">
            <h3 class="h3">{l s='Products on SALE:' mod='SalesBooster'}</h3>
        </div>
        <div class="card-body">
            <div id="salesBoosterCarousel"
                 class="carousel slide"
                 data-interval="5000"
                 data-wrap="false"
                 data-pause="hover"
                 data-touch="true">

                <ol class="carousel-indicators">
                    {foreach $selectedProducts as $key => $product}
                        <li data-target="#salesBoosterCarousel"
                            data-slide-to="{$key}"
                            {if $key == 0}class="active"{/if}>
                        </li>
                    {/foreach}
                </ol>

                <ul class="carousel-inner" role="listbox" aria-label="{l s='Carousel container' d='Shop.Theme.Global'}">
                    {foreach from=$selectedProducts item=product key=key}
                        <li class="carousel-item {if $key == 0}active{/if}" role="option" aria-hidden="{if $key == 0}false{else}true{/if}">
                            <figure>
                                <img src="{$product.image}" alt="{$product.name|escape:'html':'UTF-8'}" loading="lazy">
                                <figcaption class="caption">
                                    <h2>{$product.name}</h2>
                                    <div class="caption-description">
                                        {$product.price}<br>
                                        <a href="{$product.link}" class="btn btn-primary">
                                            {l s='View Product' mod='SalesBooster'}
                                        </a>
                                    </div>
                                </figcaption>
                            </figure>
                        </li>
                    {/foreach}
                </ul>

                <div class="direction" aria-label="{l s='Carousel buttons' d='Shop.Theme.Global'}">
                    <a class="left carousel-control"
                       href="#salesBoosterCarousel"
                       role="button"
                       data-slide="prev"
                       aria-label="{l s='Previous' mod='SalesBooster'}">
          <span class="icon-prev hidden-xs" aria-hidden="true">
            <i class="material-icons">&#xE5CB;</i>
          </span>
                    </a>
                    <a class="right carousel-control"
                       href="#salesBoosterCarousel"
                       role="button"
                       data-slide="next"
                       aria-label="{l s='Next' mod='SalesBooster'}">
          <span class="icon-next" aria-hidden="true">
            <i class="material-icons">&#xE5CC;</i>
          </span>
                    </a>
                </div>
            </div>
        </div>
    </div>
{/if}
