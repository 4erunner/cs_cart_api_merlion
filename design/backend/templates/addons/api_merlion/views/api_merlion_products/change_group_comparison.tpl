{script src="js/addons/api_merlion/api_merlion_func.js"}
{capture name="mainbox"}
<div class="items-container">
    {if $groups_tree !== false}
        <table class="table table-tree table-middle">
            <thead>
            <tr>
                <th width="50%">
                    &nbsp;{__("name")}
                </th>
                <th width="14%">
                    &nbsp;{__("api_merlion_products.partnumber_name")} {include file="common/tooltip.tpl" tooltip=__("api_merlion_tooltip.partnumber_name")}
                </th>
                <th width="14%">
                    &nbsp;{__("list_price")} {include file="common/tooltip.tpl" tooltip=__("api_merlion_tooltip.list_price")}
                </th>
                <th width="14%">
                    &nbsp;{__("feature_comparison")} {include file="common/tooltip.tpl" tooltip=__("api_merlion_tooltip.feature_comparison")}
                </th>
                <th width="10%" class="right">{__("status")}</th>
            </tr>
            </thead>
        </table>
        {include file="addons/api_merlion/views/api_merlion_products/components/groups_tree.tpl"}
        
    {else}
        <p class="no-items">{__("no_items")}</p>
    {/if}
</div>

{/capture}
{include file="common/mainbox.tpl" title=__("api_merlion_products.managing_groups") content=$smarty.capture.mainbox  buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons select_languages=true}
