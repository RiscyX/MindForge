<?php
declare(strict_types=1);

namespace App\Controller;

use OpenApi\Generator;

class SwaggerController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication->allowUnauthenticated(['ui', 'json']);
    }

    public function ui(): void
    {
        $this->viewBuilder()->setLayout('ajax');
    }

    public function json(): void
    {
        $this->request->allowMethod(['get']);
        $this->disableAutoRender();

        // Use zircote/swagger-php to scan the src/Controller/Api directory.
        // Return the raw OpenAPI JSON (no CakePHP wrapping), otherwise SwaggerUI
        // won't find the top-level "openapi" field.
        $generator = new Generator();
        $openapi = $generator->generate([
            ROOT . DS . 'src' . DS . 'Controller' . DS . 'Api',
        ]);

        $this->response = $this->response
            ->withType('application/json')
            ->withStringBody($openapi->toJson());
    }
}
