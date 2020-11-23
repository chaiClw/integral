<?php
namespace integral\model;

class IntegralOrderModel extends BaseModel
{
    protected $table = 'library.integral_order';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }

    protected $pk = 'id';
}
