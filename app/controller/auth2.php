<?php
// TODO: Включить механизм аторизации на сайте через cookie
// TODO: Добавить галочку запомнить меня в стандартной форме авторизации

/**
 * Контроллер для авторизации на сайте
 */
function auth2()
{	
	// Установим шаблон
	s()->template('app/view/index.php');
		
	// Установим представление
	m()->title('Авторизация');
}

/**
 * Ассинхронній контроллер для проверки указанного Email
 */
function auth2_email()
{		
	// Ассинхронынй вывод без шаблона
	s()->async(TRUE);		
		
	// Выполним проверку email пользователя
	echo auth()->verify_email( $_POST['md5Email'], $_POST['token']);			
}

/**
 * Ассинхронный контроллер для авторизации в системе
 * @param string $md5Email		MD5-Хеш еmail пользователя
 * @param string $md5Password	MD5-Хеш пароль пользователя
 * @param string $md5Token		MD5-Хеш жетона формы
 * @return mixed JSON объект со статусом или ничего
 */
function auth2_login( $md5Email = NULL, $md5Password = NULL, $md5Token = NULL )
{	
	// Ассинхронынй вывод без шаблона
	s()->async(TRUE);	
				
	// Пытаемся авторизироваться только если переданы все параметры 
	if( isset($md5Email) && isset($md5Password) && isset($md5Token)  )
	{
		// Выполним попытку авторизации пользователя
		echo auth()->login( $md5Email, $md5Password, $md5Token, TRUE );
	}
}

/**
 * Контроллер для выхода из системы
 */
function auth2_logout( $url = NULL )
{	
	// Установим ассинхронный режим	
	s()->async(TRUE);
	
	// Выполним попытку выхода пользователя
	auth()->logout();	
	
	// Перейдем на главную
	url()->redirect( $url );
	
	// Сообщим
	echo 'Выполняется переадресация на форму авторизации пользователя...';
}

/**
 * Контроллер для обработки не описаных методов
 * @param string $method Метод запрошенный через URL
 */
function auth2__HANDLER( $method )
{	
	// В независимости от того что от нас хотят выполним 
	// стандартный обработчик
	auth2();
}
?>