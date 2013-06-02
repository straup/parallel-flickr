<div class="api-sect">

<p>The application named <strong>{$key.app_title|escape}</strong> is requesting acccess to your account with <strong>{$str_perms|strtoupper|escape}</strong> permissions. <strong>{$str_perms|strtoupper|escape}</strong> permissions means that this API key will be able to:</p>

{if $str_perms=='login'}

<ul class="list-o-things">
    <li>Validate your account.</li>
</ul>

<p>It will not be able to perform any other functions (or call API methods) that require permissions.</p>

{elseif $str_perms=='read'}

<ul class="api-list-o-things">
    <li>Validate your account.</li>
    <li>Access things that you've marked as private (to your own account).</li>
</ul>

<p>It will not be able to perform any other functions (or call API methods) that require <q>write</q> permissions.</p>

{elseif $str_perms=='write'}

<ul class="api-list-o-things">
    <li>Validate your account.</li>
    <li>Access things that you've marked as private (to your own account).</li>
    <li>Update things that you've marked as private (to your own account).</li>
</ul>

{else}
<p class="error">You should not be seeing this. If you are then something is very wrong!</p>
{/if}

<p>If you choose to authenticate this application don't forget that you can <a href="{$cfg.abs_root_url}api/oauth2/tokens/">revoke those permissions at any time</a>.</p>

</div>
