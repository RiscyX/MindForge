<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller;

use App\Model\Entity\Role;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\I18n;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @link https://book.cakephp.org/5/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * Supported languages mapped to locales.
     *
     * @var array<string, string>
     */
    protected array $supportedLanguages = [
        'en' => 'en_US',
        'hu' => 'hu_HU',
    ];

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('FormProtection');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');

        $this->loadComponent('Authentication.Authentication');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/5/en/controllers/components/form-protection.html
         */
        //$this->loadComponent('FormProtection');
    }

    /**
     * Called before the controller action. Sets locale based on URL language parameter.
     *
     * @param \Cake\Event\EventInterface $event The beforeFilter event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Get language from URL parameter (e.g., /en/login or /hu/login)
        $lang = $this->request->getParam('lang', 'en');

        // Set the locale based on the language
        if (isset($this->supportedLanguages[$lang])) {
            $locale = $this->supportedLanguages[$lang];
        } else {
            $locale = 'en_US';
            $lang = 'en';
        }

        // Set the locale for translations
        I18n::setLocale($locale);

        // Make language available to all views
        $this->set('lang', $lang);
        $this->set('currentLocale', $locale);

        $identity = $this->request->getAttribute('identity');
        if ($identity !== null && (int)$identity->get('role_id') === Role::CREATOR) {
            $prefix = (string)$this->request->getParam('prefix', '');
            $controller = (string)$this->request->getParam('controller', '');
            $action = (string)$this->request->getParam('action', '');

            if (!$this->isCreatorRouteAllowed($prefix, $controller, $action)) {
                throw new ForbiddenException();
            }
        }

        if ($identity !== null && (int)$identity->get('role_id') === Role::USER) {
            $prefix = (string)$this->request->getParam('prefix', '');
            $controller = (string)$this->request->getParam('controller', '');
            $action = (string)$this->request->getParam('action', '');

            if (!$this->isRegularUserRouteAllowed($prefix, $controller, $action)) {
                throw new ForbiddenException();
            }
        }
    }

    /**
     * Check whether a creator can access the requested route.
     *
     * @param string $prefix Route prefix.
     * @param string $controller Route controller.
     * @param string $action Route action.
     * @return bool
     */
    private function isCreatorRouteAllowed(string $prefix, string $controller, string $action): bool
    {
        if ($prefix === 'Api') {
            return true;
        }

        if ($prefix === 'QuizCreator') {
            return $controller === 'Dashboard';
        }

        if ($prefix === 'Admin') {
            return false;
        }

        if ($controller === 'Tests') {
            return true;
        }

        if ($controller === 'Users') {
            return in_array(
                $action,
                [
                    'profile',
                    'profileEdit',
                    'stats',
                    'logout',
                    'login',
                    'register',
                    'forgotPassword',
                    'resetPassword',
                    'confirm',
                ],
                true,
            );
        }

        if ($controller === 'Pages') {
            return in_array($action, ['display', 'redirectToQuizCreator', 'redirectToDefaultLanguage'], true);
        }

        return false;
    }

    /**
     * Check whether a regular user can access the requested route.
     *
     * @param string $prefix Route prefix.
     * @param string $controller Route controller.
     * @param string $action Route action.
     * @return bool
     */
    private function isRegularUserRouteAllowed(string $prefix, string $controller, string $action): bool
    {
        if ($prefix === 'Api') {
            return true;
        }

        if ($prefix === 'Admin' || $prefix === 'QuizCreator') {
            return false;
        }

        if ($controller === 'Tests') {
            return true;
        }

        if ($controller === 'Users') {
            return in_array(
                $action,
                [
                    'profile',
                    'profileEdit',
                    'stats',
                    'logout',
                    'login',
                    'register',
                    'forgotPassword',
                    'resetPassword',
                    'confirm',
                ],
                true,
            );
        }

        if ($controller === 'Pages') {
            return in_array($action, ['display', 'redirectToDefaultLanguage'], true);
        }

        return false;
    }
}
