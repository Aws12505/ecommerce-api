<?php
// app/Services/V1/Legal/LegalDocumentService.php

namespace App\Services\V1\Legal;

use App\Models\LegalDocument;
use Illuminate\Database\Eloquent\Collection;

class LegalDocumentService
{
    public function getAllDocuments(bool $publishedOnly = false): Collection
    {
        $query = LegalDocument::with(['creator', 'updater']);

        if ($publishedOnly) {
            $query->published();
        }

        return $query->orderBy('type')->get();
    }

    public function getDocumentByType(string $type, bool $publishedOnly = true): ?LegalDocument
    {
        $query = LegalDocument::where('type', $type);

        if ($publishedOnly) {
            $query->published();
        }

        return $query->first();
    }

    public function getTermsOfService(): ?LegalDocument
    {
        return $this->getDocumentByType(LegalDocument::TYPE_TERMS_OF_SERVICE);
    }

    public function getPrivacyPolicy(): ?LegalDocument
    {
        return $this->getDocumentByType(LegalDocument::TYPE_PRIVACY_POLICY);
    }

    public function createDocument(array $data, int $userId): LegalDocument
    {
        // Auto-increment version if document type exists
        $existingDocument = LegalDocument::where('type', $data['type'])->first();
        if ($existingDocument && !isset($data['version'])) {
            $currentVersion = (float) $existingDocument->version;
            $data['version'] = (string) ($currentVersion + 0.1);
        }

        return LegalDocument::create([
            'type' => $data['type'],
            'title' => $data['title'],
            'content' => $data['content'],
            'version' => $data['version'] ?? '1.0',
            'is_published' => $data['is_published'] ?? false,
            'created_by' => $userId,
            'updated_by' => $userId,
            'published_at' => ($data['is_published'] ?? false) ? now() : null,
        ]);
    }

    public function updateDocument(LegalDocument $document, array $data, int $userId): LegalDocument
    {
        // Auto-increment version if content changed
        if (!isset($data['version']) && $document->content !== $data['content']) {
            $currentVersion = (float) $document->version;
            $data['version'] = (string) ($currentVersion + 0.1);
        }

        $updateData = [
            'type' => $data['type'],
            'title' => $data['title'],
            'content' => $data['content'],
            'version' => $data['version'] ?? $document->version,
            'updated_by' => $userId,
        ];

        // Handle publishing status
        if (isset($data['is_published'])) {
            $updateData['is_published'] = $data['is_published'];
            if ($data['is_published'] && !$document->is_published) {
                $updateData['published_at'] = now();
            } elseif (!$data['is_published']) {
                $updateData['published_at'] = null;
            }
        }

        $document->update($updateData);

        return $document->fresh(['creator', 'updater']);
    }

    public function deleteDocument(LegalDocument $document): bool
    {
        return $document->delete();
    }

    public function publishDocument(LegalDocument $document): LegalDocument
    {
        $document->publish();
        return $document->fresh(['creator', 'updater']);
    }

    public function unpublishDocument(LegalDocument $document): LegalDocument
    {
        $document->unpublish();
        return $document->fresh(['creator', 'updater']);
    }

    public function getAvailableTypes(): array
    {
        return LegalDocument::getAvailableTypes();
    }
}
