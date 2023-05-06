<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Error log for try/catch exceptions.
 *
 * @param Exception $exception
 * @param string $message
 * @param string $location
 * @param array $params
 */
function logError(Exception $exception, string $message, string $location = '', array $params = []): void
{
    simpleErrorLog($exception->getMessage(), $location, $params, $exception->getTraceAsString(), $message);
}

/**
 * Simple error log format.
 *
 * @param string $errorMessage
 * @param string $location
 * @param array $params
 * @param string $errorTrace
 * @param string $message
 */
function simpleErrorLog(string $errorMessage, string $location, array $params, string $errorTrace = '', string $message = 'Error'): void
{
    Log::error($message, [
        'message' => $errorMessage,
        'location' => $location,
        'params' => array_merge($params, request()->all()),
        'trace' => $errorTrace,
    ]);
}

/**
 * Store the uploaded file image to the file storage.
 *
 * @param UploadedFile $file
 * @return string
 */
function storeImage(UploadedFile $file): string
{
    return $file->storePubliclyAs('logos', Str::ulid() . '.' . $file->extension());
}
