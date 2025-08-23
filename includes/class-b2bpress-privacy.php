<?php
/**
 * B2BPress 隐私导出/擦除集成
 */
class B2BPress_Privacy {
    public function __construct() {
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_eraser'));
    }

    public function register_exporter($exporters): mixed {
        $exporters['b2bpress'] = array(
            'exporter_friendly_name' => __('B2BPress 用户首选语言', 'b2bpress'),
            'callback' => array($this, 'export_user_data'),
        );
        return $exporters;
    }

    public function register_eraser($erasers) {
        $erasers['b2bpress'] = array(
            'eraser_friendly_name' => __('B2BPress 用户首选语言', 'b2bpress'),
            'callback' => array($this, 'erase_user_data'),
        );
        return $erasers;
    }

    public function export_user_data($email_address, $page = 1) {
        $data_to_export = array();
        $done = true;

        $user = get_user_by('email', $email_address);
        if ($user) {
            $lang = get_user_meta($user->ID, 'b2bpress_language', true);
            if (!empty($lang)) {
                $data_to_export[] = array(
                    'group_id'    => 'b2bpress',
                    'group_label' => __('B2BPress', 'b2bpress'),
                    'item_id'     => "b2bpress-language-{$user->ID}",
                    'data'        => array(
                        array(
                            'name'  => __('首选语言', 'b2bpress'),
                            'value' => $lang,
                        ),
                    ),
                );
            }
        }

        return array(
            'data' => $data_to_export,
            'done' => $done,
        );
    }

    public function erase_user_data($email_address, $page = 1) {
        $items_removed  = false;
        $items_retained = false;
        $messages       = array();
        $done           = true;

        $user = get_user_by('email', $email_address);
        if ($user) {
            delete_user_meta($user->ID, 'b2bpress_language');
            $items_removed = true;
        }

        return array(
            'items_removed'  => $items_removed,
            'items_retained' => $items_retained,
            'messages'       => $messages,
            'done'           => $done,
        );
    }
}


