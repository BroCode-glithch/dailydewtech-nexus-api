<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Success response helper.
     */
    protected function successResponse($data, string $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Error response helper.
     */
    protected function errorResponse(string $message, $errors = null, int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Not found response helper.
     */
    protected function notFoundResponse(string $resource = 'Resource'): JsonResponse
    {
        return $this->errorResponse("{$resource} not found.", null, 404);
    }

    /**
     * Validation error response helper.
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return $this->errorResponse('Validation failed.', $errors, 422);
    }

    /**
     * Created response helper.
     */
    protected function createdResponse($data, string $message = 'Resource created successfully.'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    /**
     * Deleted response helper.
     */
    protected function deletedResponse(string $message = 'Resource deleted successfully.'): JsonResponse
    {
        return $this->successResponse(null, $message, 200);
    }
}
