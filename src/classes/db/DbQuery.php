<?php

/**
 * SQL query builder
 *
 * @since 1.9.1.0
 */
class DbQuery {

    public $dbprefix;

    public function __construct($dbprefix = _DB_PREFIX_) {

        $this->dbprefix = $dbprefix;

    }

    /**
     * List of data to build the query
     *
     * @var array
     *
     * @since 1.9.1.0
     * @version 1.8.1.0 Initial version
     */
    protected $query = [
        'type'   => 'SELECT',
        'select' => [],
        'delete' => [],
        'set'    => [],
        'fields' => [],
        'values' => [],
        'from'   => [],
        'join'   => [],
        'where'  => [],
        'group'  => [],
        'having' => [],
        'order'  => [],
        'limit'  => ['offset' => 0, 'limit' => 0],
        'args'   => [],
    ];

    public function type($type) {

        $types = ['SELECT', 'DELETE', 'UPDATE', 'INSERT', 'REPLACE'];

        if (!empty($type) && in_array($type, $types)) {
            $this->query['type'] = $type;
        }

        return $this;
    }

    public function select($fields) {

        if (!empty($fields)) {
            $this->query['select'][] = $fields;
        }

        return $this;
    }

    public function delete($fields) {

        if (!empty($fields)) {
            $this->query['delete'][] = $fields;
        }

        return $this;
    }

    public function insert($args) {

        if (!empty($args)) {
            $this->query['insert'][] = $args;
        }

        return $this;
    }

    public function fields($fields) {

        if (!empty($fields)) {
            $this->query['fields'][] = $fields;
        }

        return $this;
    }

    public function values($fields) {

        if (!empty($fields)) {
            $this->query['values'][] = $fields;
        }

        return $this;
    }

    public function args($args) {

        if (!empty($args)) {
            $this->query['args'][] = $args;
        }

        return $this;
    }

    public function from($table, $alias = null, $table2 = null, $alias2 = null) {

        if (!empty($table)) {

            if (strncmp($this->dbprefix, $table, strlen($this->dbprefix)) !== 0) {
                $table = $this->dbprefix . $table;
            }

            if (empty($this->query['from'])) {
                $this->query['from'] = [];
            }

            if (!empty($table2)) {

                if (strncmp($this->dbprefix, $table2, strlen($this->dbprefix)) !== 0) {
                    $table2 = $this->dbprefix . $table2;
                }

            }

            $this->query['from'][] = '`' . bqSQL($table) . '`' . ($alias ? ' ' . $alias : '');

            if (!empty($table2)) {

                if (strncmp($this->dbprefix, $table2, strlen($this->dbprefix)) !== 0) {
                    $table2 = $this->dbprefix . $table2;
                }

                $this->query['from'][] .= ' `' . bqSQL($table2) . '`' . ($alias2 ? ' ' . $alias2 : '');
            }

        }

        return $this;
    }

    public function join($join) {

        if (!empty($join)) {
            $this->query['join'][] = $join;
        }

        return $this;
    }

    public function leftJoin($table, $alias = null, $on = null) {

        if (strncmp($this->dbprefix, $table, strlen($this->dbprefix)) !== 0) {
            $table = $this->dbprefix . $table;
        }

        return $this->join('LEFT JOIN `' . bqSQL($table) . '`' . ($alias ? ' `' . pSQL($alias) . '`' : '') . ($on ? ' ON ' . $on : ''));
    }

    public function innerJoin($table, $alias = null, $on = null) {

        if (strncmp($this->dbprefix, $table, strlen($this->dbprefix)) !== 0) {
            $table = $this->dbprefix . $table;
        }

        return $this->join('INNER JOIN `' . bqSQL($table) . '`' . ($alias ? ' ' . pSQL($alias) : '') . ($on ? ' ON ' . $on : ''));
    }

    public function leftOuterJoin($table, $alias = null, $on = null) {

        if (strncmp($this->dbprefix, $table, strlen($this->dbprefix)) !== 0) {
            $table = $this->dbprefix . $table;
        }

        return $this->join('LEFT OUTER JOIN `' . bqSQL($table) . '`' . ($alias ? ' ' . pSQL($alias) : '') . ($on ? ' ON ' . $on : ''));
    }

    public function naturalJoin($table, $alias = null) {

        if (strncmp($this->dbprefix, $table, strlen($this->dbprefix)) !== 0) {
            $table = $this->dbprefix . $table;
        }

        return $this->join('NATURAL JOIN `' . bqSQL($table) . '`' . ($alias ? ' ' . pSQL($alias) : ''));
    }

    public function rightJoin($table, $alias = null, $on = null) {

        if (strncmp($this->dbprefix, $table, strlen($this->dbprefix)) !== 0) {
            $table = $this->dbprefix . $table;
        }

        return $this->join('RIGHT JOIN `' . bqSQL($table) . '`' . ($alias ? ' `' . pSQL($alias) . '`' : '') . ($on ? ' ON ' . $on : ''));
    }

    public function straightJoin($table, $alias = null, $on = null) {

        if (strncmp($this->dbprefix, $table, strlen($this->dbprefix)) !== 0) {
            $table = $this->dbprefix . $table;
        }

        return $this->join('STRAIGHT_JOIN `' . bqSQL($table) . '`' . ($alias ? ' `' . pSQL($alias) . '`' : '') . ($on ? ' ON ' . $on : ''));
    }

    public function set($fields) {

        if (!empty($fields)) {
            $this->query['set'][] = $fields;
        }

        return $this;
    }

    public function where($restriction) {

        if (!empty($restriction)) {
            $this->query['where'][] = $restriction;
        }

        return $this;
    }

    public function having($restriction) {

        if (!empty($restriction)) {
            $this->query['having'][] = $restriction;
        }

        return $this;
    }

    public function orderBy($fields) {

        if (!empty($fields)) {
            $this->query['order'][] = $fields;
        }

        return $this;
    }

    public function groupBy($fields) {

        if (!empty($fields)) {
            $this->query['group'][] = $fields;
        }

        return $this;
    }

    public function limit($limit, $offset = 0) {

        $offset = (int) $offset;

        if ($offset < 0) {
            $offset = 0;
        }

        $this->query['limit'] = [
            'offset' => $offset,
            'limit'  => (int) $limit,
        ];

        return $this;
    }

    public function build() {

        if ($this->query['type'] == 'SELECT') {
            $sql = 'SELECT ' . ((($this->query['select'])) ? implode(",\n", $this->query['select']) : '*') . "\n";
        } else

        if ($this->query['type'] == 'DELETE') {
            $sql = 'DELETE ' . (($this->query['delete']) ? implode(",\n", $this->query['delete']) : '') . "\n";
        } else

        if ($this->query['type'] == 'INSERT') {
            $sql = 'INSERT ' . (isset($this->query['insert']) ? implode(",\n", $this->query['insert']) : '') . "\n";
        } else

        if ($this->query['type'] == 'REPLACE') {
            $sql = 'REPLACE ' . (isset($this->query['insert']) ? implode(",\n", $this->query['insert']) : '') . "\n";
        } else {
            $sql = $this->query['type'] . ' ';
        }

        if (!$this->query['from']) {
            throw new PhenyxException('Table name not set in DbQuery object. Cannot build a valid SQL query.');
        }

        if ($this->query['type'] == 'UPDATE') {
            $sql .= implode(', ', $this->query['from']) . ' SET ' . implode(', ', $this->query['set']) . "\n";
        } else

        if ($this->query['type'] == 'INSERT' || $this->query['type'] == 'REPLACE') {
            $sql .= 'INTO ' . implode(', ', $this->query['from']) . ' (' . implode(', ', $this->query['fields']) . ') VALUES (' . implode(', ', $this->query['values']) . ') ' . "\n";
        } else {
            $sql .= 'FROM ' . implode(', ', $this->query['from']) . "\n";
        }

        if ($this->query['join']) {
            $sql .= implode("\n", $this->query['join']) . "\n";
        }

        if ($this->query['where']) {
            $sql .= 'WHERE (' . implode(') AND (', $this->query['where']) . ")\n";
        }

        if ($this->query['group']) {
            $sql .= 'GROUP BY ' . implode(', ', $this->query['group']) . "\n";
        }

        if ($this->query['having']) {
            $sql .= 'HAVING (' . implode(') AND (', $this->query['having']) . ")\n";
        }

        if ($this->query['order']) {
            $sql .= 'ORDER BY ' . implode(', ', $this->query['order']) . "\n";
        }

        if ($this->query['limit']['limit']) {
            $limit = $this->query['limit'];
            $sql .= 'LIMIT ' . ($limit['offset'] ? $limit['offset'] . ', ' : '') . $limit['limit'];
        }

        if ($this->query['args']) {
            $sql .= "\n" . implode(', ', $this->query['args']);
        }

        return $sql;
    }

    public function __toString() {

        return $this->build();
    }

}
