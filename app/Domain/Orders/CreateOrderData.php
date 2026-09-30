<?php

namespace App\Domain\Orders;

final class CreateOrderData
{
    /**
     * @param  array<int, array{product_id:int, quantity:int, observations?:?string}>  $items
     */
    public function __construct(
        public readonly int $restaurantId,
        public readonly int $userId,
        public readonly ?string $idempotencyKey = null,
        public readonly ?int $tableId = null,
        public readonly ?int $subsectorItemId = null,
        public readonly ?string $customerName = null,
        public readonly ?string $observations = null,
        public readonly array $items = [],
        public readonly bool $sendToKitchen = false,
        public readonly bool $ensureTableOccupied = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            restaurantId: (int) $data['restaurant_id'],
            userId: (int) $data['user_id'],
            idempotencyKey: $data['idempotency_key'] ?? null,
            tableId: isset($data['table_id']) ? (int) $data['table_id'] : null,
            subsectorItemId: isset($data['subsector_item_id']) ? (int) $data['subsector_item_id'] : null,
            customerName: $data['customer_name'] ?? null,
            observations: $data['observations'] ?? null,
            items: $data['items'] ?? [],
            sendToKitchen: (bool) ($data['send_to_kitchen'] ?? false),
            ensureTableOccupied: (bool) ($data['ensure_table_occupied'] ?? true),
        );
    }
}
