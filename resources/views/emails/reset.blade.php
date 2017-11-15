<!DOCTYPE html>
<html><head>
</head><body style="width:100%">
	<h1>RPG Companion</h1>
	<p>Reset your password</p>
	<div>
		<!--[if mso]>
		<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{url('api/reset-password?t=' . $token)}}" style="height:40px;v-text-anchor:middle;width:200px;" arcsize="10%" strokecolor="#1e3650" fill="t">
		<v:fill type="tile" src="http://i.imgur.com/0xPEf.gif" color="#556270" />
		<w:anchorlock/>
		<center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">Reset Password</center>
		</v:roundrect>
		<![endif]-->
		<a href="{{url('api/reset-password?t=' . $token)}}"
			style="background-color:#556270;background-image:url(http://i.imgur.com/0xPEf.gif);border:1px solid #1e3650;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:200px;-webkit-text-size-adjust:none;mso-hide:all;">
			Reset Password
		</a>
	</div>
	<p>If the above button doesn't work please visit:
		<br>
		<a href="{{url('api/reset-password?t=' . $token)}}">{{url('api/reset-password?t=' . $token)}}</a>
	</p>
	<p>Thank you for using RPG Companion.</p>
</body></html>
