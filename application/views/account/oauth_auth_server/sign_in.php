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
			
	<h2>Please enter your username and password to continue</h2>
				
	<?php echo form_open('oauth/sign_in'); ?>
	
		<?php if($error): ?>
		
			<div class="error">	
				<strong>Please correct the following errors</strong>
				<ul>
				<?php foreach($error_messages as $e): ?>
					<li><?php echo $e; ?></li>
				<?php endforeach; ?>
				</ul>
			</div>
		
		<?php endif; ?>
	
		
		<p>
			<label for="username">Your username</label><br>
			<input type="text" id="username" name="username" placeholder="demo">
		</p>
		
		<p>
			<label for="password">Your password</label><br>
			<input type="password" id="password" name="password" placeholder="demotest">
		</p>
		
		<p>
			<input type="submit" name="validate_user" value="Sign-in">
		</p>
	
	<?php echo form_close(); ?>
	
</body>
</html>