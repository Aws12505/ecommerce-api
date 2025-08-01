<?php
// app/Http/Controllers/Api/V1/Legal/LegalDocumentController.php

namespace App\Http\Controllers\Api\V1\Legal;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Legal\StoreLegalDocumentRequest;
use App\Http\Requests\V1\Legal\UpdateLegalDocumentRequest;
use App\Services\V1\Legal\LegalDocumentService;
use App\Models\LegalDocument;

class LegalDocumentController extends Controller
{
    protected LegalDocumentService $legalDocumentService;

    public function __construct(LegalDocumentService $legalDocumentService)
    {
        $this->legalDocumentService = $legalDocumentService;
    }

    public function index()
    {
        try {
            $publishedOnly = request()->boolean('published_only', false);
            $documents = $this->legalDocumentService->getAllDocuments($publishedOnly);

            return response()->json([
                'success' => true,
                'message' => 'Legal documents retrieved successfully',
                'data' => $documents,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve legal documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($type)
    {
        try {
            $document = $this->legalDocumentService->getDocumentByType($type, true);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found or not published',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Legal document retrieved successfully',
                'data' => $document,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve legal document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTermsOfService()
    {
        try {
            $document = $this->legalDocumentService->getTermsOfService();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Terms of Service not found or not published',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Terms of Service retrieved successfully',
                'data' => $document,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Terms of Service',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPrivacyPolicy()
    {
        try {
            $document = $this->legalDocumentService->getPrivacyPolicy();

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Privacy Policy not found or not published',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Privacy Policy retrieved successfully',
                'data' => $document,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Privacy Policy',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreLegalDocumentRequest $request)
    {
        try {
            $document = $this->legalDocumentService->createDocument(
                $request->validated(),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Legal document created successfully',
                'data' => $document->load(['creator', 'updater']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create legal document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateLegalDocumentRequest $request, LegalDocument $legalDocument)
    {
        try {
            $document = $this->legalDocumentService->updateDocument(
                $legalDocument,
                $request->validated(),
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Legal document updated successfully',
                'data' => $document,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update legal document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(LegalDocument $legalDocument)
    {
        try {
            $this->legalDocumentService->deleteDocument($legalDocument);

            return response()->json([
                'success' => true,
                'message' => 'Legal document deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete legal document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function publish(LegalDocument $legalDocument)
    {
        try {
            $document = $this->legalDocumentService->publishDocument($legalDocument);

            return response()->json([
                'success' => true,
                'message' => 'Legal document published successfully',
                'data' => $document,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish legal document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function unpublish(LegalDocument $legalDocument)
    {
        try {
            $document = $this->legalDocumentService->unpublishDocument($legalDocument);

            return response()->json([
                'success' => true,
                'message' => 'Legal document unpublished successfully',
                'data' => $document,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unpublish legal document',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
