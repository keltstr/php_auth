<?php
namespace samson\auth;

use samson\core\CompressableService;

/**
 * Модуль авторизации на сайте
 *
 * @package SamsonPHP
 * @author Vitaly Iegorov <vitalyiegorov@gmail.com>
 * @version 1.5
 */
class Auth extends CompressableService
{
    /** Реальный идентификатор файлов модуля */
    protected $id = 'auth2';

    /** Список модулей от которых завист данный модуль */
    protected $requirements = array
    (
        'activerecord',        
        'md5'
    );

    /** Имя сущности в БД с которой должен работать модуль */
    public $entity = 'user';

    /** Флаг принудительной авторизации на сайте */
    public $force = FALSE;

    /**	Имя поля для e-mail в таблице БД	*/
    public $email = 'md5_email';

    /** Имя поля для пароля в таблице БД */
    public $password = 'md5_password';

    /** Database field name for storing online status */
    public $online = 'online';

    /** Database field name for storing last login status */
    public $lastlogin = 'last_login';

    /** Флаг авторизирован ли пользователь в системе */
    public $authorized = FALSE;

    /** Collection of modules that can ignore forced authorization */
    public $allow = array( 'auth2', 'auth', 'resourcerouter' );

    /**
     * Указатель на текущего пользователь
     * @var \samson\activerecord\dbRecord
     */
    public $user;

    /** Внешний обработчик который выполняется перед инициализацией модуля авторизации */
    public $init_handler;

    /** Внешний обработчик успешной авторизации пользователя */
    public $login_handler;

    /** Внешний обработчик выхода пользователя */
    public $logout_handler;

    /** Внешний обработчик принудительной авторизации */
    public $force_handler;

    /** Маркер для хранения указателя на данные в сессии */
    private $session_marker;

    /** Prepare module environment */
    public function prepare()
    {
        // TODO: local classes load after prepare - they must load before prepares im modules
        if (class_exists($this->entity)) {
            // Create special fields
            db()->createField($this, $this->entity, 'email', 'VARCHAR(32)');
            db()->createField($this, $this->entity, 'password', 'VARCHAR(32)');
            db()->createField($this, $this->entity, 'online', 'INT(1)');
            db()->createField($this, $this->entity, 'lastlogin', 'DATETIME');
        }
    }

    /** @see \samson\core\ModuleConnector::init() */
    public function init(array $params = array())
    {
        // Вызовем родительсикй метод
        parent::init( $params );

        // Получим корень сайта
        $url_base = url()->base();

        // Сформируем правильное имя класса
        $this->entity = ns_classname($this->entity, 'samson\activerecord');

        //trace($this->entity);

        // Проверим существует ли указанная сущность описівающая пользователей системы
        if(class_exists($this->entity)) {
            // Имя указателя на запись об авторизации в сессии
            $this->session_marker = '_auth_' . str_replace('/', '_',$url_base) . '_';

            // Получим указатель на схему сущности в БД
            $this->db_handler = dbQuery( $this->entity );

            // Если указан обработчик инициализации модуля - выполним его
            if( function_exists($this->init_handler) ) $this->init_handler();

            // Если маркер установлен
            if( isset( $_SESSION[ $this->session_marker ] ) )
            {
                // Достанем пользователя из сессии
                $this->user = unserialize( $_SESSION[ $this->session_marker ] );

                // Установим флаг авторизации
                $this->authorized = TRUE;
            }
            // Если куки авторизации заданы
            else if ( isset($_COOKIE[$url_base.'_cookie_md5Email']) && isset($_COOKIE[$url_base.'_cookie_md5Password']) )
            {
                // Выполним авторизацию пользователя
                $this->login( $_COOKIE[$url_base.'_cookie_md5Email'], $_COOKIE[$url_base.'_cookie_md5Password'], $this->create_token('', TRUE));
            }
            // Если необходима принудительная авторизация - перейдем к форме авторизации, если мы уже не в ней
            else if( $this->force && !in_array( url()->module(), $this->allow))
            {
                // Сохраним требуемую пользователем страницу в сессию
                // что бы после авторизации вернуть пользователя на эту страницу
                $_SESSION['__SamsonAuth__LP__'] = $_SERVER['REQUEST_URI'];

                // Выполним переадресацию на контроллер авторизации
                url()->redirect( $this->id );
            }

            // If we have ecternal force handler
            if( function_exists($this->force_handler) ) {
                call_user_func($this->force_handler);
            }
        }
        // Без авторизации - никуда
        else die('Модуль авторизации - Не передано имя сущности в БД');

        // Все ок
        return true;
    }

    /** Обновить информацию об авторизированном  пользователе */
    public function update( & $db_user = NULL )
    {
        // Подменим указатель на запись в БД текущего пользователя
        if( isset( $db_user )) $this->user = & $db_user;

        // Запишем пользователя из сессии
        $_SESSION[ $this->session_marker ] = serialize( $this->user );
    }

    /**
     * Сгенерировать одноразовый жетон
     *
     * @param string $token_id Идентификатор жетона для его уникальности
     * @param boolean $returnValue Флаг возврата значения жетона
     * @return string Сгенерированный жетон
     */
    public function create_token( $token_id = '', $returnValue = FALSE )
    {
        // Сформируем уникальное имя жетона формы
        $token_name = $this->session_marker . 'token_' . $token_id;

        // Выдадим временный жетон для формы
        $token_value = md5( uniqid( '', TRUE ) . uniqid( '', TRUE ) );

        // Сохраним жетон формы в сессиию
        $_SESSION[ $token_name ] = $token_value;

        // Получим хеш жетона
        $token =  md5( $token_value );

        // Вернем сгенерированное значение закодированное хеш-функцией
        if( ! $returnValue ) return '<input name="token" id="token" type="hidden" value="' . $token . '">';
        // Если передан флаг то вернем только значение жетона
        else return $token;
    }

    /**
     * Сверить полученное значение жетона с сгенерированным
     *
     * @param string $token_value	Значение жетона для проверки
     * @param string $token_id		Специальный идентификатор жетона
     * @return boolean Является ли жетон валидным
     */
    public function verify_token( $token_value = '', $token_id = '' )
    {
        // Сформируем уникальное имя жетона формы
        $token_name = $this->session_marker . 'token_' . $token_id;

        // Если жетон вообще не существует
        if( ! isset( $_SESSION[ $token_name ] )) return e('Указанный жетон #'.$token_name.' - не существует');
        // Сверим хеш версии жетонов
        else if( md5( $_SESSION[ $token_name ] ) === $token_value ) return TRUE;
        // Ниче не вышло - жетоны не совпадают
        else return e('Полученный жетон ## -> не валидный ##-##', D_SAMSON_AUTH_DEBUG, array( $token_name, $token_value, md5( $_SESSION[ $token_name ] ) ) );
    }

    /**
     * Выполнить верификацию E-mail пользователя
     *
     * @param string 	$md5_email 		E-mail пользователя в хеш-формате
     * @param string 	$token_value	Значение жетона для проверки
     * @param mixed 	$verified_user	Переменная для возврата в нее найденого пользователя
     * @return boolean	Результат верификации E-mail
     */
    public function verify_email( $md5_email, $token_value, & $verified_user = NULL )
    {
        // Проверим жетон на валидность
        if( $this->verify_token( $token_value ) )
        {
            // Сформируем параметры запроса динамически
            if( $this->db_handler->cond( $this->email, $md5_email )->cond( 'Active', 1 )->first( $verified_user ))
            {
                // Если пользователь с таким хешем имейла найден - все прошло успешно
                return TRUE;
            }
            else return e('Указанный E-mail(##) не найден', D_SAMSON_AUTH_DEBUG, array( $md5_email ) );
        }
        else return e('Одноразовый жетон(##) не валидный', D_SAMSON_AUTH_DEBUG, array( $token_value ) );
    }

    /**
     * Finish authorization process and return asynchronous response
     * @param \samson\activerecord\dbRecord $user Pointer to filled user object
     * @param bool $remember Flag for setting cookie for further automatic authorization
     *
     * @return array Asynchronous response array
     */
    public function authorize(&$user, $remember = false)
    {
        // Store pointer to authorized user
        $this->user = & $user;

        // Запишем в сессию указатель на авторизированного пользователя
        $_SESSION[ $this->session_marker ] = serialize( $this->user );

        // Установим глобальный флаг авторизации пользователя
        $this->authorized = true;

        // Если задан внешний обработчик то выполним его
        if (is_callable($this->login_handler)) {
            return call_user_func($this->login_handler, $this->user );
        } else { // Выполним стандартные действия
            // Отметим что пользователь вошел в систему
            $this->user[$this->online] = 1;
            $this->user[$this->lastlogin] = date('Y-m-d H:i:s');
            $this->user->save();
        }

        // Если необходимо сохранить пользователя
        if ($remember) {
            // Получим корень сайта
            $url_base = url()->base();

            // Установим куки для автоматической авторизации
            setcookie( $url_base.'_cookie_md5Email', $user[$this->email], time()+(24*3600),'/');
            setcookie( $url_base.'_cookie_md5Password', $user[$this->password], time()+(24*3600),'/' );
        }

        // Специальный массив для клиента
        $return = array( 'status' => '1' );

        // Если пользователь хотел попасть на конкретнуюю станицу то вернем его туда, иначе на главную
        $return[ 'url' ] = isset( $_SESSION['__SamsonAuth__LP__'] ) ? $_SESSION['__SamsonAuth__LP__']: url()->base();

        // Очистим URL для редиректа
        unset($_SESSION['__SamsonAuth__LP__']);

        // Если все прошло успешно то вернем пециальный объект со статусом и адрессом переадресации
        return $return;
    }

    /**
     * Выполнить авторизацию пользователя в системе
     *
     * @param string    $md5_email    E-mail пользователя в хеш-формате
     * @param string    $md5_password Пароль пользователя в хеш-формате
     * @param string    $token_value  Значение жетона для проверки
     * @param bool      $remember_me  Флаг для записи данных пользователя в КУКИ браузера
     *
     * @return boolean    Результат авторизации
     */
    public function login( $md5_email, $md5_password, $token_value, $remember_me = FALSE )
    {
        // Выполним верификацию E-mail
        if ($this->verify_email($md5_email, $token_value, $this->user)) {
            // Теперь сверим md5 пароль пользователя
            if ($this->user[$this->password] === $md5_password) {
                // Call authorization finishing logic
                return json_encode($this->authorize($this->user, $remember_me));
            } else {
                return e( 'Не правильный пароль ## - ##', D_SAMSON_AUTH_DEBUG, array( $this->user->$password, $md5_password ) );
            }
        } else {
            return e('Авторизация не удалась', D_SAMSON_AUTH_DEBUG );
        }
    }

    /**
     * Выполнить выход пользователя из системы
     */
    public function logout()
    {
        // Если пользователь авторизирован
        if( $this->authorized )
        {
            // Установим флаг что мы вышли из системы
            $this->authorized = FALSE;

            // Получим корень сайта
            $url_base = url()->base();

            // Очистим маркер сессии
            unset( $_SESSION[ $this->session_marker ] );

            // Очистим куки
            setcookie( $url_base.'_cookie_md5Email','',-1,'/');
            setcookie( $url_base.'_cookie_md5Password','',-1,'/');

            // Если задан внешний обработчик то выполним его
            if( is_callable( $this->logout_handler ) ){$handler = $this->logout_handler; $handler( $this->user );}
            // Выполним стандартные действия
            else
            {
                // Сохраним в БД статус
                $this->user->Online = 0;
                $this->user->save();
            }
        }
    }

    /** Обработчик сериализации объекта */
    public function __sleep()
    {
        // Remove all unnessesary fields from serialization
        return array_diff( parent::__sleep(), array( 'authorized', 'user' ));
    }
}