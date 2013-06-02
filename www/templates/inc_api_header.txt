<div id="api-header">
<h1>{$title|escape}</h1>
<h2>
<a href="{$cfg.abs_root_url}api/">API documentation</a>
 – <a href="{$cfg.abs_root_url}api/methods/">API methods</a>
{if "api_register_keys"|@features_is_enabled}
 – <a href="{$cfg.abs_root_url}api/keys/register/">Create a new API key</a>
 – <a href="{$cfg.abs_root_url}api/keys/">Your API keys</a>
{/if}
{if "api_delegated_auth"|@features_is_enabled} – <a href="{$cfg.abs_root_url}api/oauth2/tokens/">Your access tokens</a>{/if}
</h2>
</div>
