{script src="js/lib/bootstrap_switch/js/bootstrapSwitch.js"}
{style src="lib/bootstrap_switch/stylesheets/bootstrapSwitch.css"}
{capture name="mainbox"}

<form action="{""|fn_url}" method="post" name="manage_api_merlion_settings_form" class="form-horizontal form-edit cm-ajax">

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

{if $current_api_merlion_settings}
   <table width="100%" class="table table-middle cr-table">
        <thead>
            <tr>
                <th width="30%">
                <th width="70%">
                    {__("api_merlion_settings.values")}
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.order_id")}</td>
                <td>
                    <input name="api_merlion_settings[api_merlion_order_id]" value="{$current_api_merlion_settings.api_merlion_order_id}"  class="input-medium"/>
                    <span>
                {include file="buttons/button.tpl" but_text="{__('api_merlion_settings.create_order')}" but_onclick="Tygh.$.ceAjax('request', '{"api_merlion_orders.get_or_create_order"|fn_url nofilter}', {$ldelim} method: 'POST', callback: function(obj){$ldelim}if(obj.new_order != undefined){$ldelim}$('input[name=\"api_merlion_settings[api_merlion_order_id]\"]').val(obj.new_order);{$rdelim}{$rdelim}{$rdelim})" allow_href=false}
                </span>
                    <span>
                {include file="buttons/button.tpl" but_text="{__('api_merlion_settings.delete_order')}" but_onclick="Tygh.$.ceAjax('request', '{"api_merlion_orders.delete_order"|fn_url nofilter}', {$ldelim} method: 'POST', callback: function(obj){$ldelim}if(obj.new_order != undefined){$ldelim}$('input[name=\"api_merlion_settings[api_merlion_order_id]\"]').val(obj.new_order);{$rdelim}{$rdelim}{$rdelim})" allow_href=false}
                </span>
                </td>

            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.order_auto_create")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_auto_create')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_order_auto_create" value="1" {if $current_api_merlion_settings.api_merlion_order_auto_create == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.order_note")}  <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.order_note')}"></i></td>
                <td>
                    <input name="api_merlion_settings[api_merlion_order_note]" value="{$current_api_merlion_settings.api_merlion_order_note}"  class="input-medium"/>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("language_code")}</td>
                <td>
                    <select name="api_merlion_settings[api_merlion_product_language]">
                        {foreach from=$languages item="language"}
                        <option {if $cart_languages == $language.lang_code and not $current_api_merlion_settings.api_merlion_product_language}selected{elseif $current_api_merlion_settings.api_merlion_product_language == $language.lang_code}selected{/if} value="{$language.lang_code}">{$language.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
        </tbody>
        <thead>
            <tr>
                <th width="30%">
                <th width="70%">
                    YML_export
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.yml_export_enable")}</td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_yml_export_enable" value="1" {if $current_api_merlion_settings.api_merlion_yml_export_enable == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.yml_export")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.yml_export')}"></i></td>
                <td>
                    <label for="api_merlion_yml_export_code" class="cm-regexp"></label><input id="api_merlion_yml_export_code" name="api_merlion_settings[api_merlion_yml_export_code]" value="{$current_api_merlion_settings.api_merlion_yml_export_code}"  class="input-medium "/>
                </td>
            </tr>
        </tbody>
        <thead>
            <tr>
                <th width="30%">
                <th width="70%">
                    {__("import")} {__("api_merlion_menu")}
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.product_package")}</td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_product_package" value="1" {if $current_api_merlion_settings.api_merlion_product_package == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.product_available")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.product_available')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_product_available" value="1" {if $current_api_merlion_settings.api_merlion_product_available == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_products.delete_file")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.delete_file')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_delete_import_file" value="1" {if $current_api_merlion_settings.api_merlion_delete_import_file == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>            
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_products.import_offline")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.import_offline')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_import_offline" value="1" {if $current_api_merlion_settings.api_merlion_import_offline == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>            
        </tbody>
        <thead>
            <tr>
                <th width="30%">
                <th width="70%">
                    {__("filters")}
                </th>
            </tr>
        </thead>

        <tbody>
            <tr>
                <td>{__("api_merlion_settings.products_filters_create")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.products_filters_create')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_products_filters_create" value="1" {if $current_api_merlion_settings.api_merlion_products_filters_create == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.products_filters_bind")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.products_filters_bind')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_products_filters_bind" value="1" {if $current_api_merlion_settings.api_merlion_products_filters_bind == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.products_filters_enable")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.products_filters_enable')}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_products_filters_enable" value="1" {if $current_api_merlion_settings.api_merlion_products_filters_enable == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        <thead>
            <tr>
                <th width="30%">
                <th width="70%">
                    {__("others")}
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.sort_features")}</td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_sort_features" value="1" {if $current_api_merlion_settings.api_merlion_sort_features == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("logging")}  <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.logging')} {$current_api_merlion_settings.api_merlion_logging_dir}"></i></td>
                <td>
                    <div class="switch switch-mini cm-switch-change list-btns">
                        <input type="checkbox" name="api_merlion_logging" value="1" {if $current_api_merlion_settings.api_merlion_logging == "Y"}checked="checked"{/if}/>
                    </div>
                </td>
            </tr>
        </tbody>
        <tbody>
        <tr>
            <td>{__("api_merlion_settings.debug")} </td>
            <td>
                <div class="switch switch-mini cm-switch-change list-btns">
                    <input type="checkbox" name="api_merlion_debug" value="1" {if $current_api_merlion_settings.api_merlion_debug == "Y"}checked="checked"{/if}/>
                </div>
            </td>
        </tr>
        </tbody>
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.products_filters_delete")} <i class="cm-tooltip icon-question-sign" title="{__('api_merlion_tooltip.products_filters_delete')}"></i></td>
                <td>
                    {include file="buttons/button.tpl" but_text={__("api_merlion_settings.products_filters_delete")} but_onclick="if(confirm(Tygh.tr('api_merlion_settings.products_filters_delete'))){$ldelim}Tygh.$.ceAjax('request', '{"api_merlion_products.del_filters"|fn_url nofilter}', {$ldelim}{$rdelim}){$rdelim}" allow_href=false but_target_id="api_merlion_about_product"}
                </td>
            </tr>
        </tbody>
   </table>
{literal}
<script type="text/javascript">
Tygh.$.ceFormValidator('setRegexp', {
        'api_merlion_yml_export_code': {
        /*regexp: /^https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)$/ig,*/
        regexp: /^([a-z0-9]{6,}|)$/ig,
        message: "wrong activate code"
        }
    }
);
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
<script type="text/javascript">
Tygh.tr('api_merlion_settings.products_filters_delete','{__("api_merlion_settings.products_filters_delete")}');
</script>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

{capture name="buttons"}
    {if $current_api_merlion_settings}
        {include file="buttons/save.tpl" but_name="dispatch[api_merlion_settings.m_update]" but_role="submit-link" but_target_form="manage_api_merlion_settings_form"}
    {/if}
{/capture}

<div class="clearfix">
    {include file="common/pagination.tpl" div_id=$smarty.request.content_id}
</div>

</form>
{/capture}

{include file="common/mainbox.tpl" title=__("api_merlion_settings.manage_values") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar content_id="call_request"}