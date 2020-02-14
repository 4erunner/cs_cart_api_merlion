
        <tbody>
            <tr>
                <td>{__("api_merlion_settings.representative")}</td>
                <td>
                    <label for="api_merlion_representative" class="cm-required"></label>
                    <div id="apm_representative">
                    <select name="api_merlion_settings[api_merlion_representative]" class="input-medium input-hidden" id="api_merlion_representative" style="border-color:red">
                        <option></option>
                        {foreach from=$api_merlion_settings.api_merlion_representative item=item key=id}
                            <option value="{$item.CounterAgentCode}">{$item.Representative} {$item.StartDate} - {$item.EndDate}</option>
                        {/foreach}
                    </select>
                    <!--apm_representative--></div>
                </td>
            </tr>
        </tbody>
