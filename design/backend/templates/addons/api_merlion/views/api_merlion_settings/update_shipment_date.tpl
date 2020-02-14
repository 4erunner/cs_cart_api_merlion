{hook name="data_feeds:notice"}
{notes title=__("notice")}
    <p><b>{__("api_merlion_tooltip.cron_hint")}</b>:<br />
        <span>php /path/to/cart/{""|fn_url:"A":"rel"} --dispatch=api_merlion_settings.update_shipment_date --cron_password={$current_api_merlion_settings.api_merlion_import_cron_password}</span>
    </p>
{/notes}
{/hook}

{capture name="mainbox"}

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

<p class="no-items">{__("no_data")}</p>

<div class="clearfix">
    {include file="common/pagination.tpl" div_id=$smarty.request.content_id}
</div>

{/capture}

{include file="common/mainbox.tpl" title=__("api_merlion_settings.update_shipment_date") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar content_id="call_request"}