<?php

namespace integral\model;

class RankQuanModel extends BaseModel
{
    protected $table = 'library.rank_quan';

    public function __construct($data = []) {
        parent::__construct($data);
        $env = $_SERVER['ENV'] ?? '';
        $this->table = $env . $this->table;
    }
}