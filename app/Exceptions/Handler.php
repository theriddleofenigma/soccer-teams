<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param $request
     * @param Throwable $e
     * @return JsonResponse|Response
     * @throws Throwable
     */
    public function render($request, Throwable $e): JsonResponse|Response
    {
        if ($e instanceof ModelNotFoundException) {
            return new JsonResponse([
                'message' => "{$this->prettyModelNotFound($e)} not found."
            ], 404);
        }

        return parent::render($request, $e);
    }

    /**
     * Prettify the model name if available.
     *
     * @param ModelNotFoundException $exception
     * @return string
     */
    private function prettyModelNotFound(ModelNotFoundException $exception): string
    {
        if (!is_null($exception->getModel())) {
            return class_basename($exception->getModel());
        }

        return 'Resource';
    }
}
