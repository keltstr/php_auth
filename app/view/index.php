<!DOCTYPE html>
<html> 
	<head>  		
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" >		
		<meta name="robots" content="no-index, no-follow" > 		
		<title>Авторизация</title>		
	</head>
	<body id="<?php v('id'); ?>">
		<form method="post" >			
			<?php echo auth()->create_token(); ?>
			<input type="hidden" name="md5Email" id="md5Email">
			<input type="hidden" name="md5Password" id="md5Password">
		</form>			
		<table class="full_table"><tr><td>
		<div class="auth">
			<h1 id="formHeader">Войти в систему</h1>
			<?php if(isv('logo')):?>
				<div class="logo" title="Система управления сайтом - SamsonCMS"><?php v('logo');?></div>
			<?php endif?>									
			<div class="form">				
				<ul class="table-like-list form-body">
					<li class="tll-classic email-row">
						<label>Введите свой E-mail:</label>
						<div class="input-text"><input type="text" name="Email" id="Email" placeholder="E-mail..."></div>
						<input type="button" name="btnValidateEmail" id="btnValidateEmail" value=Далее class="ie-css3 auth-button">						
					</li>			
					<li class="tll-classic password-row">
						<label>Введите свой пароль:</label>
						<div class="input-text"><input type="password" name="Password" id="Password" placeholder="Пароль..."></div>
						<input type="submit" name="btnLogin" id="btnLogin" value="Войти" class="ie-css3 auth-button">						
					</li>						
					<li class="loader" ><img  src="<?php src('img/ajax_loader_1.gif');?>"></li>								
				</ul>									
			</div>
		</div>
		</td></tr></table>			
	</body>
</html>