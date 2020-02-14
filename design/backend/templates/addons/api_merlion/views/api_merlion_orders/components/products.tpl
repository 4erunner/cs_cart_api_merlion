<div id="api_order_{$group_id}">
    {math equation="x*14" x=$level|default:"0" assign="shift"}
    <table class="table table-tree table-middle">
        <thead>
            <tr class="multiple-table-row">
                <th width="15%">
                    <span style="padding-left: {$shift}px;">
                    <span class="exicon-"></span>
                    <span class="row-status">{__("api_merlion_info.no")}</span>
                    </span>
                </th>
                <th width="45%" class="nowrap left">
                    {__("api_merlion_info.name")}
                </th>
                <th width="15%" class="nowrap center">
                    {__("api_merlion_orders.quantity")}
                </th>
                <th width="15%" class="nowrap left">
                    {__("api_merlion_orders.total_cost")}
                </th>
                <th width="15%" >
                    {__("api_merlion_orders.status")}
                </th>
            </tr>
        </thead>
    </table>
{foreach from=$list item=item}
    {assign var="child_group_id" value="products_`$item.item_no`"}
    <table id="table_{$child_group_id}" class="table table-tree table-middle">
            <tbody>
            <tr  class="{if $level > 0} multiple-table-row {/if}">
                <td width="15%">
                    {strip}
                        <span style="padding-left: {$shift}px;">
                        {if $item.status}
                            <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$child_group_id}" class="cm-combination" onclick="if (!Tygh.$('#{$child_group_id}').children().get(0)) Tygh.$.ceAjax('request', '{"api_merlion_orders.manage?action=market_orders&order_id=`$item.document_no`&product_code=`$item.item_no`"|fn_url nofilter}', {$ldelim} result_ids: '{$child_group_id}'{$rdelim})"><span class="exicon-expand"> </span></span>
                            <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_{$child_group_id}" class="cm-combination hidden"><span class="exicon-collapse"></span></span>
                        {else}
                        <span class="exicon-"></span>
                        {/if}
                        <span class="row-status">{$item.item_no}</span>
                        </span>
                    {/strip}
                    
                </td>
                <td width="45%" class="left">
                    <a href="{"products.update&product_id=`$item.product_id`"|fn_url}">{$item.name}</a>
                </td>
                <td width="15%" class="nowrap center">
                    {$item.qty}
                </td>
                <td width="15%" class="nowrap left">
                    {$item.amount} RUR
                </td>
                <td width="15%">
                {if $item.status}
                    {if $item.status == 'R'}
                        <div class="apim-button-in-order" style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_product_reserved')}"></i>
                    {elseif $item.status == 'P'}
                        <div class="apim-button-in-order-warning" style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_product_reserved')} {__('api_merlion_tooltip.order_status_warning')}"></i>
                    {/if}
                    {if $item.status == 'W'}
                        <div class="apim-button-status-warning"  style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_status_warning')}"></i>
                    {/if}
                    {if $item.status == 'E'}
                        <div class="apim-button-status-no"  style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-warning-sign"></i>
                    {/if}
                    {if $item.status == 'A'}
                        <div class="apim-button-status-ok" style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_status_ok')}"></i>
                    {/if}
                    {if $item.status == 'N'}
                        <div class="apim-button-status-no"  style="display:inline-flex"></div>
                        <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_status_no')}"></i>
                    {/if}
                {else}
                    <div class="apim-button-status-test"  style="display:inline-flex"></div>
                {/if}
                </td>
            </tr>
            </tbody>
    </table>
    <div class="hidden" id="{$child_group_id}">
        <!--{$child_group_id}--></div>
{/foreach}
<!--api_order_{$group_id}--></div>