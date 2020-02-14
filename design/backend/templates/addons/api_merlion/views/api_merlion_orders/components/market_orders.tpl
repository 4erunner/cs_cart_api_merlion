<div id="products_{$group_id}">
    {math equation="x*28" x=$level|default:"0" assign="shift"}
    <table class="table table-tree table-middle">
        <thead>
            <tr class="multiple-table-row">
                <th width="15%">
                    <span style="padding-left: {$shift}px;">
                    <span class="exicon-"></span>
                    <span class="row-status">{__("order_id")}</span>
                    </span>
                </th>
                <th width="15%" class="nowrap left">
                    {__("api_merlion_orders.order_date")}
                </th>
                <th width="15%" class="nowrap left">
                    {__("api_merlion_orders.reserve_date")}
                </th>
                <th width="15%" class="nowrap center">
                    {__("api_merlion_orders.quantity")}
                </th>                
            </tr>
        </thead>
    </table>
{foreach from=$list item=item}
    
    <table class="table table-tree table-middle">
            <tbody>
            <tr  class="{if $level > 0} multiple-table-row {/if}">
                <td width="15%">
                    <span style="padding-left: {$shift}px;">
                    <span class="exicon-"></span>
                    <span class="row-status">
                    <a href="{"orders.details&order_id=`$item.order_id`"|fn_url}">{$item.order_id}</a>
                    </span>
                    </span>
                </td>
                <td width="15%" class="nowrap left">
                    {$item.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}
                </td>
                <td width="15%" class="nowrap left">
                    {$item.order_date}
                </td>
                <td width="15%" class="nowrap center">
                    {$item.amount}
                </td>
            </tr>
            </tbody>
    </table>
{/foreach}
<!--products_{$group_id}--></div>