<?php

namespace App\Http\Controllers;

use App\Services\PublicAssetResponseService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicAssetController extends Controller
{
    public function __construct(private PublicAssetResponseService $assets) {}

    public function favicon(): BinaryFileResponse
    {
        return $this->assets->serve(public_path(), 'favicon.ico');
    }

    public function backOffice(string $path): BinaryFileResponse
    {
        return $this->assets->serve(public_path('back-office'), $path);
    }

    public function loginPart(string $path): BinaryFileResponse
    {
        return $this->assets->serve(public_path('loginPart'), $path);
    }

    public function publicAsset(string $path): BinaryFileResponse
    {
        return $this->assets->serve(public_path(), $path);
    }
}
