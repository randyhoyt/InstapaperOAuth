<?php

/* InstapaperOAuth
 * ------------------------------------------------------
 * A PHP library for working with Instapaper's OAuth API
 * Randy Hoyt (randy@randyhoyt.com) http://randyhoyt.com
 *
 * This sample application interacts with the Instapaper API using
 * the InstapaperOAuth library. It only uses a small number of the
 * API's features, just enough to demonstrate the very basics of
 * working with it. 
 */

if (!class_exists("OAuthConsumer") && !class_exists("InstapaperOAuth"))
    require_once ("InstapaperOAuth/InstapaperOAuth.php");


/** 1. Create a new InstapaperOAuth object using the consumer token.
 **    The Instapaper API allows developers access to data in Instapaper.
 **    You will need to get an OAuth consumer token from Instapaper before
 **    you can access the API.
 **
 **        Request Access: http://www.instapaper.com/main/request_oauth_consumer_token
 ** 
 **    After your request is granted, you will receive an OAuth consumer key
 **    and an OAuth consumer secret. Enter those values here:
 */ 

$consumer_key = ""; // <-------------------------------------------------------------------------------------- You must configure this value for the sample application to work.
$consumer_secret = ""; // <----------------------------------------------------------------------------------- You must configure this value for the sample application to work.
$instapaper = new InstapaperOAuth($consumer_key,$consumer_secret);


/** 2. The Instapaper API uses xAuth. You ask your users for a username and password,
 *     and Instapaper will give you back a token you can use to make future requests.
 *     You would normally ask your users for this information when you first connect
 *     to their account, but for this sample application you should simply enter a
 *     username and password here: 
 */

$x_auth_username = ""; // <----------------------------------------------------------------------------------- You must configure this value for the sample application to work.
$x_auth_password = ""; // <----------------------------------------------------------------------------------- You must configure this value for the sample application to work.


/** 3. Pass this username and password to Instapaper; Instapaper will pass back a token
 **    you can use for future requests to that user's account. In a real application,
 **    you would need to save these two pieces of data so that you could use them for
 **    future requests. You need to perform this step once for each user whose
 **    Instapaper's account you want to access. 
 ** */

$token = $instapaper->get_access_token($x_auth_username,$x_auth_password);
$oauth_token = $token["oauth_token"]; 
$oauth_token_secret = $token["oauth_token_secret"];


/** 4. Once you have that information, use it to re-create the InstapaperOAuth object. */
$instapaper = new InstapaperOAuth($consumer_key,$consumer_secret,$oauth_token,$oauth_token_secret);


/** 5. You now have everything you need to request now make requests against the Instapaper API
 **     to access this particular user's account.
 ** */

// verify that the user has an active subscription
$result = ($instapaper->verify_credentials());
$user = $result[0];
echo '<h1>' . $user->username . '</h1>';
if ($user->subscription_is_active!=1)
	die('You must have an Instapaper subscription to use the features of the Instapaper API required by this application.');

// get a list of the user-created folders
$folders = $instapaper->list_folders();

if ($folders) { // if the user has folders, retrieve a list of the last 25 items the user filed in the first folder returned

	$folder_title = $folders[0]->title;
	$bookmarks = $instapaper->list_bookmarks(25,$folders[0]->folder_id);
 
} else {        // if the user does not have folders, display a list of the last 25 unread bookmarks

	$folder_title = "Unread";
	$bookmarks = $instapaper->list_bookmarks(25,'unread');
}

echo '<h2>Folder: ' . $folder_title . '</h2>';
foreach($bookmarks as $bookmark) {
	if ($bookmark->type=="bookmark") {
		echo '<li><a href="' . $bookmark->url . '">' . $bookmark->title . '</a></li>'; 	
	}
}
echo '<ul>';

?>