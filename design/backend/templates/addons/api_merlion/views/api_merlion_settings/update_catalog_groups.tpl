{capture name="mainbox"}

{include file="common/pagination.tpl" save_current_page=true save_current_url=true div_id=$smarty.request.content_id}

<p class="no-items">{__("no_data")}</p>

<div class="clearfix">
    {include file="common/pagination.tpl" div_id=$smarty.request.content_id}
</div>

{/capture}

{include file="common/mainbox.tpl" title=__("api_merlion_settings.update_catalog_groups") content=$smarty.capture.mainbox buttons=$smarty.capture.buttons sidebar=$smarty.capture.sidebar content_id="call_request"}