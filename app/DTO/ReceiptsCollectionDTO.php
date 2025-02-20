<?php

namespace App\DTO;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Responsable;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

readonly class ReceiptsCollectionDTO implements JsonSerializable, Responsable
{
    public function __construct(public LengthAwarePaginator $paginator) {}

    public function toResponse($request): Response
    {
        return response()->json($this);
    }

    public function jsonSerialize(): array
    {
        return [
            'current_page' => $this->paginator->currentPage(),
            'data' => $this->paginator->items(),
            'first_page_url' => $this->paginator->url(1),
            'last_page_url' => $this->paginator->url($this->paginator->lastPage()),
            'next_page_url' => $this->paginator->nextPageUrl(),
            'prev_page_url' => $this->paginator->previousPageUrl(),
            'from' => $this->paginator->firstItem(),
            'to' => $this->paginator->lastItem(),
            'total' => $this->paginator->total(),
            'items_per_page' => $this->paginator->perPage(),
        ];
    }
}
