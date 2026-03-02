<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
     */
    public function render($request, Throwable $e): JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        // For API requests, always return JSON
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderJsonException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Render exception as JSON with security considerations.
     */
    protected function renderJsonException($request, Throwable $e): JsonResponse
    {
        // ValidationException: return field errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        // ModelNotFoundException: resource not found
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
            ], 404);
        }

        // NotFoundHttpException: route/endpoint not found
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'Endpoint not found',
            ], 404);
        }

        // Database/Query exceptions: NEVER leak schema or SQL in production
        if ($e instanceof QueryException) {
            $status = 500;
            $message = 'A database error occurred';

            // In local/dev, show more detail; in production, hide everything
            if (config('app.debug')) {
                $message = $e->getMessage();
            }

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        // Catch-all for other exceptions
        $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        $message = config('app.debug') ? $e->getMessage() : 'An error occurred';

        // HTTP exceptions (like 403, 401) should show their message
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
            $message = $e->getMessage() ?: 'HTTP error';
        }

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
