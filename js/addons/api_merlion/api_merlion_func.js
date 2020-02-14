//
//
(function(_, $){

    $(function(){
        var api_merlion_attach_group, api_merlion_attach_category, api_merlion_attach_action, api_merlion_attach_page = '';        
        
        $(document).ready(function() {
            if($('#apim-button-action').length){
                $('#apim-button-action').hide();
                api_merlion_attach_page = true;
            }
        });

        $.ceEvent('on', 'ce.update_object_status_callback', function(data, param) {
            if(data.update_ids !== undefined){
                $.ceAjax('request', fn_url('api_merlion_products.managing_groups?group_id='+data.update_ids), {
                    result_ids: "group_"+data.update_ids,
                });
            }
        });

        $('.items-container').on('click', 'table',function(){
            if(api_merlion_attach_page){
                try{
                    var select = $(this).attr('id').split('_');
                    fn_api_merlion_element_select(select);
                }catch(e){
                    console.log(e);
                }                
            }
        });
        
        $('.items-container').on('click', 'a.apim-button-path', function(){
            var table = $(this).parents('table');
            try{
                var select = table.attr('id').split('_');
                var path = table.attr('apim-attach-path');
                var last_path = '';
                var scroll = '';
                switch (select[1]) {
                    case 'category':
                        for (var path_value of path.match(/([\W\d\w]{2})/ig)){
                            last_path = last_path+path_value;
                            $('#on_group_'+last_path).click();
                        }
                        $('#table_group_'+last_path).click();
                        scroll = $('#table_group_'+last_path).offset();
                        break;
                    case 'group':
                        for (var path_value of path.split('/')){
                            $('#on_category_'+path_value).click();
                            last_path = path_value;
                        }
                        $('#table_category_'+last_path).click();
                        scroll = $('#table_category_'+last_path).offset();
                        break;
                }
                if(scroll != undefined){
                    $(document).scrollTop(scroll.top-100);
                }
            }catch(e){
                console.log(e);
            }
        });
        
        $('#apim-button-action').on('click', function(){
            if(api_merlion_attach_action){
                var update_group = $('#table_group_'+api_merlion_attach_group).parents('div').attr('id').replace('group_','');
                var update_category = $('#table_category_'+api_merlion_attach_category).parents('div').attr('id').replace('category_','');
                $.ceAjax('request', fn_url('api_merlion_products.attach_groups'), {
                    data:{
                        action_group : api_merlion_attach_group,
                        action_category : api_merlion_attach_category,
                        update_group : update_group,
                        update_category : update_category,
                        action : api_merlion_attach_action,
                        result_ids: "group_"+update_group+","+"category_"+update_category,                        
                    }
                });
                api_merlion_attach_group = '';
                api_merlion_attach_category = '';
                api_merlion_attach_action = '';
                $('#apim-button-action').hide();
            }
        })
        
        $('#api_merlion_counter').on('change', function(){
            $.ceAjax('request', fn_url('api_merlion_settings.change_counter&counter='+$('#api_merlion_counter option:selected').val()), {
                result_ids: "apm_representative",
            });
        });
        
        function fn_api_merlion_element_select(select){
            switch (select[1]) {
                case 'category':
                    if(api_merlion_attach_category && api_merlion_attach_category != select[2]){
                        $('#table_category_'+api_merlion_attach_category).removeClass('apim-status-enable');
                    }
                    api_merlion_attach_category = select[2];
                    $('#table_category_'+api_merlion_attach_category).addClass('apim-status-enable');
                    break;
                case 'group':
                    if(api_merlion_attach_group && api_merlion_attach_group != select[2]){
                        $('#table_group_'+api_merlion_attach_group).removeClass('apim-status-enable');
                    }
                    api_merlion_attach_group = select[2];
                    $('#table_group_'+api_merlion_attach_group).addClass('apim-status-enable');
                    break;
            }
            if($('#table_group_'+api_merlion_attach_group).attr('apim-attach-id') && $('#table_category_'+api_merlion_attach_category).attr('apim-attach-id')){
                fn_api_merlion_set_action_class('apim-button-clear');
                api_merlion_attach_action = 'clear';
                $('#apim-button-action').attr('title',_.tr('api_merlion_products.attach_title_clear'));
                $('#apim-button-action').show();
            }
            else if(!$('#table_group_'+api_merlion_attach_group).attr('apim-attach-id') && api_merlion_attach_group && api_merlion_attach_category){
                fn_api_merlion_set_action_class('apim-button-attach');
                api_merlion_attach_action = 'attach';
                $('#apim-button-action').attr('title',_.tr('api_merlion_products.attach_title_attach'));
                $('#apim-button-action').show();
            }
            else{
                fn_api_merlion_set_action_class('');
                api_merlion_attach_action = '';
                $('#apim-button-action').attr('title','');
                $('#apim-button-action').hide();
            }
        }
        
        function fn_api_merlion_set_action_class(aclass){
            $('#apim-button-action').removeClass('apim-button-attach');
            $('#apim-button-action').removeClass('apim-button-clear');
            $('#apim-button-action').addClass(aclass);
        }
        

        //DEBUG AJAX
        // $.ceEvent('on', 'ce.ajaxdone', function(elms, inline_scripts, params, data, text) {
            // console.log(elms, inline_scripts, params, data, text);
        // });
    });

})(Tygh, Tygh.$);

function fn_api_merlion_change_group_comparison(elem){
    var $ = Tygh.$;
    var group_id = $(elem).attr('name').match(/^[^\_]+\_([a-zа-я0-9]+)$/i)[1];
    if(group_id != undefined){
        $.ceAjax('request', fn_url('api_merlion_products.change_group_comparison'), {
            data:{
                group_id : group_id,
                comparison : $(elem).prop('checked'),
                result_ids: $(elem).attr('name')+",",
            }
        });
    }          
}

function fn_api_merlion_change_group_list_price(elem){
    var $ = Tygh.$;
    var group_id = $(elem).attr('name').match(/^[^\_]+\_([a-zа-я0-9]+)$/i)[1];
    if(group_id != undefined){
        $.ceAjax('request', fn_url('api_merlion_products.change_group_list_price'), {
            data:{
                group_id : group_id,
                list_price : $(elem).prop('checked'),
                result_ids: $(elem).attr('name')+",",
            }
        });
    }          
}

function fn_api_merlion_change_group_partnumber_name(elem){
    var $ = Tygh.$;
    var group_id = $(elem).attr('name').match(/^[^\_]+\_([a-zа-я0-9]+)$/i)[1];
    if(group_id != undefined){
        $.ceAjax('request', fn_url('api_merlion_products.change_group_partnumber_name'), {
            data:{
                group_id : group_id,
                partnumber_name : $(elem).prop('checked'),
                result_ids: $(elem).attr('name')+",",
            }
        });
    }          
}