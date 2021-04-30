<?php

namespace App\Modules\WC;

/**
 * Вспомагательный  класс
 */
class Helper
{

    public static function getClient() {

        $modules = \App\System::getOptions("modules");

        $site = $modules['wcsite'];
        $keyc = $modules['wckeyc'];
        $keys = $modules['wckeys'];
        $api = $modules['wcapi'];
        //  $ssl = $modules['wcssl'];

        $ssl = \App\System::getSession()->wcssl == 1;

        $site = trim($site, '/') . '/';

        $woocommerce = new \Automattic\WooCommerce\Client(
            $site,
            $keyc,
            $keys,
            [
                'version'    => 'wc/' . $api,
                'verify_ssl' => $ssl
            ]
        );

        return $woocommerce;
    }

}
