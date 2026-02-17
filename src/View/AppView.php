<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\View;

use Cake\View\View;

/**
 * Application View
 *
 * Your application's default view class
 *
 * @link https://book.cakephp.org/5/en/views.html#the-app-view
 */
class AppView extends View
{
    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like adding helpers.
     *
     * e.g. `$this->addHelper('Html');`
     *
     * @return void
     */
    public function initialize(): void
    {
        /*
         * Bootstrap 5 integration for CakePHP FormHelper.
         *
         * - errorClass → 'is-invalid' so Bootstrap draws the red border.
         * - error template → 'invalid-feedback' so the message is styled + visible.
         */
        $this->addHelper('Form', [
            'templates' => [
                // When a field has a validation error CakePHP adds this CSS class
                // to the <input>/<select>/<textarea>.  Bootstrap needs 'is-invalid'.
                'error' => '<div class="invalid-feedback d-block" id="{{id}}">{{content}}</div>',
                'inputContainerError' => '<div class="{{containerClass}} {{type}}{{required}} error mb-3">{{content}}{{error}}</div>',
            ],
            'errorClass' => 'is-invalid',
        ]);
    }
}
