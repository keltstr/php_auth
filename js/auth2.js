// Функции для работы с формой авторизации
var SamsonAuth = function( _page )
{	
	// Флаг проверки email пользователя
	var emailValidated = false;
	
	// Флаг отправки запроса на авторизацию
	var requestSent = false;
	
	/**
	 * Валидация Email адресса пользователя
	 * @param emailInput Email адресс для валидации
	 */
	var validateEmail = function( object, options, event )
	{		
		// Если нажата клавиша не "Enter"
		if( event && event.keyCode && (event.keyCode != 13) ) return false; 
		
		// Получим введенный пользователем Email
		var emailValue = s('#Email').val();
		
		// Если вообще нечего не ввели
		if( ! emailValue.length ) return false;
		
		// Установим хеш для Email
		s('#md5Email', _page).val( s.md5( emailValue ) );
		
		// Отобразим строку ввода пароля
		s('.password-row').show();		
		
		// Сфокусируемся на вводе пароля
		s('#Password').focus();	
		
		// Спрячем кнопку для email
		s('#btnValidateEmail').hide();
	
		// Установим флаг что email проверен
		emailValidated = true;		
	};	
	
	/**
	 * Выполнить авторизацию пользователя
	 */
	var login = function( object, options, event )
	{		
		// Если мы уже отправили запрос на авторизацию - выйдем
		if( requestSent ) return false;
		
		// Если нажата клавиша не "Enter"
		if( event && event.keyCode && (event.keyCode != 13) ) return false;
		
		// Если email еще не проверен
		if( !emailValidated ) return false;	
		
		// Получим введенный пользователем Password
		var passwordValue = s('#Password').val() ;
		
		// Если вообще нечего не ввели
		if( ! passwordValue.length ) return false;
		
		// Выведем картинку загрузки
		s('.loader').show();	
		
		// Установим хеш для Email
		var md5_email = s.md5( s('#Email').val());
		// Установим хеш для Password
		var md5_pwd = s.md5( passwordValue );
		// Установим жетон формы
		var md5_tkn = s('#token').val();
		
		// Сформируем url для запроса к контроллеру
		var url = SamsonPHP.url_base( SamsonPHP.moduleID(), 'login', md5_email, md5_pwd, md5_tkn);
			
		// Установим флаг что мы отправили запрос а авторизацию
		requestSent = true;		
		
		// Выполним ассинхронный запрос на авторизацию
		s.ajax( url, function( serverResponse )
		{		
			// Скроем картинку загрузки
			s('.loader').hide();
			
			// Преобразуем ответ от сервера в объект
			try{serverResponse = JSON.parse( serverResponse );}
			// Обработаем исключение
			catch(e){ /*alert('Ошибка обработки ответа полученного от сервера, повторите попытку отправки данных -> '+e.toString());*/ };		
			
			// Уберем флаг отправки запроса
			requestSent = false;
			
			// Если авторизация прошла успешно
			if( serverResponse && serverResponse.status === '1' )
			{	
				// Выведем сообщение об успешной авторизации
				s('#formHeader').html('Выполняется вход...').addClass('login-success');
			
				// Перейдем на целевую страницу или на главную
				window.location.href = serverResponse.url ? serverResponse.url : '';
			}	
			// Выведем текст ошибки авторизации
			else s('#formHeader').html('Ошибка авторизации, проверьте введённые данные').addClass('login-error');
		}, undefined, undefined, undefined, 'GET' );		
	};
	
	// Обработчик ввода Email адреса пользователя, и обработчик клавиши "Enter"
	s( '#Email', _page ).change( validateEmail, true, true ).keyup( validateEmail, true, true ).focus();
	
	// Обработчик нажатия на кнопку "Далее"
	s( '#btnValidateEmail', _page ).click( validateEmail, true, true );	
	
	// Обработчик ввода Password адреса пользователя, и обработчик клавиши "Enter"
	s( '#Password', _page ).change( login, true, true ).keyup( login, true, true );
	
	// Обработчик нажатия на кнопку "Войти"
	s( '#btnLogin', _page ).click( login, true, true );		
};

// Инициализация JS для модуля авторизации
s('#auth2').pageInit(SamsonAuth);
