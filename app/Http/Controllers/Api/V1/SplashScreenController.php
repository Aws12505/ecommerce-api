<?php
// app/Http/Controllers/Api/V1/SplashScreenController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\SplashScreen\StoreSplashScreenRequest;
use App\Http\Requests\V1\SplashScreen\UpdateSplashScreenRequest;
use App\Services\V1\SplashScreenService;
use App\Models\SplashScreen;

class SplashScreenController extends Controller
{
    protected SplashScreenService $splashScreenService;

    public function __construct(SplashScreenService $splashScreenService)
    {
        $this->splashScreenService = $splashScreenService;
    }

    public function getActive()
    {
        try {
            $userType = request()->get('user_type', 'all');
            $splashScreen = $this->splashScreenService->getActiveSplashScreen($userType);

            if (!$splashScreen) {
                return response()->json([
                    'success' => true,
                    'message' => 'No active splash screen found',
                    'data' => null,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Active splash screen retrieved successfully',
                'data' => $splashScreen,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve active splash screen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function index()
    {
        try {
            $activeOnly = request()->boolean('active_only', false);
            $splashScreens = $this->splashScreenService->getAllSplashScreens($activeOnly);

            return response()->json([
                'success' => true,
                'message' => 'Splash screens retrieved successfully',
                'data' => $splashScreens,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve splash screens',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreSplashScreenRequest $request)
    {
        try {
            $splashScreen = $this->splashScreenService->createSplashScreen(
                $request->validated(),
                $request->file('image'),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Splash screen created successfully',
                'data' => $splashScreen->load(['creator', 'updater']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create splash screen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateSplashScreenRequest $request, SplashScreen $splashScreen)
    {
        try {
            $updatedSplashScreen = $this->splashScreenService->updateSplashScreen(
                $splashScreen,
                $request->validated(),
                $request->file('image'),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Splash screen updated successfully',
                'data' => $updatedSplashScreen,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update splash screen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(SplashScreen $splashScreen)
    {
        try {
            $this->splashScreenService->deleteSplashScreen($splashScreen);

            return response()->json([
                'success' => true,
                'message' => 'Splash screen deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete splash screen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function activate(SplashScreen $splashScreen)
    {
        try {
            $activatedSplashScreen = $this->splashScreenService->activateSplashScreen($splashScreen);

            return response()->json([
                'success' => true,
                'message' => 'Splash screen activated successfully',
                'data' => $activatedSplashScreen,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate splash screen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deactivate(SplashScreen $splashScreen)
    {
        try {
            $deactivatedSplashScreen = $this->splashScreenService->deactivateSplashScreen($splashScreen);

            return response()->json([
                'success' => true,
                'message' => 'Splash screen deactivated successfully',
                'data' => $deactivatedSplashScreen,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate splash screen',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
