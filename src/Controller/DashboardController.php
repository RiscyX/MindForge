<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Role;
use Psr\Http\Message\ResponseInterface;

class DashboardController extends AppController
{
    /**
     * Dashboard index.
     *
     * Admin users are redirected to the admin dashboard.
     *
     * @return \Psr\Http\Message\ResponseInterface|null Redirects for admins, renders view otherwise.
     */
    public function index(): ?ResponseInterface
    {
        $identity = $this->request->getAttribute('identity');
        if ($identity !== null && (int)$identity->get('role_id') === Role::ADMIN) {
            return $this->redirect([
                'prefix' => 'Admin',
                'controller' => 'Dashboard',
                'action' => 'index',
                'lang' => $this->request->getParam('lang', 'en'),
            ]);
        }

        $this->viewBuilder()->setLayout('default');

        $recentAttempts = [];
        $userId = $identity ? (int)$identity->getIdentifier() : 0;
        if ($userId > 0) {
            $langCode = (string)$this->request->getParam('lang', 'en');
            $language = $this->fetchTable('Languages')->find()
                ->where(['code LIKE' => $langCode . '%'])
                ->first();
            if ($language === null) {
                $language = $this->fetchTable('Languages')->find()->first();
            }

            $recentAttempts = $this->fetchTable('TestAttempts')->find()
                ->where([
                    'TestAttempts.user_id' => $userId,
                    'TestAttempts.finished_at IS NOT' => null,
                ])
                ->contain([
                    'Categories.CategoryTranslations' => function ($q) use ($language) {
                        return $q->where(['CategoryTranslations.language_id' => $language->id ?? null]);
                    },
                ])
                ->orderByDesc('TestAttempts.finished_at')
                ->limit(5)
                ->all()
                ->toList();
        }

        $this->set(compact('recentAttempts'));

        return null;
    }
}
