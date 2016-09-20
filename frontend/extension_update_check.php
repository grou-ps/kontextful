<?php
header ("Content-Type:text/xml");

if($_GET['browser']=="chrome") {
	echo <<<EOS
<?xml version='1.0' encoding='UTF-8'?>
<gupdate xmlns='http://www.google.com/update2/response' protocol='2.0'>
	<app appid='peohcfmmoaimcpahpjedfapokklpbene'>
		<updatecheck codebase='http://kontextful.com/extensions/chrome/0.1.2/kontextful.crx' version='0.1.2' />
	</app>
</gupdate>
EOS;

}