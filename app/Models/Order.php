<?php

namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class Order extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'order_id';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $allowedFields = [
    'sale_id',
    'customer_id',
    'employee_id',
    'table_no',
    'status',
    'order_type',
    'created_at',
    'notes'
];


    protected $order_id;
    protected $order_number;
    protected $waiter_name;
    protected $status;
    protected $total_amount;
    protected $created_at;

    /**
     * Get order by ID
     */
    public function get_order(int $order_id): array|Order|stdClass|null
    {
        $builder = $this->db->table($this->table);
        $builder->where('order_id', $order_id);
        $result = $builder->get()->getRow();

        if (empty($result)) {
            $result = model(Order::class);
            foreach ($this->db->getFieldNames($this->table) as $field) {
                $result->$field = '';
            }
        }

        return $result;
    }

    /**
     * Create or update an order
     */
    public function save_order(array $order_data, ?int $order_id = null): bool
    {
        if ($order_id) {
            $builder = $this->db->table($this->table);
            $builder->where('order_id', $order_id);
            return $builder->update($order_data);
        }

        $builder = $this->db->table($this->table);
        return $builder->insert($order_data);
    }

    /**
     * Delete order
     */
    public function delete_order(int $order_id): bool
    {
        $builder = $this->db->table($this->table);
        $builder->where('order_id', $order_id);
        return $builder->delete();
    }
    public function get_orders()
{
    $builder = $this->db->table('ospos_orders');
    
    $builder->select("
        ospos_orders.order_id,
        ospos_orders.table_no,
        ospos_orders.order_time,
        CONCAT(ospos_people.first_name, ' ', ospos_people.last_name) AS employee_name,
        TIMESTAMPDIFF(MINUTE, ospos_orders.order_time, NOW()) AS running_time_minutes
    ");
    
    // join employees → people to fetch names
    $builder->join('ospos_employees', 'ospos_employees.person_id = ospos_orders.employee_id', 'left');
    $builder->join('ospos_people', 'ospos_people.person_id = ospos_employees.person_id', 'left');

    $builder->where('ospos_orders.status', 'pending');
    $builder->orderBy('ospos_orders.order_time', 'ASC');

    return $builder->get()->getResultArray();
}
public function get_order_with_items($order_id)
{
    $order = [];

    $order['info'] = $this->db->get_where('orders', ['order_id' => $order_id])->row_array();

    $this->db->select('oi.*, i.name, i.unit_price as price');
    $this->db->from('order_items as oi');
    $this->db->join('items as i', 'i.item_id = oi.item_id', 'left');
    $this->db->where('oi.order_id', $order_id);
    $order['items'] = $this->db->get()->result_array();

    return $order;
}


}

