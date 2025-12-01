<?php

namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $primaryKey = 'item_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
    'order_id',
    'item_id',
    'description',
    'quantity',
    'unit_price',
    'discount_percent',
    'line'
];

    protected $item_id;
    protected $order_id;
    protected $item_name;
    protected $quantity;
    protected $price;
    protected $total;

    /**
     * Get items of a specific order
     */
    public function get_order_items(int $order_id): array
    {
        $builder = $this->db->table($this->table);
        $builder->where('order_id', $order_id);
        return $builder->get()->getResult();
    }

    /**
     * Save or update item
     */
    public function save_item(array $item_data, ?int $item_id = null): bool
    {
        if ($item_id) {
            $builder = $this->db->table($this->table);
            $builder->where('item_id', $item_id);
            return $builder->update($item_data);
        }

        $builder = $this->db->table($this->table);
        return $builder->insert($item_data);
    }

    /**
     * Delete items by order
     */
    public function delete_items_by_order(int $order_id): bool
    {
        $builder = $this->db->table($this->table);
        $builder->where('order_id', $order_id);
        return $builder->delete();
    }
    // In App\Models\OrderItem.php
public function delete_item_from_order(int $order_id, int $item_id): bool
{
    $builder = $this->db->table($this->table);
    $builder->where('order_id', $order_id);
    $builder->where('item_id', $item_id);
    return $builder->delete();
}

}
