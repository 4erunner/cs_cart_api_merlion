<div id="group_{$group_id}">
{foreach from=$groups_tree item=group}
    {assign var="child_group_id" value="group_`$group.group_id`"}
    {math equation="x*14" x=$groups_level|default:"0" assign="shift"}
    <table id="table_{$child_group_id}" class="table table-tree table-middle {if $group.category_id and $group.id_path}apim-status-attached{/if}" {if $group.category_id and $group.id_path}apim-attach-path="{$group.id_path}" apim-attach-id="{$group.category_id}"{/if}>
            <tbody>
            <tr  class="multiple-table-row cm-row-status-{$group.status|lower}">
                <td width="90%">
                    {strip}
                        <span style="padding-left: {$shift}px;">
                        {if $group.child}
                            <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$child_group_id}" class="cm-combination" onclick="if (!Tygh.$('#{$child_group_id}').children().get(0)) Tygh.$.ceAjax('request', '{"api_merlion_products.attach_groups?group_id=`$group.group_id`"|fn_url nofilter}', {$ldelim}result_ids: '{$child_group_id}'{$rdelim})"><span class="exicon-expand"> </span></span>
                            <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_{$child_group_id}" class="cm-combination hidden"><span class="exicon-collapse"></span></span>
                        {else}
                        <span class="exicon-"></span>
                        {/if}
                        <span class="row-status">{$group.name}</span>
                        </span>
                    {/strip}
                    
                </td>
                <td width="10%" class="nowrap right">
                {if $group.category_id and $group.id_path}{include file="buttons/button.tpl" but_role="action" but_meta="apim-button-path"}{/if}
                </td>
            </tr>
            </tbody>
    </table>
    <div class="hidden" id="{$child_group_id}">
        <!--{$child_group_id}--></div>
{/foreach}
<!--group_{$group_id}--></div>