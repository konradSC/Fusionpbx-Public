<?php

//includes
	require_once "root.php";
	require_once "resources/require.php";
	require_once "resources/check_auth.php";

//check permissions	
	if (permission_exists('webphone_view')) {
		//access granted
	}
	else {
		echo "access denied";
		exit;
	}

//add multi-lingual support
	$language = new text;
	$text = $language->get();


	if (is_uuid($_GET['id'])) {
		$extension_uuid = $_GET['id'];
	}
	
//get the user ID
	$sql = "SELECT extension, password,effective_caller_id_name ";
	$sql .= "FROM v_extensions ";
	$sql .= "WHERE extension_uuid = '" . $extension_uuid . "' ";
	$sql .= "AND v_extensions.domain_uuid = '" . $_SESSION["domain_uuid"] . "' LIMIT 1";
	
	$prep_statement = $db->prepare($sql);
	if ($prep_statement) {
		$prep_statement->execute();
		$row = $prep_statement->fetch(PDO::FETCH_ASSOC);
		$user_extension = $row['extension'];
		$user_password = $row['password'];
		$effective_caller_id_name = $row['effective_caller_id_name'];
	}
	
echo "<html lang='en'>\n";
echo "<head>\n";
echo "    <meta charset='utf-8' />\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>\n";
echo "    <title>ctxSip</title>\n";
echo "    <link rel='icon' type='image/gif' href='img/favicon.ico' />\n";
echo "    <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css'>\n";
echo "    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css'>\n";
echo "    <link href='css/ctxSip.css' rel='stylesheet' type='text/css' />\n";
echo "</head>\n";
echo "<body id='sipClient'>\n";
echo "<div class='container-fluid'>\n";

echo "    <div class='clearfix sipStatus'>\n";
echo "        <div id='txtCallStatus' class='pull-right'>&nbsp;</div>\n";
echo "        <div id='txtRegStatus'></div>\n";
echo "    </div>\n";

echo "    <div class='form-group' id='phoneUI'>\n";
echo "        <div class='input-group'>\n";
echo "            <div class='input-group-btn'>\n";
echo "                <button class='btn btn-sm btn-primary dropdown-toggle' data-toggle='dropdown' title='Show Keypad'>\n";
echo "                    <i class='fa fa-th'></i>\n";
echo "                </button>\n";
echo "                <div id='sip-dialpad' class='dropdown-menu'>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='1'>1<span>&nbsp;</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='2'>2<span>ABC</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='3'>3<span>DEF</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='4'>4<span>GHI</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='5'>5<span>JKL</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='6'>6<span>MNO</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='7'>7<span>PQRS</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='8'>8<span>TUV</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='9'>9<span>WXYZ</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='*'>*<span>&nbsp;</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='0'>0<span>+</span></button>\n";
echo "                    <button type='button' class='btn btn-default digit' data-digit='#'>#<span>&nbsp;</span></button>\n";
echo "                    <div class='clearfix'>&nbsp;</div>\n";
echo "                    <button class='btn btn-success btn-block btnCall' title='Send'>\n";
echo "                        <i class='fa fa-play'></i> Send\n";
echo "                    </button>\n";
echo "                </div>\n";
echo "            </div>\n";
echo "            <input type='text' name='number' id='numDisplay' class='form-control text-center input-sm' value='' placeholder='Enter number...' autocomplete='off' />\n";
echo "            <div class='input-group-btn input-group-btn-sm'>\n";
echo "                <button class='btn btn-sm btn-primary dropdown-toggle' id='btnVol' data-toggle='dropdown' title='Volume'>\n";
echo "                    <i class='fa fa-fw fa-volume-up'></i>\n";
echo "                </button>\n";
echo "                <div class='dropdown-menu dropdown-menu-right'>\n";
echo "                    <input type='range' min='0' max='100' value='100' step='1' id='sldVolume' />\n";
echo "                </div>\n";
echo "            </div>\n";
echo "        </div>\n";
echo "    </div>\n";

echo "   <div class=well-sip'>\n";
echo "       <div id='sip-splash' class='text-muted text-center panel panel-default'>\n";
echo "           <div class='panel-body'>\n";
echo "                <h3 class='page-header'>\n";
echo "                <span class='fa-stack fa-2x'>\n";
echo "                    <i class='fa fa-circle fa-stack-2x text-success'></i>\n";
echo "                    <i class='fa fa-phone fa-stack-1x fa-inverse'></i>\n";
echo "                </span><br>\n";
echo "                This is your phone.</h3>\n";
echo "                <p class='lead'>To make a call enter a number in the box above.</p>\n";
echo "                <small>Closing this window will cause calls to go to voicemail.</small>\n";
echo "            </div>\n";
echo "        </div>\n";

echo "        <div id='sip-log' class='panel panel-default hide'>\n";
echo "            <div class='panel-heading'>\n";
echo "                <h4 class='text-muted panel-title'>Recent Calls <span class='pull-right'><i class='fa fa-trash text-muted sipLogClear' title='Clear Log'></i></span></h4>\n";
echo "            </div>\n";
echo "            <div id='sip-logitems' class='list-group'>\n";
echo "                <p class='text-muted text-center'>No recent calls from this browser.</p>\n";
echo "            </div>\n";
echo "        </div>\n";
echo "    </div>\n";

echo "    <div class='modal fade' id='mdlError' tabindex='-1' role='dialog' aria-hidden='true' data-backdrop='static' data-keyboard='false'>\n";
echo "        <div class='modal-dialog modal-sm'>\n";
echo "            <div class='modal-content'>\n";
echo "                <div class='modal-header'>\n";
echo "                    <h4 class='modal-title'>Sip Error</h4>\n";
echo "                </div>\n";
echo "                <div class='modal-body text-center text-danger'>\n";
echo "                    <h3><i class='fa fa-3x fa-ban'></i></h3>\n";
echo "                    <p class='lead'>Sip registration failed. No calls can be handled.</p>\n";
echo "                </div>\n";
echo "            </div>\n";
echo "        </div>\n";
echo "    </div>\n";

echo "</div>\n";

echo "<audio id='ringtone' src='sounds/incoming.mp3' loop></audio>\n";
echo "<audio id='ringbacktone' src='sounds/outgoing.mp3' loop></audio>\n";
echo "<audio id='dtmfTone' src='sounds/dtmf.mp3'></audio>\n";
echo "<audio id='audioRemote'></audio>\n";

echo "<script type='text/javascript' src='https://code.jquery.com/jquery-1.11.3.min.js'></script>\n";
echo "<script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js'></script>\n";
echo "<script type='text/javascript' src='scripts/moment.js/moment.min.js'></script>\n";

//echo "<script type='text/javascript' src='scripts/SIP.js/sip.min.js'></script>\n";
echo "<script type='text/javascript' src='scripts/SIP.js/sip.js'></script>\n";
//echo "<script type='text/javascript' src='scripts/config.js'></script>\n";

echo "<script type='text/javascript'>\n";
echo 	"var user = {'User' : '" . $user_extension. "', ";
echo    " 'Pass' : '".$user_password."', ";
echo    " 'Realm' : '".$_SESSION["domain_name"]."', ";
echo    " 'Display' : '".$effective_caller_id_name."', ";
echo    " 'WSServer'  : 'wss://".$_SESSION["domain_name"].":7443' ";
echo "};\n";
echo "</script>\n";

echo "<script type='text/javascript' src='scripts/app.js'></script>\n";

echo "</body>\n";
//</html>
?>