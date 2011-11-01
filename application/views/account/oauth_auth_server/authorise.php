<!doctype html>	
<!--[if lt IE 7 ]><html class="no-js ie6"><![endif]-->
<!--[if IE 7 ]><html class="no-js ie7"><![endif]-->
<!--[if IE 8 ]><html class="no-js ie8"><![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html class="no-js"><!--<![endif]-->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

	<title>Secure Sign-in</title>

</head>

<body lang="en">

	<h1>Secure Sign-in</h1>
							
	
	<h2>The application "<?php echo $client_name; ?>" wants to connect to your account</h2>
			
	<p>If you click approve you will be redirected back to the application and it will be able to securely access your information and perform actions on your behalf.</p>
	
	<p>If you click deny then you will be redirected back to the application and there will be no exchange of data. You are free to approve this application again at a later date.</p>
				
	<?php echo form_open('oauth/authorise'); ?>
		<p>
			<input type="submit" class="button" value="Approve" name="doauth" /> or
			<input type="submit" class="button" value="Deny" name="doauth" />
		</p>
	<?php echo form_close(); ?>
	
</body>
</html>