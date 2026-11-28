<?php

/**
 * Recurso API de uma candidatura a petsitter: estado, documentos (com o link para abrir cada um),
 * motivos da recusa (código e texto), mensagem do admin e quem decidiu. Só o admin e o próprio candidato a veem.
 * API resource for a petsitter application: status, documents (with a link to open each one), rejection
 * reasons (code and label), the admin message and who decided. Only the admin and the applicant see it.
 */

namespace App\Http\Resources;

use App\Models\PetsitterApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetsitterApplication
 */
class PetsitterApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $documents = collect($this->identification_documents ?? [])->values()->map(fn (string $path, int $index) => [
            'index' => $index,
            'name' => basename($path),
            'type' => strtoupper(pathinfo($path, PATHINFO_EXTENSION)) ?: 'Ficheiro',
            // rota da API que devolve o ficheiro (só o admin tem acesso)
            'url' => "/api/petsitter-applications/{$this->id}/documents/{$index}",
        ]);

        return [
            'id' => $this->id,
            'status' => $this->status,
            'documents' => $documents,
            'reasons' => $this->reasons ?? [],
            'reason_labels' => collect($this->reasons ?? [])
                ->map(fn (string $code) => PetsitterApplication::REASON_LABELS[$code] ?? $code)
                ->values(),
            'message' => $this->message,
            'decided_by' => $this->whenLoaded('decidedBy', fn () => $this->decidedBy
                ? ['id' => $this->decidedBy->id, 'name' => $this->decidedBy->name]
                : null),
            'decided_at' => $this->decided_at,
            'created_at' => $this->created_at,
        ];
    }
}
