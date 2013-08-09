<?php
/**
 * Auth(Authorization) - Авторизация получить объект для работы с авторизацией
 * пользователя
 *
 * @return Authorization Объект для работы с авторизацией пользователя
 */
function & auth(){	static $_v;	return ( $_v = isset($_v) ? $_v : \samson\auth\auth::getInstance('auth')); }

/**
 * Функция для проверки авторизации пользователя на сайте и вслучаии её отсутствия перенапраление
 * его на главную страницу
 *
 * @param string $redirect_url 	URL на которое требуется перенапривить не аторизированных пользователей
 * 								Указывается относительно корня текущего веб-приложения. По умолчанию переводит
 * 								пользователя на главную страницу
 * @return boolean Авторизирован ли пользователь
 */
function auth_only( $redirect_url = ''){ if( ! auth()->authorized ){ url()->redirect($redirect_url); return FALSE; } else return TRUE;}