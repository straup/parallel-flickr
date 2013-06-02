<ul id="api-methods-toc">
{foreach from=$methods item="details" key="method_name"}
	<li><a href="#{$method_name|escape}" class="api-method-name{if $details.requires_blessing} api-method-blessed{/if}{if !$details.documented} api-method-undocumented{/if}">{$method_name|escape}</a></li>
{/foreach}
</ul>
