<?php
/**
 * Member Type Field
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if (!class_exists('Bxmtf_Field_Type_MemberType'))
{
    class Bxmtf_Field_Type_MemberType extends BP_XProfile_Field_Type
    {
        /**
         * Constructor for the MemberType field type.
         *
         */
        public function __construct() {
            parent::__construct();

            $this->name       = _x( 'Member Type', 'xprofile field type', 'buddypress' );
            //$this->category = _x( 'Single Fields', 'xprofile field type category', 'buddypress' );

            $this->supports_options           = false;     //Set to true enable options during field setup
            $this->supports_multiple_defaults = false;
            $this->accepts_null_value         = false;

            do_action( 'bp_xprofile_field_type_membertype', $this );
        }

        /**
         * Output HTML for this field type on the wp-admin Profile Fields screen.
         *
         * Must be used inside the {@link bp_profile_fields()} template loop.
         *
         * @since 2.0.0
         *
         * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
         */
        public function admin_field_html (array $raw_properties = array ())
        {
            ?>

            <label for="<?php bp_the_profile_field_input_name(); ?>" class="screen-reader-text"><?php esc_html_e( 'Select', 'buddypress' ); ?></label>
            <select <?php echo $this->get_edit_field_html_elements( $raw_properties ); ?>>
                <?php bp_the_profile_field_options(); ?>
            </select>

            <?php
        }

        /**
         * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
         *
         * Must be used inside the {@link bp_profile_fields()} template loop.
         *
         * @since 2.0.0
         *
         * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
         * @param string            $control_type  Optional. HTML input type used to render the current
         *                                         field's child options.
         */
        public function admin_new_field_html (BP_XProfile_Field $current_field, $control_type = '')
        {
            parent::admin_new_field_html( $current_field, 'checkbox' );
        }

        /**
         * Output the edit field HTML for this field type.
         *
         * Must be used inside the {@link bp_profile_fields()} template loop.
         *
         * @since 2.0.0
         *
         * @param array $raw_properties Optional key/value array of
         *                              {@link http://dev.w3.org/html5/markup/input.checkbox.html permitted attributes}
         *                              that you want to add.
         */
        public function edit_field_html( array $raw_properties = array() ) {

            // User_id is a special optional parameter that we pass to
            // {@link bp_the_profile_field_options()}.
            if ( isset( $raw_properties['user_id'] ) ) {
                $user_id = (int) $raw_properties['user_id'];
                unset( $raw_properties['user_id'] );
            } else {
                $user_id = bp_displayed_user_id();
            }
            if ($user_id) { //We can't change member type after signup!
                $raw_properties['disabled'] = true;
            }
            ?>

            <div class="member-type">
                <label for="<?php bp_the_profile_field_input_name(); ?>">
                    <?php bp_the_profile_field_name(); ?>
                    <?php bp_the_profile_field_required_label(); ?>
                </label>

                <?php

                /** This action is documented in bp-xprofile/bp-xprofile-classes */
                do_action( bp_get_the_profile_field_errors_action() ); ?>

                <select <?php echo $this->get_edit_field_html_elements( $raw_properties ); ?>>
                    <?php bp_the_profile_field_options( array(
                        'user_id' => $user_id
                    ) ); ?>
                </select>

                <?php if ( ! bp_get_the_profile_field_is_required() ) : ?>

                    <a class="clear-value" href="javascript:clear( '<?php echo esc_js( bp_get_the_profile_field_input_name() ); ?>[]' );">
                        <?php esc_html_e( 'Clear', 'buddypress' ); ?>
                    </a>

                <?php endif; ?>

            </div>

        <?php
        }

        /**
         * Output the edit field options HTML for this field type.
         *
         * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
         * These are stored separately in the database, and their templating is handled separately.
         *
         * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
         * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
         *
         * Must be used inside the {@link bp_profile_fields()} template loop.
         *
         * @since 2.0.0
         *
         * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
         */
        public function edit_field_options_html( array $args = array() ) {

            $member_types = bp_get_member_types( array(), 'objects');
            //@todo: insert here the possibility to rearrange/sort the members type array

            $current_member_type = '';
            if ($args['user_id']) { //let's retrieve the members type
                $current_member_type = bp_get_member_type($args['user_id']);
            }

            $html = '';
            foreach ($member_types as $member_type) {
                $selected = '';
                if ($current_member_type && $current_member_type == $member_type->name) {
                    $selected = 'selected="selected"';
                }
                $html .= '<option ' . $selected . ' value="' . $member_type->name . '">' . $member_type->labels['singular_name'] . '</option>';
            }

            //@todo: insert here the possibility to modify the html
            echo $html;
        }

    }
}
