<?php

/**
 * Class PhenyxStat
 *
 * @since 1.9.1.0
 */
class PhenyxStats {

    protected static $instance;

    public $context;

    public $_session;

    public function __construct($id = null, $idLang = null) {

        $this->context = Context::getContext();
        $this->_session = PhenyxSession::getInstance();

    }

    public static function getInstance() {

        if (!isset(static::$instance)) {
            static::$instance = new PhenyxStats();
        }

        return static::$instance;
    }

    public function getVisits($dateFrom, $dateTo, $granularity = false, $unique = false) {

        $visits = ($granularity == false) ? 0 : [];

        if ($granularity == 'day') {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('LEFT(`date_add`, 10) as date, COUNT(' . ($unique ? 'DISTINCT id_guest' : '*') . ') as visits')
                    ->from('connections')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                    ->groupBy('LEFT(`date_add`, 10)')
            );

            foreach ($result as $row) {
                $visits[$row['date']] = $row['visits'];
            }

        } else

        if ($granularity == 'month') {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('LEFT(`date_add`, 7) as date, COUNT(' . ($unique ? 'DISTINCT id_guest' : '*') . ') as visits')
                    ->from('connections')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                    ->groupBy('LEFT(`date_add`, 7)')
            );

            foreach ($result as $row) {
                $visits[$row['date']] = $row['visits'];
            }

        } else {
            $visits = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('COUNT(' . ($unique ? 'DISTINCT id_guest' : '*') . ') as visits')
                    ->from('connections')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
            );

        }

        return $visits;
    }

    public function getOrders($dateFrom, $dateTo, $granularity = false) {

        if ($granularity == 'day') {
            $orders = [];

            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('LEFT(`date_add`, 10) as date, COUNT(*) as orders')
                    ->from('customer_pieces')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                    ->where('`piece_type` = "INVOICE"')
                    ->where('`validate` = 1')
                    ->groupBy('LEFT(`date_add`, 10)')
            );

            foreach ($result as $row) {
                $orders[strtotime($row['date'])] = $row['orders'];
            }

            return $orders;
        } else

        if ($granularity == 'month') {
            $orders = [];
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('LEFT(`date_add`, 7) as date, COUNT(*) as orders')
                    ->from('customer_pieces')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                    ->where('`piece_type` = "INVOICE"')
                    ->where('`validate` = 1')
                    ->groupBy('LEFT(`date_add`, 7)')
            );

            foreach ($result as $row) {
                $orders[strtotime($row['date'] . '-01')] = $row['orders'];
            }

            return $orders;
        } else {
            $orders = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('COUNT(*) as orders')
                    ->from('customer_pieces')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                    ->where('`validate` = 1')
            );
        }

        return $orders;
    }

    public function getAbandonedCarts($dateFrom, $dateTo) {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(DISTINCT id_guest)')
                ->from('cart')
                ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . '" AND "' . pSQL($dateTo) . '"')
                ->where('NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'customer_pieces` WHERE `' . _DB_PREFIX_ . 'customer_pieces`.id_cart = `' . _DB_PREFIX_ . 'cart`.id_cart)')
        );

    }

    public function getTotalSales($dateFrom, $dateTo, $granularity = false) {

        if ($granularity == 'day') {
            $sales = [];

            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('LEFT(`date_add`, 10) as date, SUM(total_tax_excl / conversion_rate) as sales')
                    ->from('customer_pieces')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                    ->where('`piece_type` = "INVOICE"')
                    ->where('`validate` = 1')
                    ->groupBy('LEFT(`date_add`, 10)')
            );

            foreach ($result as $row) {
                $sales[strtotime($row['date'])] = $row['sales'];
            }

            return $sales;

        } else

        if ($granularity == 'month') {
            $sales = [];
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('LEFT(`date_add`, 7) as date, SUM(total_tax_excl / conversion_rate) as sales')
                    ->from('customer_pieces')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                    ->where('`piece_type` = "INVOICE"')
                    ->where('`validate` = 1')
                    ->groupBy('LEFT(`date_add`, 7)')
            );

            foreach ($result as $row) {
                $sales[strtotime($row['date'] . '-01')] = $row['sales'];
            }

            return $sales;
        } else {

            return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                (new DbQuery())
                    ->select('SUM(total_tax_excl / conversion_rate) as sales')
                    ->from('customer_pieces')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                    ->where('validate = 1')
            );
        }

    }

    public function getExpenses($dateFrom, $dateTo, $granularity = false) {

        $expenses = ($granularity == 'day' ? [] : 0);

        $orders = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('LEFT(o.`date_add`, 10) as date, total_paid / o.conversion_rate as total_paid_tax_incl, total_shipping_tax_excl / o.conversion_rate as total_shipping_tax_excl, o.plugin, a.id_country,      o.id_currency, c.id_reference as carrier_reference')
                ->from('customer_pieces', 'o')
                ->leftJoin('address', 'a', 'o.id_address_delivery = a.id_address')
                ->leftJoin('carrier', 'c', 'o.id_carrier = c.id_carrier')
                ->leftJoin('customer_piece_state', 'os', 'o.current_state = os.id_customer_piece_state')
                ->where('o.`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                ->where('o.validate = 1')
        );

        foreach ($orders as $order) {
            $plugin = null;

            if (isset($order['plugin'])) {
                $plugin = strtoupper(str_replace(' ', '_', $order['plugin']));
            }

            // Add flat fees for this order
            $flatFees = Context::getContext()->phenyxConfig->get('CONF_ORDER_FIXED') + (
                $order['id_currency'] == Context::getContext()->phenyxConfig->get('EPH_CURRENCY_DEFAULT')
                ? Context::getContext()->phenyxConfig->get('CONF_' . $plugin . '_FIXED')
                : Context::getContext()->phenyxConfig->get('CONF_' . $plugin . '_FIXED_FOREIGN')
            );

            // Add variable fees for this order
            $varFees = $order['total_paid_tax_incl'] * (
                $order['id_currency'] == Context::getContext()->phenyxConfig->get('EPH_CURRENCY_DEFAULT')
                ? Context::getContext()->phenyxConfig->get('CONF_' . $plugin . '_VAR')
                : Context::getContext()->phenyxConfig->get('CONF_' . $plugin . '_VAR_FOREIGN')
            ) / 100;

            // Add shipping fees for this order

            if (isset($order['carrier_reference'])) {
                $shippingFees = $order['total_shipping_tax_excl'] * (
                    $order['id_country'] == Context::getContext()->phenyxConfig->get('EPH_COUNTRY_DEFAULT')
                    ? Context::getContext()->phenyxConfig->get('CONF_' . strtoupper($order['carrier_reference']) . '_SHIP')
                    : Context::getContext()->phenyxConfig->get('CONF_' . strtoupper($order['carrier_reference']) . '_SHIP_OVERSEAS')
                ) / 100;
            } else {
                $shippingFees = $order['total_shipping_tax_excl'];
            }

            // Tally up these fees

            if ($granularity == 'day') {

                if (!isset($expenses[$order['date']])) {
                    $expenses[$order['date']] = 0;
                }

                $expenses[$order['date']] += $flatFees + $varFees + $shippingFees;
            } else {
                $expenses += $flatFees + $varFees + $shippingFees;
            }

        }

        return $expenses;
    }

    public function getPurchases($dateFrom, $dateTo, $granularity = false) {

        if ($granularity == 'day') {
            $purchases = [];
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->ExecuteS(
                '
            SELECT
                LEFT(`date_add`, 10) as date,
                SUM(od.`product_quantity` * IF(
                    od.`product_wholesale_price` > 0,
                    od.`product_wholesale_price` / `conversion_rate`,
                    od.`original_price_tax_excl` * ' . (int) Context::getContext()->phenyxConfig->get('CONF_AVERAGE_PRODUCT_MARGIN') . ' / 100
                )) as total_purchase_price
            FROM `' . _DB_PREFIX_ . 'customer_pieces` o
            LEFT JOIN `' . _DB_PREFIX_ . 'customer_piece_detail` od ON o.id_customer_piece = od.id_customer_piece
            LEFT JOIN `' . _DB_PREFIX_ . 'customer_piece_state` os ON o.current_state = os.id_customer_piece_state
            WHERE `date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59" AND os.logable = 1
            GROUP BY LEFT(`date_add`, 10)'
            );

            foreach ($result as $row) {
                $purchases[$row['date']] = $row['total_purchase_price'];
            }

            return $purchases;
        } else {
            return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
                '
            SELECT SUM(od.`product_quantity` * IF(
                    od.`product_wholesale_price` > 0,
                    od.`product_wholesale_price` / `conversion_rate`,
                    od.`original_price_tax_excl` * ' . (int) Context::getContext()->phenyxConfig->get('CONF_AVERAGE_PRODUCT_MARGIN') . ' / 100
                )) as total_purchase_price
            FROM `' . _DB_PREFIX_ . 'customer_pieces` o
            LEFT JOIN `' . _DB_PREFIX_ . 'customer_piece_detail` od ON o.id_customer_piece = od.id_customer_piece
            LEFT JOIN `' . _DB_PREFIX_ . 'customer_piece_state` os ON o.current_state = os.id_customer_piece_state
            WHERE `date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59" AND os.logable = 1'
            );

        }

    }

    public function getPercentProductStock() {

        $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('SUM(IF(IFNULL(stock.quantity, 0) > 0, 1, 0)) as with_stock, COUNT(*) as products')
                ->from('product', 'p')
                ->leftJoin('product_attribute', 'pa', 'p.id_product = pa.id_product')
                ->join(Product::sqlStock('p', 'pa'))
                ->where('p.active = 1')
        );

        return round($row['products'] ? 100 * $row['with_stock'] / $row['products'] : 0, 2) . '%';
    }

    public function getPercentProductOutOfStock() {

        $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
                ->select('SUM(IF(IFNULL(stock.quantity, 0) = 0, 1, 0)) as without_stock, COUNT(*) as products')
                ->from('product', 'p')
                ->leftJoin('product_attribute', 'pa', 'p.id_product = pa.id_product')
                ->join(Product::sqlStock('p', 'pa'))
                ->where('p.active = 1')
        );

        return round($row['products'] ? 100 * $row['without_stock'] / $row['products'] : 0, 2) . '%';
    }

    public function getProductAverageGrossMargin() {

        $value = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('AVG(1 - (IF(IFNULL(pa.wholesale_price, 0) = 0, p.wholesale_price,pa.wholesale_price) / (IFNULL(pa.price, 0) + p.price)))')
                ->from('product', 'p')
                ->leftJoin('product_attribute', 'pa', 'p.id_product = pa.id_product')
                ->where('p.active = 1')
        );

        return round(100 * $value, 2) . '%';
    }

    public function getDisabledCategories() {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('category')
                ->where('active = 0')
        );
    }

    public function getDisabledProducts() {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('product')
                ->where('active = 0')
        );
    }

    public function getTotalProducts() {

        return (int) Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            (new DbQuery())
                ->select('COUNT(*)')
                ->from('product')
        );
    }

    public function getInstalledPlugins() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            '
        SELECT COUNT(DISTINCT m.`id_plugin`)
        FROM `' . _DB_PREFIX_ . 'plugin` m
        '
        );
    }

    public function getDisabledPlugins() {

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            '
        SELECT COUNT(*)
        FROM `' . _DB_PREFIX_ . 'plugin` m
        WHERE m.active = 0'
        );
    }

    public function getCustomerMainGender() {

        $row = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getRow(
            '
        SELECT SUM(IF(g.id_gender IS NOT NULL, 1, 0)) as total, SUM(IF(type = 0, 1, 0)) as male, SUM(IF(type = 1, 1, 0)) as female, SUM(IF(type = 2, 1, 0)) as neutral
        FROM `' . _DB_PREFIX_ . 'user` c
        LEFT JOIN `' . _DB_PREFIX_ . 'gender` g ON c.id_gender = g.id_gender
        WHERE c.active = 1 '
        );

        if (!$row['total']) {
            return false;
        } else

        if ($row['male'] > $row['female'] && $row['male'] >= $row['neutral']) {
            return ['type' => 'male', 'value' => round(100 * $row['male'] / $row['total'])];
        } else

        if ($row['female'] >= $row['male'] && $row['female'] >= $row['neutral']) {
            return ['type' => 'female', 'value' => round(100 * $row['female'] / $row['total'])];
        }

        return ['type' => 'neutral', 'value' => round(100 * $row['neutral'] / $row['total'])];
    }

    public function getAverageCustomerAge() {

        $value = Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue(
            '
        SELECT AVG(DATEDIFF("' . date('Y-m-d') . ' 00:00:00", birthday))
        FROM `' . _DB_PREFIX_ . 'user` c
        WHERE active = 1
        AND birthday IS NOT NULL AND birthday != "0000-00-00" '
        );

        return round($value / 365);
    }

    public function getPendingMessages() {

        return CustomerThread::getTotalCustomerThreads('status LIKE "%pending%" OR status = "open"');
    }

    public function getAverageMessageResponseTime($dateFrom, $dateTo) {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            '
        SELECT MIN(cm1.date_add) as question, MIN(cm2.date_add) as reply
        FROM `' . _DB_PREFIX_ . 'customer_message` cm1
        INNER JOIN `' . _DB_PREFIX_ . 'customer_message` cm2 ON (cm1.id_customer_thread = cm2.id_customer_thread AND cm1.date_add < cm2.date_add)
        JOIN `' . _DB_PREFIX_ . 'customer_thread` ct ON (cm1.id_customer_thread = ct.id_customer_thread)
        WHERE cm1.`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"
        AND cm1.id_employee = 0 AND cm2.id_employee != 0
        GROUP BY cm1.id_customer_thread'
        );
        $totalQuestions = $totalReplies = $threads = 0;

        foreach ($result as $row) {
            ++$threads;
            $totalQuestions += strtotime($row['question']);
            $totalReplies += strtotime($row['reply']);
        }

        if (!$threads) {
            return 0;
        }

        return round(($totalReplies - $totalQuestions) / $threads / 3600, 1);
    }

    public function getMessagesPerThread($dateFrom, $dateTo) {

        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            '
        SELECT COUNT(*) as messages
        FROM `' . _DB_PREFIX_ . 'customer_thread` ct
        LEFT JOIN `' . _DB_PREFIX_ . 'customer_message` cm ON (ct.id_customer_thread = cm.id_customer_thread)
        WHERE ct.`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"
        AND status = "closed"
        GROUP BY ct.id_customer_thread'
        );
        $threads = $messages = 0;

        foreach ($result as $row) {
            ++$threads;
            $messages += $row['messages'];
        }

        if (!$threads) {
            return 0;
        }

        return round($messages / $threads, 1);
    }

    public function getEducationPurchases($dateFrom, $dateTo, $granularity = false) {

        if ($granularity == 'day') {

            $purchases = [];
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('LEFT(se.`date_add`, 10) as date, COUNT(id_student_education)*f.price  as cost')
                    ->from('student_education', 'se')
                    ->leftJoin('formatpack', 'f', 'f.id_formatpack = se.id_formatpack')
                    ->where('`id_student_education_state` = 9 AND se.`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
                    ->groupBy('LEFT(se.`date_add`, 10)')
            );

            foreach ($result as $row) {
                $purchases[strtotime($row['date'])] = $row['cost'];
            }

            return $purchases;

        } else {
            $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
                (new DbQuery())
                    ->select('SUM(total_tax_incl) as sales, SUM(piece_margin) as margin')
                    ->from('customer_pieces')
                    ->where('`date_add` BETWEEN "' . pSQL($dateFrom) . ' 00:00:00" AND "' . pSQL($dateTo) . ' 23:59:59"')
            );

            foreach ($result as $row) {
                $purchases[strtotime($row['date'])] = $row['sales'] - $row['margin'];
            }

            return $purchases;
        }

    }

    public function getPrevisionnel($dateFrom, $dateTo, $granularity = false) {

        $previsionnel = [];
        $result = Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS(
            (new DbQuery())
                ->select('LEFT(`date_add`, 10) as date, SUM(price) as amount')
                ->from('student_education')
                ->where('`id_student_education_state` > 3 AND `id_student_education_state` < 8 AND `date_add` BETWEEN "' . pSQL($This->context->company->accounting_period_start) . ' 00:00:00" AND "' . pSQL($this->context->company->accounting_period_end) . ' 23:59:59"')
                ->groupBy('LEFT(`date_add`, 10)')
        );

        foreach ($result as $row) {
            $previsionnel[strtotime($row['date'])] = $row['amount'];
        }

        return $previsionnel;

    }

}
