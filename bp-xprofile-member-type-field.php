<?php
/*
    Plugin Name: BuddyPress Xprofile Member Type Field
    Plugin URI: https://github.com/mottolini/buddypress-xprofile-member-type-field
    Description: BuddyPress installation required!! This plugin provides a field that allows to select one of member types
    Version: 0.0.1
    Author: mottolini
    Author URI: https://github.com/mottolini
*/
if (!class_exists('Bxmtf_Plugin'))
{
    class Bxmtf_Plugin
    {
        CONST BXMTF_MAX_FILESIZE = 8;

        private $version;
        private $user_id = null;
        private $images_ext_allowed;
        private $images_max_filesize;
        private $files_ext_allowed;
        private $files_max_filesize;

        public function __construct ()
        {
            $this->version = "0.0.1";

            /** Main hooks **/
            add_action( 'plugins_loaded', array($this, 'bxmtf_update') );

            /** Admin hooks **/
            add_action( 'admin_init', array($this, 'admin_init') );
            add_action( 'admin_notices', array($this, 'admin_notices') );

            /** Buddypress hook **/
            add_action( 'bp_init', array($this, 'init') );
            //add_action( 'bp_signup_validate', array($this, 'bxmtf_signup_validate') );
            add_action( 'xprofile_data_before_save', array($this, 'bxmtf_xprofile_data_before_save') );
            add_action( 'xprofile_data_after_delete', array($this, 'bxmtf_xprofile_data_after_delete') );

            /** Filters **/
            add_filter( 'bp_xprofile_get_field_types', array($this, 'bxmtf_get_field_types'), 10, 1 );
            add_filter( 'xprofile_get_field_data', array($this, 'bxmtf_get_field_data'), 10, 2 );
            add_filter( 'bp_get_the_profile_field_value', array($this, 'bxmtf_get_field_value'), 10, 3 );
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

                // Enqueue javascript.
                //wp_enqueue_script('bxmtf-js', plugin_dir_url(__FILE__) . 'js/admin.js', array(), $this->version, true);
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
                    $value_to_return = 1234;
                }
                // Web.
                elseif ($field->type == 'web') {
                    if (strpos($value_to_return, 'href=') === false) {
                        $value_to_return = sprintf('<a href="%s">%s</a>',
                            $value_to_return,
                            $value_to_return);
                    }
                } else {
                    // Not stripping tags.
                    $value_to_return = $value;
                }
            }

            return apply_filters('bxmtf_show_field_value', $value_to_return, $field->type, $field_id, $value);
        }

        public function bxmtf_get_field_value($value='', $type='', $id='')
        {
            if ($type == 'membertype') {
                $value = 'model';
            }
            return $value;
            $value_to_return = strip_tags($value);
            if ($value_to_return !== '') {
                // Birthdate.
                if ($type == 'birthdate') {
                    $show_age = false;
                    $field = new BP_XProfile_Field($id);
                    if ($field) {
                        $childs = $field->get_children();
                        if (isset($childs) && $childs && count($childs) > 0
                            && is_object($childs[0]) && $childs[0]->name == 'show_age') {
                            $show_age = true;
                        }
                    }
                    if ($show_age) {
                        $value_to_return = floor((time() - strtotime($value_to_return))/31556926);
                    } else {
                        $value_to_return = date_i18n(get_option('date_format') ,strtotime($value_to_return) );
                    }
                }
                // Email.
                elseif ($type == 'email') {
                    if (strpos($value_to_return, 'mailto') === false) {
                        $value_to_return = sprintf('<a href="mailto:%s">%s</a>',
                            $value_to_return,
                            $value_to_return);
                    }
                }
                // Web.
                elseif ($type == 'web') {
                    if (strpos($value_to_return, 'href=') === false) {
                        $value_to_return = sprintf('<a href="%s">%s</a>',
                            $value_to_return,
                            $value_to_return);
                    }
                } else {
                    // Not stripping tags.
                    $value_to_return = $value;
                }
            }

            return apply_filters('bxmtf_show_field_value', $value_to_return, $type, $id, $value);
        }



        public function bxmtf_signup_validate()
        {
            global $bp;
            if ( bp_is_active( 'xprofile' ) )
            {
                if ( isset( $_POST['signup_profile_field_ids'] ) && !empty( $_POST['signup_profile_field_ids'] ) )
                {
                    $profile_field_ids = explode(',', $_POST['signup_profile_field_ids']);
                    foreach ($profile_field_ids as $field_id)
                    {
                        $field = new BP_XProfile_Field($field_id);
                        if ( ($field->type == 'image' || $field->type == 'file') &&
                            isset($_FILES['field_'.$field_id])) {
                            // Delete required field error.
                            unset($bp->signup->errors['field_'.$field_id]);

                            $filesize = round($_FILES['field_'.$field_id]['size'] / (1024 * 1024), 2);
                            if ($field->is_required && $filesize <= 0) {
                                $bp->signup->errors['field_' . $field_id] = __( 'This is a required field', 'buddypress' );
                            } elseif ($filesize > 0) {
                                // Check extensions.
                                $ext = strtolower(substr($_FILES['field_'.$field_id]['name'], strrpos($_FILES['field_'.$field_id]['name'],'.')+1));
                                if ($field->type == 'image') {
                                    if (!in_array($ext, $this->images_ext_allowed)) {
                                        $bp->signup->errors['field_'.$field_id] = sprintf(__('Image type not allowed: (%s).', 'bxmtf'), implode(',', $this->images_ext_allowed));
                                    }
                                    elseif ($filesize > $this->images_max_filesize) {
                                        $bp->signup->errors['field_'.$field_id] = sprintf(__('Max image upload size: %s MB.', 'bxmtf'), $this->images_max_filesize);
                                    }
                                } elseif ($field->type == 'file') {
                                    if (!in_array($ext, $this->files_ext_allowed)) {
                                        $bp->signup->errors['field_'.$field_id] = sprintf(__('File type not allowed: (%s).', 'bxmtf'), implode(',', $this->files_ext_allowed));
                                    }
                                    elseif ($filesize > $this->files_max_filesize) {
                                        $bp->signup->errors['field_'.$field_id] = sprintf(__('Max file upload size: %s MB.', 'bxmtf'), $this->files_max_filesize);
                                    }
                                }
                            }
                        }
                        elseif ($field->type == 'checkbox_acceptance' && $field->is_required) {
                            if (isset($_POST['field_' . $field_id])
                                && $_POST['field_' . $field_id] != 1) {
                                $bp->signup->errors['field_' . $field_id] = __( 'This is a required field', 'buddypress' );
                            }
                        }
                    } // End foreach...
                } // End if ( isset...
            } // End if ( bp_is_active(...
        }

        function bxmtf_xprofile_data_before_save($data)
        {
            global $bp;

            $field_id = $data->field_id;
            $field = new BP_XProfile_Field($field_id);

            if ($field->type == 'membertype')
            {
                //Do something
            }
        }

        public function bxmtf_xprofile_data_after_delete($data)
        {
            $field_id = $data->field_id;
            $field = new BP_XProfile_Field($field_id);
            $uploads = wp_upload_dir();
            if ($field->type == 'image' && isset($_POST['field_'.$field_id.'_deleteimg']) &&
                $_POST['field_'.$field_id.'_deleteimg'])
            {
                if (isset($_POST['field_'.$field_id.'_hiddenimg']) &&
                    !empty($_POST['field_'.$field_id.'_hiddenimg']) &&
                    file_exists($uploads['basedir'] . $_POST['field_'.$field_id.'_hiddenimg']))
                {
                    unlink($uploads['basedir'] . $_POST['field_'.$field_id.'_hiddenimg']);
                }
            }

            if ($field->type == 'file' && isset($_POST['field_'.$field_id.'_deletefile']) &&
                $_POST['field_'.$field_id.'_deletefile'])
            {
                if (isset($_POST['field_'.$field_id.'_hiddenfile']) &&
                    !empty($_POST['field_'.$field_id.'_hiddenfile']) &&
                    file_exists($uploads['basedir'] . $_POST['field_'.$field_id.'_hiddenfile']))
                {
                    unlink($uploads['basedir'] . $_POST['field_'.$field_id.'_hiddenfile']);
                }
            }
        }

        public function bxmtf_map($field_type, $field)
        {
            switch($field_type) {
                case 'birthdate':
                case 'datepicker':
                    $field_type = 'datebox';
                    break;

                case 'email':
                case 'web':
                case 'image':
                case 'file':
                case 'color':
                    $field_type = 'textbox';
                    break;

                case 'decimal_number':
                    $field_type = 'number';
                    break;

                case 'select_custom_post_type':
                case 'multiselect_custom_post_type':
                case 'checkbox_acceptance':
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