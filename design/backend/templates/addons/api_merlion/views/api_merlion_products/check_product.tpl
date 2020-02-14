<div id="api_merlion_about_product">
    {if $api_merlion_products}
        {foreach from=$api_merlion_products item=product}
        <table class="table table-tree table-middle">
            <tbody>
                <tr>
                    <td width="50%">
                    {__('api_merlion_info.s_AvailableClient')}
                    </td>
                    <td>
                    {if $product.AvailableClient > 0}
                        {__("yes")}
                    {else}
                        {__("no")}
                    {/if}
                    </td>
                </tr>
            </tbody>
            {if $product.AvailableClient == 0}
            <tbody>
                <tr>
                    <td width="50%">
                    {__('api_merlion_info.s_DateExpectedNext')}
                    </td>
                    <td>
                    {$product.DateExpectedNext}
                    </td>
                </tr>
            </tbody>
            {/if}
            {if $product.AvailableClient > 0}
            <tbody>
                <tr>
                    <td width="50%">
                    {__('api_merlion_info.s_Online_Reserve')}
                    </td>
                    <td>
                    {if $product.Online_Reserve == 0}
                    {__("yes")}
                    {elseif $product.Online_Reserve == 1}
                    {__("no")}
                    {elseif $product.Online_Reserve == 2}
                    {__("yes")} (<span style="color: red">{__('api_merlion_info.s_Online_Reserve_pay')}</span>)
                    {/if}
                    </td>
                </tr>
            </tbody>
            {/if}
            {if ($product.AvailableClient > 0 && ($product.Online_Reserve == 0 || $product.Online_Reserve == 2)) || $api_merlion_order_row && $api_merlion_order_products.order_id}
                {foreach  from=$api_merlion_order_products.products item=order_product}
                    {if $order_product.product_code == $product.No}
                        {assign var=product_order value=$api_merlion_order_row[$order_product.product_code]}
                        {if $product.AvailableClient >= $order_product.amount && $product_order.status != 'R'}
                            <tbody>
                                <tr>
                                    <td width="50%">
                                        {include file="buttons/button.tpl" but_text="{__('api_merlion_products.set_order_product')}" but_onclick="Tygh.$.ceAjax('request', '{"api_merlion_orders.order_product&product_code=`$api_merlion_product_code`"|fn_url nofilter}', {$ldelim} method: 'POST', result_ids: 'api_merlion_about_product'{$rdelim})" allow_href=false but_target_id="api_merlion_about_product"}
                                    </td>
                                    <td>
                                        {__('api_merlion_products.product_from_order')} <a href="{"orders.details&order_id=`$api_merlion_order_products.order_id`"|fn_url nofilter}" target="_blank">{$api_merlion_order_products.order_id}</a> {__('api_merlion_products.will_be_placed')}
                                    </td>
                                </tr>
                            </tbody>
                        {elseif $product_order.status == 'R'}
                            <tbody>
                                <tr>
                                    <td width="50%">
                                        {include file="buttons/button.tpl" but_text="Remove reserv" but_onclick="Tygh.$.ceAjax('request', '{"api_merlion_orders.un_order_product&product_code=`$api_merlion_product_code`"|fn_url nofilter}', {$ldelim} method: 'POST', result_ids: 'api_merlion_about_product'{$rdelim})" allow_href=false but_target_id="api_merlion_about_product"}
                                    </td>
                                    <td>
                                    {__("api_merlion_products.product_from_order")}: <a href="{"orders.details&order_id=`$product_order.order_id`"|fn_url nofilter}" target="_blank">{$product_order.order_id}</a> - <span style="color: red">{__("api_merlion_orders.already_to_order")}</span>
                                    </td>
                                </tr>
                            </tbody>                            
                        {else}
                            <tbody>
                                <tr>
                                    <td width="50%">
                                        {__("api_merlion_products.no_products_amount")}
                                    </td>
                                    <td>
                                    </td>
                                </tr>
                            </tbody>
                        {/if}
                    {/if}
                {/foreach}
            {/if}
        </table>
        {/foreach}
    {else}
        <p class="no-items">{__("api_merlion_errors.no_data")}</p>
    {/if}
<!--api_merlion_about_product--></div>