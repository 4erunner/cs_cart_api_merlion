
<style>
span [class*="exicon-"]{ 
    padding: 0 10px 0 10px;
}
</style>
{capture name="mainbox"}
<div class="items-container">
    {if $list}
        {if $action == "manage"}
            {include file="addons/api_merlion/views/api_merlion_orders/components/api_orders.tpl"}
        {elseif $action == "products"}
            {include file="addons/api_merlion/views/api_merlion_orders/components/products.tpl"}
        {elseif $action == "market_orders"}
            {include file="addons/api_merlion/views/api_merlion_orders/components/market_orders.tpl"}
        {/if}
    {else}
        <p class="no-items">{__("no_items")}</p>
    {/if}
</div>
{/capture}
{include file="common/mainbox.tpl" title=__("api_merlion_orders.manage") content=$smarty.capture.mainbox  buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons select_languages=true}
