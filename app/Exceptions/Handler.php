<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Database\QueryException;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->renderable(function (Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->wantsJson() || $request->ajax()) {
                $status = 500;
                $response = [
                    'success' => false,
                    'message' => 'Erreur serveur'
                ];

                if ($e instanceof ValidationException) {
                    $status = 422;
                    $response = [
                        'success' => false,
                        'message' => 'Erreur de validation',
                        'errors' => $e->errors()
                    ];
                } elseif ($e instanceof QueryException) {
                    $status = 500;
                    $response = [
                        'success' => false,
                        'message' => 'Erreur de base de données',
                        'error' => $e->getMessage()
                    ];
                } elseif ($e instanceof HttpException) {
                    $status = $e->getStatusCode();
                    $response = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }

                if (config('app.debug')) {
                    $response['debug'] = [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ];
                }

                return response()->json($response, $status, [
                    'Content-Type' => 'application/json;charset=UTF-8'
                ]);
            }
        });
    }
}
