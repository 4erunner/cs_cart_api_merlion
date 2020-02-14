{script src="js/addons/api_merlion/api_merlion_func.js"}
{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="manage_api_merlion_settings_form" class="form-horizontal form-edit cm-ajax">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

{if $api_merlion_settings}
   <table width="100%" class="table table-middle cr-table">
    <thead>
        <tr>
            <th width="30%">
                {__("api_merlion_settings.handbook")}
            <th width="70%">
                {__("api_merlion_settings.values")}
            </th>
        </tr>
    </thead>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.counter")}</td>
                <td>
                    <label for="api_merlion_counter" class="cm-required"></label>
                    <select name="api_merlion_settings[api_merlion_counter]" class="input-medium input-hidden" id="api_merlion_counter">
                        <option></option>
                        {foreach from=$api_merlion_settings.counter item=item key=id}
                            <option value="{$item.Code}"{if $item.Code == $current_api_merlion_settings.api_merlion_counter} selected="selected"{/if}>{$item.Description}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        </tbody>
 
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.representative")}</td>
                <td>
<div id="apm_representative">
                    {if $current_api_merlion_settings.api_merlion_counter}
                    <label for="api_merlion_representative"></label>
                    <select name="api_merlion_settings[api_merlion_representative]" class="input-medium input-hidden" id="api_merlion_representative">
                        <option></option>
                        {foreach from=$api_merlion_settings.api_merlion_representative item=item key=id}
                            <option value="{$item.CounterAgentCode}"{if $item.CounterAgentCode == $current_api_merlion_settings.api_merlion_representative} selected="selected"{/if}>{$item.Representative} {$item.StartDate} - {$item.EndDate}</option>
                        {/foreach}
                    </select>
                    {/if}
<!--apm_representative--></div>
                </td>
            </tr>
        
        </tbody>

        <tbody>
            <tr>
                <td>{__("api_merlion_settings.shipment_agent")}</td>
                <td>
                    <label for="api_merlion_shipment_agent" class="cm-required"></label>
                    <select name="api_merlion_settings[api_merlion_shipment_agent]" class="input-medium input-hidden" id="api_merlion_shipment_agent">
                        <option></option>
                        {foreach from=$api_merlion_settings.shipment_agent item=item key=id}
                            <option value="{$item.Code}"{if $item.Code == $current_api_merlion_settings.api_merlion_shipment_agent} selected="selected"{/if}>{$item.Description}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.shipment_method")}</td>
                <td>
                    <label for="api_merlion_shipment_method" class="cm-required"></label>
                    <select name="api_merlion_settings[api_merlion_shipment_method]" class="input-medium input-hidden" id="api_merlion_shipment_method">
                        <option></option>
                        {foreach from=$api_merlion_settings.shipment_method item=item key=id}
                            <option value="{$item.Code}"{if $item.Code == $current_api_merlion_settings.api_merlion_shipment_method or $item.isDefault == 1 } selected="selected"{/if}>{$item.Description}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.endpoint_delivery_id")}</td>
                <td>
                    <label for="api_merlion_endpoint_delivery_id" class="cm-required"></label>
                    <select name="api_merlion_settings[api_merlion_endpoint_delivery_id]" class="input-medium input-hidden" id="api_merlion_endpoint_delivery_id">
                        <option></option>
                        {foreach from=$api_merlion_settings.api_merlion_endpoint_delivery_id item=item key=id}
                            <option value="{$item.ID}"{if $item.ID == $current_api_merlion_settings.api_merlion_endpoint_delivery_id} selected="selected"{/if}>{$item.Endpoint_address} | {$item.Endpoint_contact} | {$item.City} | {$item.ShippingAgentCode}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.packing_type")}</td>
                <td>
                    <label for="api_merlion_packing_type" class="cm-required"></label>
                    <select name="api_merlion_settings[api_merlion_packing_type]" class="input-medium input-hidden" id="api_merlion_packing_type">
                        <option></option>
                        {foreach from=$api_merlion_settings.api_merlion_packing_type item=item key=id}
                            <option value="{$item.Code}"{if $item.Code == $current_api_merlion_settings.api_merlion_packing_type} selected="selected"{/if}>{$item.Description}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        </tbody>
   </table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{capture name="buttons"}
    {if $api_merlion_settings}
        {include file="buttons/save.tpl" but_name="dispatch[api_merlion_settings.m_update]" but_role="submit-link" but_target_form="manage_api_merlion_settings_form"}
    {/if}
{/capture}

<div class="clearfix">
    {include file="common/pagination.tpl" div_id=$smarty.request.content_id}
</div>

</form>
{/capture}

{include file="common/mainbox.tpl" title=__("api_merlion_settings.manage_handbooks") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar content_id="call_request"}