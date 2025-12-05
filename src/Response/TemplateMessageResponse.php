<?php

namespace Dialog360\Response;

class TemplateMessageResponse
{

    private int $count;

    private array $filters;

    private int $limit;

    private int $offset;

    private array $sort;

    private int $total;

    private array $waba_templates;

    public function __construct(array $data)
    {

        $this->count = $data['count'] ?? 0;
        $this->filters = $data['filters'] ?? [];
        $this->limit = $data['limit'] ?? 0;
        $this->offset = $data['offset'] ?? 0;
        $this->sort = $data['sort'] ?? [];
        $this->total = $data['total'] ?? 0;
        $this->waba_templates = $data['waba_templates'] ?? [];

    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getSort(): array
    {
        return $this->sort;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getWabaTemplates(): array
    {
        return $this->waba_templates;
    }

} 