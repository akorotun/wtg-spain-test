<?php

namespace App\Dto;

class PropertySearchDto
{
    public function __construct(
        public ?string  $city = null,
        public $check_in,
        public $check_out,
        public int $guests,
        public ?int $page = null,
        public ?int $perPage = null,
    ){}

    public static function fromArray(array $data): self
    {
        return new self(
            city: $data['city'] ?? null,
            check_in: $data['check_in'],
            check_out: $data['check_out'],
            guests: $data['guests'],
            page: $data['page'] ?? 1,
            perPage: $data['per_page'] ?? 50,

        );
    }

}
