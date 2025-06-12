<?php

/**
 * Class PhenyxMaintenance
 *
 * This class implements tasks for maintaining hte ephenyx digital installation, to be
 * run on a regular schedule. It gets called by an asynchronous Ajax request
 * in DashboardController.
 *
 * @since 1.0.8
 */
class PhenyxMaintenance {

    /**
     * Run tasks as needed. Should take care of running tasks not more often
     * than needed and that one run takes not longer than a few seconds.
     *
     * This method gets triggered by the 'getNotifications' Ajax request, so
     * every two minutes while somebody has back office open.
     *
     * @since 1.0.8
     */
    public static function run() {

        $now = time();
        $lastRun = Context::getContext()->phenyxConfig->get('PHENYX_MAINTENANCE_LAST_RUN');

        if ($now - $lastRun > 86400) {
            // Run daily tasks.
            PhenyxTools::cleanBackTabs();
            PhenyxTools::cleanMetas();
            PhenyxTools::cleanPlugins();
            PhenyxTools::cleanHook();

            Context::getContext()->phenyxConfig->updateGlobalValue('PHENYX_MAINTENANCE_LAST_RUN', $now);
        }

    }
    
     public static function cleanUserAccount() {

        $context::getContext();
        $accounts = StdAccount::getCustomerStdAccount($context);

        foreach ($accounts as $account) {

            $id_user = Db::getInstance()->getValue(
                (new DbQuery())
                    ->select('`id_user`')
                    ->from('user')
                    ->where('`id_stdaccount` = ' . (int) $account->id)
            );

            if ($id_user > 0) {
                continue;
            }

            $account->delete();
        }

    }

    public static function cleanOutdatedUser() {

        $date = date('Y-m-d');
        $time = new DateTime($date);
        $time->modify('-5 year');
        $renewalDate = $time->format('Y-m-d');

        $customerToDeletes = [];

        $customers = Db::getInstance()->executeS(
            (new DbQuery())
                ->select('*')
                ->from('user')
                ->where('`last_connection_date` <= "' . (int) $renewalDate . '"')
        );

        foreach ($customers as $customer) {

            $orders = Db::getInstance()->executeS(
                (new DbQuery())
                    ->select('*')
                    ->from('customer_pieces')
                    ->where('`date_add` >= "' . (int) $renewalDate . '" AND `id_customer` = ' . $customer['id_customer'])
            );

            if (is_array($orders) && count($orders)) {
                continue;
            }

            $customerToDeletes[] = $customer['id_customer'];
        }

        foreach ($customerToDeletes as $id_customer) {
            $user = new Customer($id_customer);
            $user->delete();
        }

    }

    
}
