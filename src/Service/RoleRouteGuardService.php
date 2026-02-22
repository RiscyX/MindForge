<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Determines whether a given role is allowed to access a specific route.
 *
 * Encapsulates the per-role route whitelists previously hardcoded in
 * AppController::isCreatorRouteAllowed() and isRegularUserRouteAllowed().
 */
class RoleRouteGuardService
{
    /**
     * Check whether a creator (role_id = 2) can access the requested route.
     *
     * @param string $prefix Route prefix.
     * @param string $controller Route controller.
     * @param string $action Route action.
     * @return bool
     */
    public function isCreatorRouteAllowed(string $prefix, string $controller, string $action): bool
    {
        if ($prefix === 'Api') {
            return true;
        }

        if ($prefix === 'QuizCreator') {
            return in_array($controller, ['Dashboard', 'Tests'], true);
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
     * Check whether a regular user (role_id = 3) can access the requested route.
     *
     * @param string $prefix Route prefix.
     * @param string $controller Route controller.
     * @param string $action Route action.
     * @return bool
     */
    public function isRegularUserRouteAllowed(string $prefix, string $controller, string $action): bool
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
