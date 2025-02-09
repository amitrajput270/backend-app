<?php
namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (\Exception $e) {

            if ($e instanceof TokenExpiredException) {
                return response()->json([
                    'status'  => false,
                    'message' => $e->getMessage(),
                    'data'    => [],
                ], Response::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof TokenInvalidException) {
                return response()->json([
                    'status'  => false,
                    'message' => $e->getMessage(),
                    'data'    => [],
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($e instanceof JWTException) {
                return response()->json([
                    'status'  => false,
                    'message' => $e->getMessage(),
                    'data'    => [],
                ], Response::HTTP_BAD_REQUEST);
            }

            // try {
            //     \Tymon\JWTAuth\Facades\JWTAuth::parseToken()->authenticate();
            // } catch (TokenExpiredException $e) {
            //     return response()->json([
            //         'status'  => false,
            //         'message' => $e->getMessage(),
            //         'data'    => [],
            //     ], Response::HTTP_UNAUTHORIZED);
            // } catch (TokenInvalidException $e) {
            //     return response()->json([
            //         'status'  => false,
            //         'message' => $e->getMessage(),
            //         'data'    => [],
            //     ], Response::HTTP_BAD_REQUEST);
            // } catch (JWTException $e) {
            //     return response()->json([
            //         'status'  => false,
            //         'message' => $e->getMessage(),
            //         'data'    => [],
            //     ], Response::HTTP_BAD_REQUEST);
            // }

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => $e->getTraceAsString(),
            ], 500);
        });
    }
}
