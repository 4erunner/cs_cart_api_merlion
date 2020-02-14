{script src="js/lib/bootstrap_switch/js/bootstrapSwitch.js"}
{style src="lib/bootstrap_switch/stylesheets/bootstrapSwitch.css"}

{hook name="data_feeds:notice"}
{notes title=__("notice")}
    <p><b>{__("api_merlion_tooltip.cron_hint")} :</b></p>
    <p><b>{__('api_merlion_import_items')}</b> <i class="cm-tooltip icon-question-sign" title="php /path/to/cart/{''|fn_url:'A':'rel'} --dispatch=api_merlion_products.m_import --p --api_merlion_import=items --api_merlion_import_items=(local_items|categories_items) [--api_merlion_import_force=1] [--api_merlion_import_items_quantity=1] --cron_password={$current_api_merlion_settings.api_merlion_import_cron_password}"></i>
    </p>
    <p><b> {__('api_merlion_import_features')}</b> <i class="cm-tooltip icon-question-sign" title="php /path/to/cart/{''|fn_url:'A':'rel'} --dispatch=api_merlion_products.m_import  --p --api_merlion_import=features --api_merlion_import_features=(local_items|period_items --api_merlion_import_features_period= Tygh\Tools\DateTimeHelper::PERIOD_) --cron_password={$current_api_merlion_settings.api_merlion_import_cron_password}"></i>
    </p>
    <p><b>{__('api_merlion_import_images')}</b> <i class="cm-tooltip icon-question-sign" title="php /path/to/cart/{''|fn_url:'A':'rel'}  --dispatch=api_merlion_products.m_import --p --api_merlion_import=images --api_merlion_import_images=(local_items|period_items --api_merlion_import_images_period= Tygh\Tools\DateTimeHelper::PERIOD_) --cron_password={$current_api_merlion_settings.api_merlion_import_cron_password}"></i>
    </p>
{/notes}
{/hook}

{capture name="mainbox"}
{if $api_merlion_settings}
    <table width="100%" class="table table-middle cr-table">
        <thead>
            <th>
                <td colspan="5"><strong>{__("api_merlion_products.about_update_values")}</strong></td>
            </th>
        </thead>        
        <tbody>
            <tr>
                <td width="35%">{__("api_merlion_products.items_update")} <i class="cm-tooltip icon-question-sign" title="{__("api_merlion_products.process_update")}{__("api_merlion_notice.process_update")}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_import_items_update" value="1" {if $current_api_merlion_settings.api_merlion_import_items_update == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
                <td  width="16%">
                    {$current_api_merlion_settings.api_merlion_last_items_update}
                </td>
                <td  width="2%">
                    >>
                </td>
                <td  width="16%">
                    {$current_api_merlion_settings.api_merlion_last_items_update_stop}
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td width="35%">{__("api_merlion_products.features_update")} <i class="cm-tooltip icon-question-sign" title="{__("api_merlion_products.process_update")}{__("api_merlion_notice.process_update")}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_import_features_update" value="1" {if $current_api_merlion_settings.api_merlion_import_features_update == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
                <td  width="16%">
                    {$current_api_merlion_settings.api_merlion_last_features_update}
                </td>
                <td  width="2%">
                    >>
                </td>
                <td  width="16%">
                    {$current_api_merlion_settings.api_merlion_last_features_update_stop}
                </td>
            </tr> 
        </tbody>
        <tbody>
            <tr>
                <td width="35%">{__("api_merlion_products.images_update")} <i class="cm-tooltip icon-question-sign" title="{__("api_merlion_products.process_update")}{__("api_merlion_notice.process_update")}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_import_images_update" value="1" {if $current_api_merlion_settings.api_merlion_import_images_update == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
                <td  width="16%">
                    {$current_api_merlion_settings.api_merlion_last_images_update}
                </td>
                <td  width="2%">
                    >>
                </td>
                <td  width="16%">
                    {$current_api_merlion_settings.api_merlion_last_images_update_stop}
                </td>
            </tr>            
        </tbody>
    </table>
    <table  width="100%" class="table table-middle cr-table">
        <thead>
            <th>
                <td colspan="2"><strong>{__("api_merlion_products.about_operation_values")}</strong></td>
            </th>
        </thead> 
        <tbody>
            <tr>
                <td width="35%">{__("api_merlion_products.delete_file")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.delete_file')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_delete_import_file" value="1" {if $current_api_merlion_settings.api_merlion_delete_import_file == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>            
        </tbody>
        <tbody>
            <tr>
                <td width="35%">{__("api_merlion_products.import_offline")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.import_offline')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_import_offline" value="1" {if $current_api_merlion_settings.api_merlion_import_offline == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>            
        </tbody>
    </table>    
{literal}
<script type="text/javascript">
(function (_, $) {
    $(_.doc).on('switch-change', '.cm-switch-change', function (e, data) {
        var value = data.value, elem = $(data['el']);
        if(data.value){
            elem.val("1");
        }
        else{
            elem.val("0");
        }
        console.log(elem);
        $.ceAjax('request', fn_url("api_merlion_settings.u_update"), {
            method: 'post',
            data: {
                name: data.el.prop('name'),
                value: value ? 1 : 0
            }
        });
    });
}(Tygh, Tygh.$));
</script>
{/literal}
{/if}
<form action="{""|fn_url}" method="post" name="api_merlion_products_import" class="form-horizontal form-edit">
<input type="hidden" name="skip_result_ids_check" value="1" />

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

{if $api_merlion_settings}
    <table width="100%" class="table table-middle cr-table">
        <tbody>
           <tr>
                <td colspan=2>
                {__('choose_category')} <i class="cm-tooltip icon-question-sign" title="{__("api_merlion_tooltip.products_choose_category")}"></i>
                </td>
                <td colspan=2>
                    <div class="control-group" id="product_categories">
                        {math equation="rand()" assign="rnd"}
                        {if $smarty.request.category_id}
                            {assign var="request_category_id" value=","|explode:$smarty.request.category_id}
                        {else}
                            {assign var="request_category_id" value=""}
                        {/if}
                        <label for="ccategories_{$rnd}_ids" class="control-label" style="display:none">{__("categories")}</label>
                        <div class="controls" style="margin-left:0">
                            {include file="pickers/categories/picker.tpl" company_ids=$api_merlion_import_company_id rnd=$rnd data_id="categories" but_text="{__('choose_category')}" input_name="api_merlion_import_category_ids" radio_input_name="api_merlion_import_category_id" hide_link=true hide_delete_button=true display_input_id="category_ids" disable_no_item_text=true view_mode="list" but_meta="btn" show_active_path=true}
                        </div>
                    <!--product_categories--></div>
                </td>
            </tr> 
        </tbody>
        <tbody>
            <tr>
                <td rowspan="3" width="5%">
                    <input name="api_merlion_import" type="radio" value="items">
                </td>
                <td rowspan="3"  width="30%">
                    {__('api_merlion_import_items')}
                </td>
                <td  width="5%">
                    <input name="api_merlion_import_items" type="radio" value="local_items"  checked="checked">
                </td>
                <td width="30%">
                    {__('api_merlion_for_downloaded')}
                </td>
            </tr>
            <tr>
                <td  width="5%">
                    <input name="api_merlion_import_items" type="radio" value="categories_items">
                </td>
                <td width="30%">
                    {__('api_merlion_all_categories')}
                </td>
            </tr>
            <tr>
                <td  width="5%">
                    <input name="api_merlion_import_items_quantity" type="checkbox" value="1" checked="checked">
                </td>
                <td width="30%">
                    {__('api_merlion_products.items_quantity')}
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td colspan="2">
                    {include file="common/subheader.tpl" title="{__('api_merlion_additional_fields')} {__('api_merlion_import_items')}" target="#import_options_items"}
                </td>
                <td colspan="2">
                   {assign var="pattern" value=$patterns_products.products}
                    <div id="import_options_items" class="collapsed collapse">
                    <table>
                    {foreach from=$pattern.export_fields key="field_name" item="field_value"}
                        
                        {if !$field_value.required}
                            <tr>
                                <td><label>{$field_name}</label></td>
                                <td><input name="api_merlion_import_extra[items][{$field_name}]" type="text" value="{if !empty($api_merlion_import_items_extra.$field_name)}{$api_merlion_import_items_extra.$field_name}{/if}"></td>
                            <tr>
                        {/if}
                        
                    {/foreach}
                    </table>
                    </div>
                </td>
            </tr>
        </tbody>        
        <tbody>
            <tr>
                <td rowspan="2" width="5%">
                    <input name="api_merlion_import" type="radio" value="features">
                </td>
                <td rowspan="2"  width="30%">
                    {__('api_merlion_import_features')}
                </td>
                <td  width="5%">
                    <input name="api_merlion_import_features" type="radio" value="local_items"  checked="checked">
                </td>
                <td width="30%">
                    {__('api_merlion_for_downloaded')}
                </td>
            </tr>
            <tr>
                <td  width="5%">
                    <input name="api_merlion_import_features" type="radio" value="period_items">
                </td>
                <td width="30%">
                    {include file="common/period_selector.tpl" form_name="api_merlion_products_import" prefix="api_merlion_import_features_"}
                </td>
            </tr>
        </tbody>
        {if false}
        <tbody>
            <tr>
                <td colspan="2">
                    {include file="common/subheader.tpl" title="{__('api_merlion_additional_fields')} {__('api_merlion_import_features')}" target="#import_options_features"}
                </td>
                <td colspan="3">
                    {assign var="pattern" value=$patterns_features.features}
                    <div id="import_options_features" class="collapsed collapse">
                    <table>
                    {foreach from=$pattern.export_fields key="field_name" item="field_value"}
                        
                        {if !$field_value.required}
                            <tr>
                                <td><label>{$field_name}</label></td>
                                <td><input name="api_merlion_import_extra[features][{$field_name}]" type="text" value="{if !empty($api_merlion_import_features_extra.$field_name)}{$api_merlion_import_features_extra.$field_name}{/if}"></td>
                            <tr>
                        {/if}
                        
                    {/foreach}
                    </table>
                    </div>
                </td>
            </tr>
        </tbody>
        {/if}
        <tbody>
            <tr>
                <td rowspan="3" width="5%">
                    <input name="api_merlion_import" type="radio" value="images">
                </td>
                <td rowspan="3"  width="30%">
                    {__('api_merlion_import_images')}
                </td>
                <td  width="5%">
                    <input name="api_merlion_import_images" type="radio" value="local_items"  checked="checked">
                </td>
                <td width="30%">
                    {__('api_merlion_for_downloaded')}
                </td>
            </tr>
            <tr>
                <td  width="5%">
                    <input name="api_merlion_import_images" type="radio" value="period_items">
                </td>
                <td width="30%">
                    {include file="common/period_selector.tpl" form_name="api_merlion_products_import" prefix="api_merlion_import_images_"}
                </td>
            </tr>
            <tr>
                <td  width="5%">
                    <input name="api_merlion_import_images_force" type="checkbox" value="1">
                </td>
                <td width="30%">
                    {__('api_merlion_products.images_force')}
                </td>
            </tr>
        </tbody>
        {if false}
        <tbody>
            <tr>
                <td  colspan="2">
                    {include file="common/subheader.tpl" title="{__('api_merlion_additional_fields')} {__('api_merlion_import_images')}" target="#import_options_images"}
                </td>
                <td colspan="3">
                    {assign var="pattern" value=$patterns_products.product_images}
                    <div id="import_options_images" class="collapsed collapse">
                    <table>
                    {foreach from=$pattern.export_fields key="field_name" item="field_value"}
                        
                        {if !$field_value.required}
                            <tr>
                                <td><label>{$field_name}</label></td>
                                <td><input name="api_merlion_import_extra[images][{$field_name}]" type="text" value="{if !empty($api_merlion_import_images_extra.$field_name)}{$api_merlion_import_images_extra.$field_name}{/if}"></td>
                            <tr>
                        {/if}
                        
                    {/foreach}
                    </table>
                    </div>
                </td>
            </tr>
        </tbody>
        {/if}        
   </table>
{else}
    <p class="no-items">{ __('api_merlion_errors.error_connection')} {__("no_data")}</p>
{/if}

{capture name="buttons"}
    {if $api_merlion_settings}
        {include file="buttons/button.tpl" but_role="submit-link" but_text=__("api_merlion_products.import_button") but_name="dispatch[api_merlion_products.m_import]" but_role="submit-link" but_target_form="api_merlion_products_import" but_meta=" cm-post cm-comet cm-ajax"}
    {/if}
{/capture}

<div class="clearfix">
    {include file="common/pagination.tpl" div_id=$smarty.request.content_id}
</div>

</form>
{/capture}

{include file="common/mainbox.tpl" title=__("api_merlion_products.import") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar content_id="call_request"}