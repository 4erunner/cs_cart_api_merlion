<div id="group_{$group_id}">
{foreach from=$groups_tree item=group}
    {assign var="child_group_id" value="group_`$group.group_id`"}
    {math equation="x*14" x=$groups_level|default:"0" assign="shift"}
    <table id="table_{$child_group_id}" class="table table-tree table-middle">
            <tbody>
            <tr  class="{if $group.level > 0} multiple-table-row {/if}cm-row-status-{$group.status|lower}">
                <td width="50%">
                    {strip}
                        <span style="padding-left: {$shift}px;">
                        {if $group.child}
                            <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$child_group_id}" class="cm-combination" onclick="if (!Tygh.$('#{$child_group_id}').children().get(0)) Tygh.$.ceAjax('request', '{"api_merlion_products.managing_groups?group_id=`$group.group_id`"|fn_url nofilter}', {$ldelim}result_ids: '{$child_group_id}'{$rdelim})"><span class="exicon-expand"> </span></span>
                            <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_{$child_group_id}" class="cm-combination hidden"><span class="exicon-collapse"></span></span>
                        {else}
                        <span class="exicon-"></span>
                        {/if}
                        <span class="row-status">{$group.name}</span>
                        </span>
                    {/strip}
                    
                </td>
                <td width="14%" class="nowrap center">
                    <input type="checkbox" name="{$child_group_id}" {if $group.partnumber_name}checked="checked"{/if} onclick="fn_api_merlion_change_group_partnumber_name(this);">
                </td>
                <td width="14%" class="nowrap center">
                    <input type="checkbox" name="{$child_group_id}" {if $group.list_price}checked="checked"{/if} onclick="fn_api_merlion_change_group_list_price(this);">
                </td>
                <td width="14%" class="nowrap center">
                    <input type="checkbox" name="{$child_group_id}" {if $group.comparison}checked="checked"{/if} onclick="fn_api_merlion_change_group_comparison(this);">
                </td>
                <td width="10%" class="nowrap right">
                    {include file="common/select_popup.tpl" popup_additional_class="dropleft" id=$group.group_id status=$group.status hidden=true object_id_name="group_id" update_controller="api_merlion_products"  table="api_merlion_groups" hidden=false}
                </td>
            </tr>
            </tbody>
    </table>
    <div class="hidden" id="{$child_group_id}">
        <!--{$child_group_id}--></div>
{/foreach}
<!--group_{$group_id}--></div>