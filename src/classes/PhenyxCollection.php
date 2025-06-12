<?php

/**
 * Class PhenyxCollection
 *
 * @since 1.9.1.0
 */
class PhenyxCollection implements Iterator, ArrayAccess, Countable {
	
    const LEFT_JOIN = 1;
    const INNER_JOIN = 2;
    const LEFT_OUTER_JOIN = 3;
    const LANG_ALIAS = 'l';
    const META_ALIAS = 'm';

    // @codingStandardsIgnoreStart
    /**
     * @var string Object class name
     */
    protected $classname;
    /**
     * @var int
     */
    protected $id_lang;
    /**
     * @var array Object definition
     */
    protected $definition = [];
    /**
     * @var DbQuery
     */
    protected $query;
    /**
     * @var array Collection of objects in an array
     */
    protected $results = [];
    /**
     * @var bool Is current collection already hydrated
     */
    protected $is_hydrated = false;
    /**
     * @var int Collection iterator
     */
    protected $iterator = 0;
    /**
     * @var int Total of elements for iteration
     */
    protected $total;
    /**
     * @var int Page number
     */
    protected $page_number = 0;
    /**
     * @var int Size of a page
     */
    protected $page_size = 0;
    protected $fields = [];
    protected $alias = [];
    protected $alias_iterator = 0;
    protected $join_list = [];
    protected $association_definition = [];
    protected $association_meta = [];
    
    public function __construct($classname, $idLang = null) {

        $this->classname = $classname;
        $this->id_lang = $idLang;

        $this->definition = PhenyxObjectModel::getDefinition($this->classname);

        if (!isset($this->definition['table'])) {
            throw new PhenyxException('Miss table in definition for class ' . $this->classname);
        } else
        if (!isset($this->definition['primary'])) {
            throw new PhenyxException('Miss primary in definition for class ' . $this->classname);
        }

        $this->query = new DbQuery();
    }
    
    public function sqlWhere($sql) {

        $this->query->where($this->parseFields($sql));

        return $this;
    }
    
    protected function parseFields($str) {

        preg_match_all('#\{(([a-z0-9_]+\.)*[a-z0-9_]+)\}#i', $str, $m);

        for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
            $str = str_replace($m[0][$i], $this->parseField($m[1][$i]), $str);
        }

        return $str;
    }
    
    protected function parseField($field) {

        $info = $this->getFieldInfo($field);

        return $info['alias'] . '.`' . $info['name'] . '`';
    }

    protected function getFieldInfo($field) {

        if (!isset($this->fields[$field])) {
            $split = explode('.', $field);
            $total = count($split);

            if ($total > 1) {
                $fieldname = $split[$total - 1];
                unset($split[$total - 1]);
                $association = implode('.', $split);
            } else {
                $fieldname = $field;
                $association = '';
            }

            $definition = $this->getDefinition($association);

            if ($association && !isset($this->join_list[$association])) {
                $this->join($association);
            }

            if ($fieldname == $definition['primary'] || (!empty($definition['is_lang']) && $fieldname == 'id_lang')) {
                $type = PhenyxObjectModel::TYPE_INT;
            } else {
                // Test if field exists

                if (!isset($definition['fields'][$fieldname])) {
                    throw new PhenyxException('Field ' . $fieldname . ' not found in class ' . $definition['classname']);
                }

                // Test field validity for language fields

                if (empty($definition['is_lang']) && !empty($definition['fields'][$fieldname]['lang'])) {
                    throw new PhenyxException('Field ' . $fieldname . ' is declared as lang field but is used in non multilang context');
                } else
                if (!empty($definition['is_lang']) && empty($definition['fields'][$fieldname]['lang'])) {
                    throw new PhenyxException('Field ' . $fieldname . ' is not declared as lang field but is used in multilang context');
                }

                $type = $definition['fields'][$fieldname]['type'];
            }

            $this->fields[$field] = [
                'name'        => $fieldname,
                'association' => $association,
                'alias'       => $this->generateAlias($association),
                'type'        => $type,
            ];
        }

        return $this->fields[$field];
    }

    public function getDefinition($association) {
        
        if (!$association) {
            return $this->definition;
        }

        if (!isset($this->association_definition[$association])) {
            $definition = $this->definition;
            $split = explode('.', $association);
            $isLang = false;

            for ($i = 0, $totalAssociation = count($split); $i < $totalAssociation; $i++) {
                $asso = $split[$i];

                // Check is current association exists in current definition

                if (!isset($definition['associations'][$asso])) {
                    throw new PhenyxException('Association ' . $asso . ' not found for class ' . $this->definition['classname']);
                }

                $currentDef = $definition['associations'][$asso];

                // Special case for lang alias

                if ($asso == static::LANG_ALIAS) {
                    $isLang = true;
                    break;
                }

                $classname = (isset($currentDef['object'])) ? $currentDef['object'] : Tools::toCamelCase($asso, true);
                $definition = PhenyxObjectModel::getDefinition($classname);
            }

            // Get definition of associated entity and add information on current association
            $currentDef['name'] = $asso;

            if (!isset($currentDef['object'])) {
                $currentDef['object'] = Tools::toCamelCase($asso, true);
            }

            if (!isset($currentDef['field'])) {
                $currentDef['field'] = 'id_' . $asso;
            }

            if (!isset($currentDef['foreign_field'])) {
                $currentDef['foreign_field'] = 'id_' . $asso;
            }

            if ($totalAssociation > 1) {
                unset($split[$totalAssociation - 1]);
                $currentDef['complete_field'] = implode('.', $split) . '.' . $currentDef['field'];
            } else {
                $currentDef['complete_field'] = $currentDef['field'];
            }

            $currentDef['complete_foreign_field'] = $association . '.' . $currentDef['foreign_field'];

            $definition['is_lang'] = $isLang;
            $definition['asso'] = $currentDef;
            $this->association_definition[$association] = $definition;
        } else {
            $definition = $this->association_definition[$association];
        }

        return $definition;
    }

    public function join($association, $on = '', $type = null) {

        if (!$association) {
            return false;
        }

        if (!isset($this->join_list[$association])) {
            $definition = $this->getDefinition($association);
            $on = '{' . $definition['asso']['complete_field'] . '} = {' . $definition['asso']['complete_foreign_field'] . '}';
            $type = static::LEFT_JOIN;
            $this->join_list[$association] = [
                'table' => ($definition['is_lang']) ? $definition['table'] . '_lang' : $definition['table'],
                'alias' => $this->generateAlias($association),
                'on'    => [],
            ];
        }

        if ($on) {
            $this->join_list[$association]['on'][] = $this->parseFields($on);
        }

        if ($type) {
            $this->join_list[$association]['type'] = $type;
        }

        return $this;
    }

    protected function generateAlias($association = '') {

        if (!isset($this->alias[$association])) {
            $this->alias[$association] = 'a' . $this->alias_iterator++;
        }

        return $this->alias[$association];
    }

    public function having($field, $operator, $value) {

        return $this->where($field, $operator, $value, 'having');
    }

    public function where($field, $operator, $value, $method = 'where') {

        if ($method != 'where' && $method != 'having') {
            throw new PhenyxException('Bad method argument for where() method (should be "where" or "having")');
        }

        // Create WHERE clause with an array value (IN, NOT IN)

        if (is_array($value)) {

            switch (strtolower($operator)) {
            case '=':
            case 'in':
                $this->query->$method($this->parseField($field) . ' IN(' . implode(', ', $this->formatValue($value, $field)) . ')');
                break;

            case '!=':
            case '<>':
            case 'notin':
                $this->query->$method($this->parseField($field) . ' NOT IN(' . implode(', ', $this->formatValue($value, $field)) . ')');
                break;

            default:
                throw new PhenyxException('Operator not supported for array value');
            }

        }

        // Create WHERE clause
        else {

            switch (strtolower($operator)) {
            case '=':
            case '!=':
            case '<>':
            case '>':
            case '>=':
            case '<':
            case '<=':
            case 'like':
            case 'regexp':
                $this->query->$method($this->parseField($field) . ' ' . $operator . ' ' . $this->formatValue($value, $field));
                break;

            case 'notlike':
                $this->query->$method($this->parseField($field) . ' NOT LIKE ' . $this->formatValue($value, $field));
                break;

            case 'notregexp':
                $this->query->$method($this->parseField($field) . ' NOT REGEXP ' . $this->formatValue($value, $field));
                break;
            default:
                throw new PhenyxException('Operator not supported');
            }

        }

        return $this;
    }

    protected function formatValue($value, $field) {

        $info = $this->getFieldInfo($field);

        if (is_array($value)) {
            $results = [];

            foreach ($value as $item) {
                $results[] = PhenyxObjectModel::formatValue($item, $info['type'], true);
            }

            return $results;
        }

        return PhenyxObjectModel::formatValue($value, $info['type'], true);
    }

    public function sqlHaving($sql) {

        $this->query->having($this->parseFields($sql));

        return $this;
    }

    public function orderBy($field, $order = 'asc') {

        $order = strtolower($order);

        if ($order != 'asc' && $order != 'desc') {
            throw new PhenyxException('Order must be asc or desc');
        }

        $this->query->orderBy($this->parseField($field) . ' ' . $order);

        return $this;
    }

    public function sqlOrderBy($sql) {

        $this->query->orderBy($this->parseFields($sql));

        return $this;
    }

    public function groupBy($field) {

        $this->query->groupBy($this->parseField($field));

        return $this;
    }

    public function sqlGroupBy($sql) {

        $this->query->groupBy($this->parseFields($sql));

        return $this;
    }

    public function getFirst() {

        $this->getAll();

        if (!count($this)) {
            return false;
        }

        return $this[0];
    }

    public function getAll($displayQuery = false) {

        
        if ($this->is_hydrated) {
            return $this;
        }

        $this->is_hydrated = true;

        $alias = $this->generateAlias();
        if (!empty($this->definition['have_meta'])) {
            $alias = 'a';
        }
        //$this->query->select($alias.'.*');
        $this->query->from($this->definition['table'], $alias);

        // If multilang, create association to lang table

        if (!empty($this->definition['multilang'])) {
            $this->join(static::LANG_ALIAS);

            if ($this->id_lang) {
                $this->where(static::LANG_ALIAS . '.id_lang', '=', $this->id_lang);
            }

        }
        
        if (!empty($this->definition['have_meta'])) {
            $this->query->select('a.*,'.implode(', ', $this->definition['have_meta']['field']));
            $this->query->leftJoin($this->definition['table'].'_meta', static::META_ALIAS, 'a.`'.$this->definition['primary'] .'` = '.static::META_ALIAS.'.`'.$this->definition['primary'].'`');
        }

        // Add join clause

        foreach ($this->join_list as $data) {
            $on = '(' . implode(') AND (', $data['on']) . ')';

            switch ($data['type']) {
            case static::LEFT_JOIN:
                $this->query->leftJoin($data['table'], $data['alias'], $on);
                break;

            case static::INNER_JOIN:
                $this->query->innerJoin($data['table'], $data['alias'], $on);
                break;

            case static::LEFT_OUTER_JOIN:
                $this->query->leftOuterJoin($data['table'], $data['alias'], $on);
                break;
            }

        }

        // All limit clause

        if ($this->page_size) {
            $this->query->limit($this->page_size, $this->page_number * $this->page_size);
        }

        // Shall we display query for debug ?

        if ($displayQuery) {
            echo $this->query . '<br />';
        }
       

        $this->results = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($this->query);

        if ($this->results && is_array($this->results)) {
            $this->results = PhenyxObjectModel::hydrateCollection($this->classname, $this->results, $this->id_lang);
        }

        return $this;
    }

    public function getResults() {

        $this->getAll();

        return $this->results;
    }

	#[\ReturnTypeWillChange]
    public function rewind() {

        $this->getAll();
        $this->results = array_merge($this->results);
        $this->iterator = 0;
        $this->total = count($this->results);
    }

	#[\ReturnTypeWillChange]
    public function current() {
		
        return isset($this->results[$this->iterator]) ? $this->results[$this->iterator] : 0;
    }

	#[\ReturnTypeWillChange]
    public function valid() {

        return $this->iterator < $this->total;
    }

	#[\ReturnTypeWillChange]
    public function key() {

        return $this->iterator;
    }

	#[\ReturnTypeWillChange]
    public function next() {

        $this->iterator++;
    }

	#[\ReturnTypeWillChange]
    public function count() {

        $this->getAll();

        return count($this->results);
    }

	#[\ReturnTypeWillChange]
    public function offsetExists($offset) {

        $this->getAll();

        return isset($this->results[$offset]);
    }

	#[\ReturnTypeWillChange]
    public function offsetGet($offset) {

        $this->getAll();

        if (!isset($this->results[$offset])) {
            throw new PhenyxException('Unknown offset ' . $offset . ' for collection ' . $this->classname);
        }

        return $this->results[$offset];
    }

	#[\ReturnTypeWillChange]
    public function offsetSet($offset, $value) {

        if (!$value instanceof $this->classname) {
            throw new PhenyxException('You cannot add an element which is not an instance of ' . $this->classname);
        }

        $this->getAll();

        if (is_null($offset)) {
            $this->results[] = $value;
        } else {
            $this->results[$offset] = $value;
        }

    }

	#[\ReturnTypeWillChange]
    public function offsetUnset($offset) {

        $this->getAll();
        unset($this->results[$offset]);
    }

    public function setPageNumber($pageNumber) {

        $pageNumber = (int) $pageNumber;

        if ($pageNumber > 0) {
            $pageNumber--;
        }

        $this->page_number = $pageNumber;

        return $this;
    }

    public function setPageSize($pageSize) {

        $this->page_size = (int) $pageSize;

        return $this;
    }

}
