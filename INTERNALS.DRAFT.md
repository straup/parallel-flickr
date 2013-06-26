# privatesquare internals notes

More an aide memoire than anything else; this is to help me remember what URL maps to what source file, what source file uses what Smarty template and what Smarty template is included by another Smarty template. If you're interested in what's going on in the guts of the code, read on.

## URL to source file mappings


*  `/` (maps to) `index.php`
*  `/signout` (maps to) `signout.php`
*  `/checkcookie` (maps to) `checkcookie.php`
*  `/auth` (maps to) `auth_callback_foursquare_oauth.php`
*  `/signin` (maps to) `signin_foursquare_oauth.php`
*  `/forgot` (maps to) `forgot.php`
*  `/reset` (maps to) `reset.php`
*  `/account` (maps to) `account.php`
*  `/account/foursquare/sync` (maps to) `account_foursquare_sync.php`
*  `/me` (maps to) `me.php`
*  `/venue/xxx` (maps to) `venue.php`
*  `/user/xxx/checkin/xxx` (maps to) `user_checkin.php`
*  `/user/xxx/places/pagexxx` (maps to) `user_places.php`
*  `/user/xxx/places/xxx/pagexxx` (maps to) `user_place.php`
*  `/user/xxx/history/nearby` (maps to) `user_history_nearby.php`
*  `/user/xxx/history/export` (maps to) `user_history_export.php`
*  `/user/xxx/history/pagexxx` (maps to) `user_history.php`
*  `/user/xxx/history/xxx/pagexxx` (maps to) `user_history.php`

## template file inclusion, by source file

In general this is characterised as `template file` = `page_sourcefile.txt` but there's sometimes multiple or conditional inclusions. As you do.

*  `index.php` includes ...
    *  `page_index_loggedout.txt`
    *  `page_index.txt`
*  `signout.php` includes ...
    *  `page_signout_done.txt`
    *  `page_signout.txt`
*  `checkcookie.php` includes ...
    *  `page_checkcookie.txt`
*  `auth_callback_foursquare_oauth.php` includes ...
    *  `page_signin_disabled.txt`
    *  `page_auth_callback_foursquare_oauth.txt`
    *  `page_signup_disabled.txt`
*  `signin_foursquare_oauth.php` includes ...
    *  `page_signin_disabled.txt`
*  `forgot.php` includes ...
    *  `page_forgot_sent.txt`
    *  `page_forgot.txt`
*  `reset.php` includes ...
    *  `page_reset.txt`
*  `account.php` includes ...
    *  `page_account.txt`
*  `account_foursquare_sync.php` includes ...
    *  `page_account_foursquare_sync.txt`
*  `venue.php` includes ...
    *  `page_venue.txt`
*  `user_checkin.php` includes ...
    *  `page_user_checkin.txt`
*  `user_places.php` includes ...
    *  `page_user_places.txt`
*  `user_place.php` includes ...
    *  `page_user_place.txt`
*  `user_history_nearby.php` includes ...
    *  `page_user_history_nearby.txt`
*  `user_history_export.php` includes ...
    *  `page_user_history_export.txt`
*  `user_history.php` includes ...
    *  `page_user_history.txt`

## template file inclusion, by template file

Smarty templates include other Smarty template. Who'd have thought?

*  `page_index_loggedout.txt`
*  `page_index.txt`
*  `page_signout_done.txt`
*  `page_signout.txt`
*  `page_checkcookie.txt`
*  `page_signin_disabled.txt`
*  `page_auth_callback_foursquare_oauth.txt`
*  `page_signup_disabled.txt`
*  `page_signin_disabled.txt`  
*  `page_forgot_sent.txt`
*  `page_forgot.txt`
*  `page_reset.txt` 
*  `page_account.txt`
*  `page_account_foursquare_sync.txt`
*  `page_venue.txt`
*  `page_user_checkin.txt`
*  `page_user_places.txt` 
*  `page_user_place.txt` 
*  `page_user_history_nearby.txt`
*  `page_user_history_export.txt`
*  `page_user_history.txt` include ...
    * `inc_head.txt`
    * `inc_foot.txt`
	
*  `inc_head.txt`
	* links to nearby, history, places, export, logout, sign in