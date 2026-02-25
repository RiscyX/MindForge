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
use App\Service\RoleRouteGuardService;
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
        if ((string)$this->request->getParam('prefix', '') !== 'Api') {
            $this->loadComponent('FormProtection');
        }
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

        // Force-logout any authenticated user whose account has been banned.
        // A fresh DB lookup is used so the ban takes effect on the very next request,
        // regardless of the stale session snapshot.
        if ($identity !== null) {
            $userId = $identity->getIdentifier();
            /** @var \App\Model\Table\UsersTable $usersTable */
            $usersTable = $this->fetchTable('Users');
            $row = $usersTable->find()
                ->select(['is_blocked'])
                ->where(['id' => $userId])
                ->first();

            if ($row !== null && (bool)$row->is_blocked) {
                $this->Authentication->logout();
                $this->Flash->error(__('Your account has been suspended. Please contact support.'));
                $event->setResult($this->redirect([
                    'controller' => 'Users',
                    'action' => 'login',
                    'lang' => $lang,
                    'prefix' => false,
                ]));

                return;
            }
        }

        if ($identity !== null && (int)$identity->get('role_id') === Role::CREATOR) {
            $prefix = (string)$this->request->getParam('prefix', '');
            $controller = (string)$this->request->getParam('controller', '');
            $action = (string)$this->request->getParam('action', '');

            if (!(new RoleRouteGuardService())->isCreatorRouteAllowed($prefix, $controller, $action)) {
                throw new ForbiddenException();
            }
        }

        if ($identity !== null && (int)$identity->get('role_id') === Role::USER) {
            $prefix = (string)$this->request->getParam('prefix', '');
            $controller = (string)$this->request->getParam('controller', '');
            $action = (string)$this->request->getParam('action', '');

            if (!(new RoleRouteGuardService())->isRegularUserRouteAllowed($prefix, $controller, $action)) {
                throw new ForbiddenException();
            }
        }
    }
}
