<?php

namespace App\Dto;

class PropertySearchDto
{
    public function __construct(
        public $check_in,
        public $check_out,
        public int $guests,
        public ?string $city = null,
        public ?int $page = null,
        public ?int $perPage = null,
    ){}

    public static function fromArray(array $data): self
    {
        return new self(
            check_in: $data['check_in'],
            check_out: $data['check_out'],
            guests: $data['guests'],
            city: $data['city'] ?? null,
            page: $data['page'] ?? 1,
            perPage: $data['per_page'] ?? 50,

        );
    }

}
