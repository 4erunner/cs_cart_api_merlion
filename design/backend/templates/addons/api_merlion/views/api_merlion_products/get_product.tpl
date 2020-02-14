<div id="api_merlion_about_product">
    {if $api_merlion_products}
        {foreach from=$api_merlion_products item=product}
        <table class="table table-tree table-middle">
        {foreach from=$product key=product_key item=product_value}
            <tbody>
                <tr>
                    <td width="50%">
                    {if $api_merlion_products_schema[$product_key]}{$api_merlion_products_schema[$product_key]}
                    {else}
                    {$product_key}
                    {/if}
                    </td>
                    <td>
                    {$product_value}
                    </td>
                </tr>
            </tbody>
        {/foreach}
        
        </table>
        {/foreach}
    {else}
        <p class="no-items">{__("api_merlion_errors.no_data")}</p>
    {/if}
<!--api_merlion_about_product--></div>