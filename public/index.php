<?php
	ini_set('display_errors', 1);
	ini_set('display_starup_error', 1);
	error_reporting(E_ALL);


	require_once '../vendor/autoload.php';


	use Aura\Router\RouterContainer;
  	use Laminas\Diactoros\Response\RedirectResponse;
	use Illuminate\Database\Capsule\Manager as Capsule;

	
	session_start();

	
	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
	$dotenv->load();

	// CONEXION BASE DE DATOS
	$capsule = new Capsule;
	$capsule->addConnection([
	    'driver'    => 'mysql',
	    'host'      => getenv('DB_HOST'),
	    'database'  => getenv('DB_NAME'),
	    'username'  => getenv('DB_USER'),
	    'password'  => getenv('DB_PASS'),
	    'charset'   => 'utf8',
	    'collation' => 'utf8_unicode_ci',
	    'prefix'    => '',
	]);

	$capsule->setAsGlobal();

	$capsule->bootEloquent();





	$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals(
	    $_SERVER,
	    $_GET,
	    $_POST,
	    $_COOKIE,
	    $_FILES
	);


	// ROUTER!!!!
	$routerContainer = new RouterContainer();
	$map = $routerContainer->getMap();

	//ROUTES MAP
	//GET METHOD 
	$map->get('Login', '/soe/', [
		'controller' => 'App\Controllers\LoginController',
		'action' => 'getLogin'
	]);
	$map->get('Logout', '/soe/logout', [
		'controller' => 'App\Controllers\LoginController',
		'action' => 'logout',
		'auth' => true
	]);

	//ADMIN ROUTES
	$map->get('adminDashboard', '/soe/dashboard', [
		'controller' => 'App\Controllers\AdminDashController',
		'action' => 'getAdminDashboard',
		'auth' => true, 
		'userType' => getenv('USER_TYPE1')
	]);
	$map->get('adminMateriaDashboard', '/soe/dashboard/materia', [
		'controller' => 'App\Controllers\AdminDashController',
		'action' => 'getAdminMateriaDashboard',
		'auth' => true, 
		'userType' => getenv('USER_TYPE1')
	]);
	$map->get('adminMateriaAddForm', '/soe/dashboard/materia/add', [
		'controller' => 'App\Controllers\AdminMateriaController',
		'action' => 'getAdminAddMateriaForm',
		'auth' => true, 
		'userType' => getenv('USER_TYPE1')
	]);

	// TEACHER ROUTES
	$map->get('studentDashboard', '/soe/student', [
		'controller' => 'App\Controllers\StudentDashController',
		'action' => 'getStudentDashboard',
		'auth' => true, 
		'userType' => getenv('USER_TYPE2')
	]);

	// ESTUDENT ROUTES
	$map->get('teacherDashboard', '/soe/teacher', [
		'controller' => 'App\Controllers\TeacherDashController',
		'action' => 'getTeacherDashboard',
		'auth' => true, 
		'userType' => getenv('USER_TYPE3')
	]);


	// POST METHOD
	$map->post('PostLogin', '/soe/postLogin', [
		'controller' => 'App\Controllers\LoginController',
		'action' => 'postLogin'
	]);

	// ADMIN ROUTES 
	$map->post('adminMateriaAdd', '/soe/dashboard/materia/add', [
		'controller' => 'App\Controllers\AdminMateriaController',
		'action' => 'adminAddMateria',
		'auth' => true, 
		'userType' => getenv('USER_TYPE1')
	]);



	$matcher = $routerContainer->getMatcher();	
	$route = $matcher->match($request);

	if(!$route)
	{
		echo 'Ruta no encontrada';
	}else{
		$handlerData = $route->handler;
		$controllerName = $handlerData['controller'];
		$actionName = $handlerData['action'];
		$needsAuth = $handlerData['auth'] ?? false;
		$needsUserType = $handlerData['userType'] ?? false;

		$sessionUserId = $_SESSION['userId'] ?? null;
		$sessionUserType = $_SESSION['userType'] ?? null;

		if($needsAuth && !$sessionUserId)
		{
			$response = new RedirectResponse('/soe');  
		}else
		{
			if(!$needsUserType){
				$controller = new $controllerName;
				$response = $controller->$actionName($request);	
			}else
			{
				if ($needsUserType == $sessionUserType) 
				{
					$controller = new $controllerName;
					$response = $controller->$actionName($request);
				}else
				{
					unset($_SESSION['userId']);
					$response = new RedirectResponse('/soe');
				}	
			}	
			
		}


		foreach ($response->getHeaders() as $name => $values) 
		{
			foreach ($values as $value) {
				header(sprintf('%s: %s', $name, $value), false);
			}
		}

		http_response_code($response->getStatusCode());
		echo $response->getBody();
	}