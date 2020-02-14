<div id="content_api_merlion">
    {if $runtime.controller == 'products' && $runtime.mode == 'update'}
    {include file="buttons/button.tpl" but_text="{__('api_merlion_products.get_current_data_product')}" but_onclick="Tygh.$.ceAjax('request', '{"api_merlion_products.get_product?product_code=`$product_data.product_code`"|fn_url nofilter}', {$ldelim}result_ids: 'api_merlion_about_product'{$rdelim})" allow_href=false but_target_id="api_merlion_about_product"}
    {* include file="buttons/button.tpl" but_text="{__('api_merlion_products.get_status_product')}" but_onclick="Tygh.$.ceAjax('request', '{"api_merlion_products.check_product?product_code=`$product_data.product_code`"|fn_url nofilter}', {$ldelim}result_ids: 'api_merlion_about_product'{$rdelim})" allow_href=false but_target_id="api_merlion_about_product" *}
    <div id="api_merlion_about_product">
    <!--api_merlion_about_product--></div>
    {/if}
</div>
