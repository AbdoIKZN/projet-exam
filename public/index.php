<?php
/**
 * EventHub Pro — public/index.php
 * Point d'entrée unique (Front Controller) de l'application MVC.
 */

// Charger les classes de base du Core
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Database.php';

// Charger les modèles
require_once __DIR__ . '/../app/Models/EventModel.php';
require_once __DIR__ . '/../app/Models/RegistrationModel.php';
require_once __DIR__ . '/../app/Models/UserModel.php';

// Charger les contrôleurs
require_once __DIR__ . '/../app/Controllers/EventController.php';
require_once __DIR__ . '/../app/Controllers/ApiController.php';
require_once __DIR__ . '/../app/Controllers/PdfController.php';

// Initialiser le routeur
$router = new Router();

// Enregistrer les routes de l'application
$router->get('', 'EventController@index');
$router->get('events/create', 'EventController@createForm');
$router->post('events/create', 'EventController@createSubmit');
$router->post('events/register', 'EventController@registerSubmit');
$router->get('events/unsubscribe', 'EventController@unsubscribe');
$router->get('dashboard', 'EventController@dashboard');

// Routes API JSON
$router->get('api/events', 'ApiController@searchEvents');
$router->post('api/events', 'ApiController@searchEvents');
$router->get('api/stats', 'ApiController@dashboardStats');

// Routes PDF
$router->get('pdf/ticket', 'PdfController@downloadTicket');
$router->get('pdf/report', 'PdfController@downloadReport');

// Dispatcher la requête
$router->dispatch();
