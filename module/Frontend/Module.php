<?php

namespace Frontend;

use Zend\Mvc\MvcEvent,
    Zend\Session\Container,
    Zend\Session\SessionManager,
    Zend\Mvc\ModuleRouteListener,
    Zend\I18n\Translator\Translator,
    Zend\Authentication\AuthenticationService,
    Zend\ModuleManager\Feature\AutoloaderProviderInterface,
    Zend\Authentication\Adapter\DbTable as DbTableAuthAdapter;

class Module implements AutoloaderProviderInterface {

    public function onBootstrap($e) {
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        $eventManager->getSharedManager()->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function($e) {
            $controller = $e->getTarget();
            $controllerClass = get_class($controller);
            $moduleNamespace = substr($controllerClass, 0, strpos($controllerClass, '\\'));
            $config = $e->getApplication()->getServiceManager()->get('config');
            if (isset($config['module_layouts'][$moduleNamespace])) {
                $controller->layout($config['module_layouts'][$moduleNamespace]);
            }
        }, 100);

        $eventManager->attach(MvcEvent::EVENT_RENDER, function ($e) {
            $appEnv = APPLICATION_ENV;
            $config = $e->getApplication()->getServiceManager()->get('config');
            if ($appEnv === 'production' && PHP_SAPI !== 'cli') {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                if ($statusCode == 403 || $statusCode == 404 || $statusCode == 500) {
                    return $e->getViewModel()->setTemplate('error/' . $appEnv);
                }
            }
        }, -1000);

        $sessionManager = new SessionManager();
        $sessionManager->rememberMe(SES_EXPIRED);
        $sessionManager->start();
        Container::setDefaultManager($sessionManager);
    }

    public function getServiceConfig() {
        return array(
            'factories' => array(
                'My\Auth\MyStorage' => function($sm) {
                    return new \My\Auth\MyStorage('amazon247UserLogin');
                },
                'AuthService' => function($sm) {
                    $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
                    $dbTableAuthAdapter = new DbTableAuthAdapter($dbAdapter, 'users', 'email', 'password');
                    $authService = new AuthenticationService();
                    $authService->setAdapter($dbTableAuthAdapter);

                    $authService->setStorage($sm->get('My\Auth\MyStorage'));
                    return $authService;
                },
                'MvcTranslator' => function() {
                    return new Translator();
                },
            ),
        );
    }

    public function getControllerConfig() {
        return array(
            'abstract_factories' => array(
                'My\Service\ControlAbstractFactory'
            ),
        );
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
        );
    }

}
