<div class="api-sect">

<h3>login</h3>

<ul class="api-list-o-things">
    <li>Validate your account, which just means an application could use your Cooper-Hewitt account as a single-sign-on provider (kind of like Facebook Connect).</li>
</ul>

<p>It will not be able to perform any other functions (or call API methods) that require permissions.</p>

</div>

<div class="api-sect">

<h3>read</h3>

<ul class="api-list-o-things">
    <li>Validate your account.</li>
    <li>Access things that you've marked as private (to own account).</li>
</ul>

<p>It will not be able to perform any other functions (or call API methods) that require <q>write</q> permissions.</p>

</div>

<div class="api-sect">

<h3>write</h3>

<ul class="api-list-o-things">
    <li>Validate your account.</li>
    <li>Access things that you've marked as private (to own account).</li>
    <li>Update things that you've marked as private (to own account).</li>
</ul>

</div>

<div class="api-sect">

<p>When you authorize an access token you may also give it a <q>time to live</q> (one hour, one day and so on). By default access tokens <strong>do not</strong> have an expiry date. If you choose to authenticate an application don't forget that you can <a href="{$cfg.abs_root_url}api/oauth2/tokens/">change (or revoke) its permissions at any time</a>.</p>

</div>
