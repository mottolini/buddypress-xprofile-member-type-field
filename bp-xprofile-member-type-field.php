<?php
/*
    Plugin Name: BuddyPress Xprofile Member Type Field
    Plugin URI: https://github.com/mottolini/buddypress-xprofile-member-type-field
    Description: BuddyPress installation required!! This plugin provides a field that allows to select one of member types
    Version: 1.0.0
    Author: mottolini
    Author URI: https://github.com/mottolini
*/
if (!class_exists('Bxmtf_Plugin'))
{
    class Bxmtf_Plugin
    {
        private $version;
        private $user_id = null;

        public function __construct ()
        {
            $this->version = "1.0.0";

            /** Main hooks **/
            add_action( 'plugins_loaded', array($this, 'bxmtf_update') );

            /** Admin hooks **/
            add_action( 'admin_init', array($this, 'admin_init') );
            add_action( 'admin_notices', array($this, 'admin_notices') );

            /** Buddypress hook **/
            add_action( 'bp_init', array($this, 'init') );
            //add_action( 'bp_signup_validate', array($this, 'bxmtf_signup_validate') );
            //add_action( 'xprofile_data_before_save', array($this, 'bxmtf_xprofile_data_before_save') );
            add_action( 'xprofile_data_after_save', array($this, 'bxmtf_xprofile_data_after_save') );
            //add_action( 'xprofile_data_after_delete', array($this, 'bxmtf_xprofile_data_after_delete') );

            /** Filters **/
            add_filter( 'bp_xprofile_get_field_types', array($this, 'bxmtf_get_field_types'), 10, 1 );      //enumerates the field types
            add_filter( 'xprofile_get_field_data', array($this, 'bxmtf_get_field_data'), 10, 2 );
            //add_filter( 'bp_get_the_profile_field_value', array($this, 'bxmtf_get_field_value'), 6, 3 );
            /** BP Profile Search Filters **/
            add_filter ('bps_field_validation_type', array($this, 'bxmtf_map'), 10, 2);
            add_filter ('bps_field_html_type', array($this, 'bxmtf_map'), 10, 2);
            add_filter ('bps_field_criteria_type', array($this, 'bxmtf_map'), 10, 2);
            add_filter ('bps_field_query_type', array($this, 'bxmtf_map'), 10, 2);
        }

        public function init()
        {
            /** Includes **/
            require_once( 'classes/Bxmtf_Field_Type_MemberType.php' );
        }

        public function admin_init()
        {
            if (is_admin() && get_option('bxmtf_activated') == 1) {
                // Check if BuddyPress 2.2 is installed.
                $version_bp = 0;
                if (function_exists('is_plugin_active') && is_plugin_active('buddypress/bp-loader.php')) {
                    // BuddyPress loaded.
                    $data = get_file_data(WP_PLUGIN_DIR . '/buddypress/bp-loader.php', array('Version'));
                    if (isset($data) && count($data) > 0 && $data[0] != '') {
                        $version_bp = (float)$data[0];
                    }
                }
                if ($version_bp < 2.2) {
                    $notices = get_option('bxmtf_notices');
                    $notices[] = __('BuddyPress Xprofile Member Type Field plugin needs <b>BuddyPress 2.2</b>, please install or upgrade BuddyPress.', 'bxmtf');
                    update_option('bxmtf_notices', $notices);
                    delete_option('bxmtf_activated');
                }

            }
        }

        public function admin_notices()
        {
            $notices = get_option('bxmtf_notices');
            if ($notices) {
                foreach ($notices as $notice)
                {
                    echo "<div class='error'><p>$notice</p></div>";
                }
                delete_option('bxmtf_notices');
            }
        }

        public function bxmtf_get_field_types($fields)
        {
            $new_fields = array(
                'member_type'                   => 'Bxmtf_Field_Type_MemberType',
            );
            $fields = array_merge($fields, $new_fields);

            return $fields;
        }

        public function bxmtf_get_field_data($value, $field_id)
        {
            $field = new BP_XProfile_Field($field_id);
            $value_to_return = strip_tags($value);
            if ($value_to_return !== '') {
                // Member Type
                if ($field->type == 'member_type') {
                    $user_id = bp_displayed_user_id();
                    $value_to_return = bp_get_member_type($user_id);
                } else {
                    // Not stripping tags.
                    $value_to_return = $value;
                }
            }

            return apply_filters('bxmtf_show_field_value', $value_to_return, $field->type, $field_id, $value);
        }

        public function bxmtf_get_field_value($value='', $type='', $id='')
        {
            if ($type == 'member_type') {
                //$value = 'Model122';
                $member_type = bp_get_member_types( array('name' => $value), 'objects');
                $search_url   = add_query_arg( array( 's' => urlencode( $value ) ), bp_get_members_directory_permalink() );
                $value = '<a href="' . esc_url( $search_url ) . '" rel="nofollow">' . $member_type[$value]->labels['singular_name'] . '</a>';
            }
            return $value;

        }

        public function bxmtf_signup_validate() {
        }

        function bxmtf_xprofile_data_after_save($data)
        {
            $field_id = $data->field_id;
            $field = new BP_XProfile_Field($field_id);

            if ($field->type == 'member_type')
            {
                $member_type = bp_set_member_type( $data->user_id, $data->value );
            }
        }

        public function bxmtf_xprofile_data_after_delete($data) {
        }

        public function bxmtf_map($field_type, $field)
        {
            switch($field_type) {
                case 'member_type':
                    $field_type = 'selectbox';
                    break;
            }

            return $field_type;
        }

        public function bxmtf_update()
        {
            $locale = apply_filters( 'bxmtf_load_load_textdomain_get_locale', get_locale() );
            if ( !empty( $locale ) ) {
                $mofile_default = sprintf( '%slang/%s.mo', plugin_dir_path(__FILE__), $locale );
                $mofile = apply_filters( 'bxmtf_load_textdomain_mofile', $mofile_default );

                if ( file_exists( $mofile ) ) {
                    load_textdomain( "bxmtf", $mofile );
                }
            }

            if (!get_option('bxmtf_activated')) {
                add_option('bxmtf_activated', 1);
            }
            if (!get_option('bxmtf_notices')) {
                add_option('bxmtf_notices');
            }
        }

        public static function activate()
        {
            add_option('bxmtf_activated', 1);
            add_option('bxmtf_notices', array());
        }

        public static function deactivate()
        {
            delete_option('bxmtf_activated');
            delete_option('bxmtf_notices');
        }
    }
}

if (class_exists('Bxmtf_Plugin')) {
    register_activation_hook(__FILE__, array('Bxmtf_Plugin', 'activate'));
    register_deactivation_hook(__FILE__, array('Bxmtf_Plugin', 'deactivate'));
    $bxmtf_plugin = new Bxmtf_Plugin();
}