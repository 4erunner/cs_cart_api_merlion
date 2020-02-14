    <div id="api_merlion_order_products">
            <table width="100%" class="table table-middle">
            <thead>
                <tr>
                    <th width="50%">{__("product")}</th>
                    <th width="10%">{__("price")}</th>
                    <th class="center" width="10%">{__("quantity")}</th>
                    <th class="center" width="10%">{__("api_merlion_orders.product_in_reserve")}</th>
                    <th width="10%" class="right">&nbsp;{__("subtotal")}</th>
                    <th width="10%" class="right">&nbsp;{__('api_merlion_orders.status')}</th>
                    <th width="10%" class="right">&nbsp;{__('api_merlion_orders.action')}</th>
                </tr>
            </thead>
            {foreach from=$order_info.products item="oi" key="key"}
            {assign var=apim_product_order_status value=$api_merlion_order_products_status[$oi.product_id]}
            {hook name="orders:items_list_row"}
            {if !$oi.extra.parent}
            <tr>
                <td>
                    <div class="order-product-image">
                        {include file="common/image.tpl" image=$oi.main_pair.icon|default:$oi.main_pair.detailed image_id=$oi.main_pair.image_id image_width=50 href="products.update?product_id=`$oi.product_id`"|fn_url}
                    </div>
                    <div class="order-product-info">
                        {if !$oi.deleted_product}<a href="{"products.update?product_id=`$oi.product_id`"|fn_url}">{/if}{$oi.product nofilter}{if !$oi.deleted_product}</a>{/if}
                        <div class="products-hint">
                        {hook name="orders:product_info"}
                            {if $oi.product_code}<p>{__("sku")}:{$oi.product_code}</p>{/if}
                        {/hook}
                        </div>
                        {if $oi.product_options}<div class="options-info">{include file="common/options_info.tpl" product_options=$oi.product_options}</div>{/if}
                    </div>
                </td>
                <td class="nowrap">
                    {if $oi.extra.exclude_from_calculate}{__("free")}{else}{include file="common/price.tpl" value=$oi.original_price}{/if}           
                    </td>
                <td class="center">
                    &nbsp;{$oi.amount}<br />
                    {if !"ULTIMATE:FREE"|fn_allowed_for && $use_shipments && $oi.shipped_amount > 0}
                        &nbsp;<span class="muted"><small>({$oi.shipped_amount}&nbsp;{__("shipped")})</small></span>
                    {/if}
                </td>
                <td class="center">{$apim_product_order_status.amount}</td>
                <td class="right">&nbsp;<span>{if $oi.extra.exclude_from_calculate}{__("free")}{else}{include file="common/price.tpl" value=$oi.display_subtotal}{/if}</span></td>
                <td width="10%" class="right">
                {if $apim_product_order_status.status}
                    {if $apim_product_order_status.status == 'R'}
                        <div class="apim-button-in-order" style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_product_reserved')}"></i>
                    {elseif $apim_product_order_status.status == 'P'}
                        <div class="apim-button-in-order-warning" style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_product_reserved')} {__('api_merlion_tooltip.order_status_warning')}"></i>
                    {/if}
                    {if $apim_product_order_status.status == 'W'}
                        <div class="apim-button-status-warning"  style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_status_warning')}"></i>
                    {/if}
                    {if $apim_product_order_status.status == 'E'}
                        <div class="apim-button-status-no"  style="display:inline-flex"></div>
                        {if $apim_product_order_status.message}<i class="cm-tooltip icon-warning-sign" title="{$apim_product_order_status.message}"></i>{/if}
                    {/if}
                    {if $apim_product_order_status.status == 'A'}
                        <div class="apim-button-status-ok" style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_status_ok')}"></i>
                    {/if}
                    {if $apim_product_order_status.status == 'N'}
                        <div class="apim-button-status-no"  style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_status_no')}"></i>
                    {/if}
                {else}
                    <div class="apim-button-status-test"  style="display:inline-flex"></div>
                {/if}
                </td>
                <td width="10%" class="right">
                {if $apim_product_order_status}
                    {if $apim_product_order_status.status == 'R' || $apim_product_order_status.status == 'P'}
                        {include file="buttons/button.tpl" but_role="action" but_meta="apim-button-del-from-order" but_onclick="Tygh.$.ceAjax('request', '{"api_merlion_orders.order_remove_product?order_id=`$order_info.order_id`&product_id=`$oi.product_id`"|fn_url nofilter}', {$ldelim}result_ids: 'api_merlion_order_products'{$rdelim})" allow_href=false but_target_id="api_merlion_order_products"}<i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_unreserve_product')}"></i>
                    {/if}
                    {if $apim_product_order_status.status == 'E'}
                        {include file="buttons/button.tpl" but_role="action" but_meta="apim-button-status-test" but_onclick="Tygh.$.ceAjax('request', '{"api_merlion_orders.order_check_products?order_id=`$order_info.order_id`"|fn_url nofilter}', {$ldelim}result_ids: 'api_merlion_order_products'{$rdelim})" allow_href=false but_target_id="api_merlion_order_products"}
                    {/if}
                    {if $apim_product_order_status.status == 'A' || $apim_product_order_status.status == 'W'}
                        {include file="buttons/button.tpl" but_role="action" but_meta="apim-button-add-to-order" but_onclick="Tygh.$.ceAjax('request', '{"api_merlion_orders.order_add_product?order_id=`$order_info.order_id`&product_id=`$oi.product_id`"|fn_url nofilter}', {$ldelim}result_ids: 'api_merlion_order_products'{$rdelim})" allow_href=false but_target_id="api_merlion_order_products"}<i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_reserve_product')}"></i>
                    {/if}
                {/if}
                </td>
            </tr>
            {if $apim_product_order_status.order_price >= $oi.original_price || $oi.amount > $apim_product_order_status.order_available}
            <tr>
                <td>{__("message")}:</td>
                <td colspan="5" class="center" style="color:red">
                {if $apim_product_order_status.order_price >= $oi.original_price}
                    {__('api_merlion_orders.supplier_price_higher')}&nbsp;
                {/if}
                {if $oi.amount > $apim_product_order_status.order_available}
                    &nbsp;{__('api_merlion_orders.supplier_product_less')}
                {/if}
                </td>
            </tr>
            {/if}
            {/if}
            {/hook}
            {/foreach}
            </table>
    <!--api_merlion_order_products--></div>  