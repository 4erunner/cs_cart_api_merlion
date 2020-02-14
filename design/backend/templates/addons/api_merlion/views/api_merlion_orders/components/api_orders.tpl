{math equation="x*14" x=$level|default:"0" assign="shift"}   
    <table class="table table-tree table-middle">
        <thead>
            <tr class="multiple-table-row">
                <th width="15%">
                    <span style="padding-left: {$shift}px;">
                    <span class="exicon-"></span>
                    <span class="row-status">{__("api_merlion_orders.doc_no")}</span>
                    </span>
                </th>
                <th width="15%" class="nowrap left">
                    {__("api_merlion_info.volume")}
                </th>
                <th width="15%" class="nowrap left">
                    {__("api_merlion_orders.total_cost")}
                </th>
                <th width="30%" class="nowrap left">
                    {__("api_merlion_orders.description")}
                </th>
                <th width="15%" class="nowrap left">
                    {__("api_merlion_orders.status")}
                </th>
            </tr>
        </thead>
    </table>
{foreach from=$list item=item}
    {assign var="child_group_id" value="api_order_`$item.document_no`"}
    
    <table id="table_{$child_group_id}" class="table table-tree table-middle">
            <tbody>
            <tr  class="{if $level > 0} multiple-table-row {/if}cm-row-status-{$group.status|lower}">
                <td width="15%">
                    {strip}
                        <span style="padding-left: {$shift}px;">
                        {if $item.Amount > 0}
                            <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$child_group_id}" class="cm-combination" onclick="if (!Tygh.$('#{$child_group_id}').children().get(0)) Tygh.$.ceAjax('request', '{"api_merlion_orders.manage?action=products&order_id=`$item.document_no`"|fn_url nofilter}', {$ldelim} result_ids: '{$child_group_id}'{$rdelim})"><span class="exicon-expand"> </span></span>
                            <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_{$child_group_id}" class="cm-combination hidden"><span class="exicon-collapse"></span></span>
                        {else}
                        <span class="exicon-"></span>
                        {/if}
                        <span class="row-status">{$item.document_no}</span>
                        </span>
                    {/strip}
                    
                </td>
                <td width="15%" class="nowrap left">
                    {$item.Volume}
                </td>
                <td width="15%" class="nowrap left">
                    {$item.AmountRUR}
                </td>
                <td width="30%" class="nowrap left">
                    {$item.PostingDescription}
                </td>
                <td width="15%" class="nowrap left">
                    {$item.Status}
                </td>
            </tr>
            </tbody>
            
    </table>
    <div class="hidden" id="{$child_group_id}">
        <!--{$child_group_id}--></div>
{/foreach}