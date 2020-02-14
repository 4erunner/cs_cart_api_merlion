{script src="js/addons/api_merlion/api_merlion_func.js"}
<style>
span [class*="exicon-"]{ 
    padding: 0 10px 0 10px;
}
</style>
{capture name="mainbox"}
<div class="items-container">
    {if $groups_tree !== false}
        <table class="table table-tree table-middle">
            <thead>
            <tr>
                <th width="45%">
                {__("categories")}
                </th>
                <th width="10%">
                </th>
                <th width="45%" >
                {__("api_merlion_products.groups")}
                </th>
            </tr>
            </thead>
        </table>
        <div style="width:45%; float: left">
        {include file="addons/api_merlion/views/api_merlion_products/components/category_tree.tpl"}
        </div>
        <div style="width:10%; float: left;text-align: center">
        {include file="buttons/button.tpl" but_role="action" but_id="apim-button-action"}
        </div>
        <div style="width:45%; float: right">
        {include file="addons/api_merlion/views/api_merlion_products/components/attach_tree.tpl"}
        </div>
       
    {else}
        <p class="no-items">{__("no_items")}</p>
    {/if}
</div>
<script type="text/javascript">
Tygh.tr('api_merlion_products.attach_title_clear',"{__('api_merlion_products.attach_title_clear')}");
Tygh.tr('api_merlion_products.attach_title_attach',"{__('api_merlion_products.attach_title_attach')}");
</script>
{/capture}
{include file="common/mainbox.tpl" title=__("api_merlion_products.attach_groups") content=$smarty.capture.mainbox  buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar adv_buttons=$smarty.capture.adv_buttons select_languages=true}
