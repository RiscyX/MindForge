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
use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/5/en/controllers/pages-controller.html
 */
class PagesController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->Authentication->allowUnauthenticated([
            'display',
            'redirectToDefaultLanguage',
            'redirectToAdmin',
            'redirectToQuizCreator',
        ]);
    }

    /**
     * Displays a view
     *
     * @param string ...$path Path segments.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\ForbiddenException When a directory traversal attempt.
     * @throws \Cake\View\Exception\MissingTemplateException When the view file could not
     *   be found and in debug mode.
     * @throws \Cake\Http\Exception\NotFoundException When the view file could not
     *   be found and not in debug mode.
     * @throws \Cake\View\Exception\MissingTemplateException In debug mode.
     */
    public function display(string ...$path): ?Response
    {
        if (!$path) {
            return $this->redirect('/');
        }
        if (in_array('..', $path, true) || in_array('.', $path, true)) {
            throw new ForbiddenException();
        }
        $page = $subpage = null;

        if (!empty($path[0])) {
            $page = $path[0];
        }
        if (!empty($path[1])) {
            $subpage = $path[1];
        }
        $this->set(compact('page', 'subpage'));

        // If a user is already logged in, don't show the landing page.
        if ($page === 'home') {
            $identity = $this->request->getAttribute('identity');
            if ($identity !== null) {
                $lang = (string)$this->request->getParam('lang', 'en');
                $roleId = (int)$identity->get('role_id');

                if ($roleId === Role::ADMIN) {
                    return $this->redirect([
                        'prefix' => 'Admin',
                        'controller' => 'Dashboard',
                        'action' => 'index',
                        'lang' => $lang,
                    ]);
                }

                if ($roleId === Role::CREATOR) {
                    return $this->redirect([
                        'prefix' => 'QuizCreator',
                        'controller' => 'Dashboard',
                        'action' => 'index',
                        'lang' => $lang,
                    ]);
                }

                return $this->redirect([
                    'prefix' => false,
                    'controller' => 'Tests',
                    'action' => 'index',
                    'lang' => $lang,
                ]);
            }
        }

        try {
            return $this->render(implode('/', $path));
        } catch (MissingTemplateException $exception) {
            if (Configure::read('debug')) {
                throw $exception;
            }
            throw new NotFoundException();
        }
    }

    /**
     * Redirects to the default language home page.
     *
     * @return \Cake\Http\Response
     */
    public function redirectToDefaultLanguage(): Response
    {
        return $this->redirect([
            'controller' => 'Pages',
            'action' => 'display',
            'home',
            'lang' => 'en',
        ]);
    }

    /**
     * Redirects to the default language admin dashboard.
     *
     * @return \Cake\Http\Response
     */
    public function redirectToAdmin(): Response
    {
        $lang = (string)$this->request->getQuery('lang', 'en');
        if (!in_array($lang, ['en', 'hu'], true)) {
            $lang = 'en';
        }

        return $this->redirect([
            'prefix' => 'Admin',
            'controller' => 'Dashboard',
            'action' => 'index',
            'lang' => $lang,
        ]);
    }

    /**
     * Redirects to the default language quiz creator dashboard.
     *
     * @return \Cake\Http\Response
     */
    public function redirectToQuizCreator(): Response
    {
        $lang = (string)$this->request->getQuery('lang', 'en');
        if (!in_array($lang, ['en', 'hu'], true)) {
            $lang = 'en';
        }

        return $this->redirect([
            'prefix' => 'QuizCreator',
            'controller' => 'Dashboard',
            'action' => 'index',
            'lang' => $lang,
        ]);
    }
}
