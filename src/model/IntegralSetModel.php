<?php


namespace integral\model;


class IntegralSetModel extends BaseModel
{
    protected $table = 'library.integral_set';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}