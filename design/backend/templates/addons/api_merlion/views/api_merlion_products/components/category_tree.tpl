<div id="category_{$category_id}">
{foreach from=$categories_tree item=category}
    {assign var="child_category_id" value="category_`$category.category_id`"}
    {math equation="x*14" x=$categories_level|default:"0" assign="shift"}
    <table id="table_{$child_category_id}" class="table table-tree table-middle {if $category.group_id}apim-status-attached{/if}" {if $category.group_id}apim-attach-path="{$category.group_id}" apim-attach-id="{$category.group_id}"{/if}>
            <tbody>
            <tr  class="multiple-table-row cm-row-status-{$category.status|lower}">
                <td width="90%">
                    {strip}
                        <span style="padding-left: {$shift}px;">
                        {if $category.child}
                            <span alt="{__("expand_sublist_of_items")}" title="{__("expand_sublist_of_items")}" id="on_{$child_category_id}" class="cm-combination" onclick="if (!Tygh.$('#{$child_category_id}').children().get(0)) Tygh.$.ceAjax('request', '{"api_merlion_products.attach_groups?category_id=`$category.category_id`&id_path=`$category.id_path`"|fn_url nofilter}', {$ldelim}result_ids: '{$child_category_id}'{$rdelim})"><span class="exicon-expand"> </span></span>
                            <span alt="{__("collapse_sublist_of_items")}" title="{__("collapse_sublist_of_items")}" id="off_{$child_category_id}" class="cm-combination hidden"><span class="exicon-collapse"></span></span>
                        {else}
                        <span class="exicon-"></span>
                        {/if}
                        <span class="row-status">{$category.category}</span>
                        </span>
                    {/strip}
                    
                </td>
                <td width="10%" class="nowrap right">
                {if $category.group_id}{include file="buttons/button.tpl" but_role="action" but_meta="apim-button-path"}{/if}
                </td>
            </tr>
            </tbody>
    </table>
    <div class="hidden" id="{$child_category_id}">
        <!--{$child_category_id}--></div>
{/foreach}
<!--category_{$category_id}--></div>